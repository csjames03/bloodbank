# Architecture

LifeLine is a small, dependency-free MVC application. This document explains how a request moves through the code, how data is modelled, and where each business rule lives.

## Request flow

```
Browser
  │
  ▼
public/index.php ──► src/bootstrap.php     env + config, error handling, session
  │
  ▼
src/routes.php  ──►  Core\Router            match path, verify CSRF (POST), run guard
  │
  ▼
Controllers\*       validate input (Core\Validator)
  │
  ├──► Services\*       business rules inside DB transactions
  │       │
  │       ▼
  └──► Repositories\*   SQL (prepared statements only)
  │
  ▼
Core\View           render views/<page>.php inside views/layouts/<layout>.php
```

Every state-changing request follows **Post/Redirect/Get**:

1. The controller validates the input and calls a service.
2. On success it flashes a toast message and redirects.
3. On failure it flashes the errors and the old input, along with the ID of the dialog the form lives in. The next page reopens that dialog with the errors shown.

## Directory guide

| Path | Responsibility |
|---|---|
| `src/Core/Router.php` | Exact paths plus `{id}` params. Per-route guards (`guest`, `auth`, `admin`, `hospital`) and CSRF checks on every POST. |
| `src/Core/Database.php` | A single lazy PDO connection (exceptions on, real prepared statements) with a `transaction()` helper. |
| `src/Core/Session.php` | Hardened session cookie, plus flash storage that lasts exactly one request. |
| `src/Core/Validator.php` | Declarative rules: `required`, `int`, `max`, `min`, `between`, `in`, `regex`. |
| `src/Core/Auth.php` | Session login for the two roles. Regenerates the session ID on login. |
| `src/Domain/BloodGroup.php` | Enum of the 8 groups with red-cell compatibility (`canReceiveFrom()`). |
| `src/Domain/RequestStatus.php` | Enum for the request lifecycle: allowed transitions, labels and tracker step. |
| `src/Domain/Priority.php` | Routine / urgent / emergency, with queue rank and badge tone. |
| `src/Domain/Actor.php` | Who is acting (role, id, name). Passed to services for ownership rules and the audit log. |
| `src/Services/RequestService.php` | Create, allocate and dispatch, receive and cancel requests, with row locks, stock changes and audit entries. |
| `src/Repositories/ActivityRepository.php` | Append-only audit log, plus the latest actor per action for request cards. |
| `src/Services/DonationService.php` | Record donations (creating quarantined units), and edit or delete them while they are still unscreened. |
| `src/Services/ScreeningService.php` | Lab screening: pass (optionally re-grouping) releases units to stock; fail discards them and defers the donor. |
| `src/Services/InventoryService.php` | Expiry sweep (expire units, warn about units nearing expiry) and manual discards with a reason. |
| `src/Repositories/UnitRepository.php` | Every stock movement: create, release, FEFO pick with `SKIP LOCKED`, dispatch, recall, discard, expire. |
| `src/Domain/UnitStatus.php`, `ScreeningFailure.php`, `DiscardReason.php` | Unit lifecycle; screening outcomes and the donor deferral each implies; reasons for discards. |
| `src/Services/Notifier.php` | Turns business events into notifications. Services call it inside their transaction. |
| `src/Domain/NotificationType.php` | Enum of notification kinds, each with an icon and colour tone. |
| `src/Repositories/*` | All SQL lives here; controllers never write queries. |
| `src/helpers.php` | View helpers: `e()`, `url()`, `icon()`, `old()`, `error()`, formatting. |
| `views/partials/*` | Reusable UI: blood grid, request tracker card, form dialogs, pagination, icons. |
| `public/assets/` | `app.css` (design system) and `app.js` (drawer, dialogs, confirmations, live stock check). |

## Data model

```
admins            hospitals ─────────────┐
                     │ 1                  │ 0..1 (collected via)
                     ▼ *                  ▼ *
               blood_requests          donations ◄──* donors
                │ 1     │ 0..1            │ 1
                │       └───────┐         ▼ *
                ▼ *             └──── blood_units ───► blood_types
         request_allocations    (one row per bag:      (the 8 groups)
                                 status, expiry, FEFO)
```

| Table | Notes |
|---|---|
| `blood_types` | One row per group. No stock column: stock is counted live from `blood_units`, so it can never drift. |
| `blood_units` | **One row per physical bag.** `unit_code` (`LL000123`, generated), group, `status` (`quarantine`, `available`, `dispatched`, `discarded`, `expired`), `collected_at`, `expires_at` (collection + shelf life), `screened_at`, the `request_id` it was issued to, discard time and reason, and `expiry_warned_at`. A `CHECK` ties `dispatched` to having a request. |
| `donors` | Personal details and their usual blood group. Age is checked to be 16–65. Deferral fields (`deferred_permanently`, `deferred_until`, `deferral_reason`) are set when a donation fails screening. |
| `donations` | Each collection: bags (1–2), the group it was recorded under, and an optional collecting hospital. Creates one unit per bag. |
| `blood_requests` | The patient's group, doctor, bags requested (1–20), `priority`, optional `needed_by`, `status`, plus `approved_at`, `completed_at` and `cancelled_at` timestamps for the tracker. |
| `request_allocations` | What was actually sent for a request: one row per donor group with its bag count. It may differ from the request (a substitute) and total less (a partial fill). |
| `activity_log` | Audit trail: subject (`request`/`donation`/`unit`), action, actor role, id and name (copied, so the history survives account changes), a note, and the time. |
| `admins`, `hospitals` | Accounts with `password_hash` (bcrypt via `password_hash()`). |
| `notifications` | One row per recipient (`recipient_role` + `recipient_id`), with `type`, `title`, `body`, an internal `link`, an optional `request_id`, and `read_at`. Staff alerts fan out to every admin account. |

### Stock and the unit lifecycle

```
quarantine ──pass──▶ available ──dispatch (FEFO)──▶ dispatched
     │                  │  ▲                            │
     │                  │  └────────── recall ──────────┘
     └── fail / discard ┴──▶ discarded
quarantine | available ──(past expires_at)──▶ expired
```

**Stock for a group = units with `status = 'available'` and `expires_at > now()`.** It is never stored, so there is nothing to drift. A unit past its expiry stops counting the instant it expires, even before the sweep marks it.

The e2e suite checks these invariants at the end of its run:

- Every dispatched unit belongs to an in-transit or received request.
- Per request and group, the units issued equal the recorded allocation.
- Every donation has exactly one unit per bag.
- No unit reached stock or a hospital unscreened.
- No out-of-date unit is still `available`.
- Every discard has a reason.

### Expiry sweep

`InventoryService::sweep()` runs at the start of every signed-in request (`public/index.php`), so no cron job is needed. In one transaction it:

1. marks quarantined or available units past `expires_at` as `expired` (`FOR UPDATE SKIP LOCKED`, so each unit is handled once even when requests overlap), alerts staff (`units_expired`), logs it as the *System* actor, and raises low or out-of-stock alerts if a group crossed the threshold;
2. flags available units entering the warning window (`expiry_warning_days`, 7) via `expiry_warned_at`, and sends one grouped `units_expiring` alert.

A failure in the sweep is logged and never breaks the page.

## Business rules

### Requests

```
pending ──allocate & dispatch──► in_transit ──receive──► completed ("Received")
   │                                 │
   └────────────cancel───────────────┴──────► cancelled
```

| Rule | Where |
|---|---|
| Requests are **always accepted**, whatever the stock. Demand above stock is a signal for the blood bank, not an error. The hospital is told it may get a substitute or a partial fill. | `RequestService::create`, `RequestController::store` |
| "Dr." is added to the doctor's name unless it is already there. A "needed by" time in the past is rejected. | `RequestService::create`, `RequestController::store` |
| **Triage order:** pending first, by priority (emergency → urgent → routine), then earliest `needed_by`, then oldest. In transit next, closed last. | `RequestRepository::ORDER` |
| **Allocate & dispatch** (staff only) locks the request, then validates the split: every group must be compatible with the patient, and the total must be between 1 and the requested bags. For each group (in a fixed order) the **soonest-expiring units are picked (FEFO)** with `SELECT … FOR UPDATE SKIP LOCKED`, so two concurrent dispatches can never issue the same bag. Too few units in any group rolls back the whole dispatch. The units are marked `dispatched` with the request id. | `RequestService::approve`, `UnitRepository::pickFefo` |
| **Default split** (also the no-JavaScript fallback): the patient's group first, then other compatible groups by stock (most first), with O− last. | `BloodGroup::preferredDonors`, `BloodGroup::suggestAllocation` |
| **Receive:** the hospital confirms its own delivery. Staff may record it for them, which is noted in the audit log. | `RequestService::receive` |
| **Cancel** on an in-transit request recalls its units: back to `available`, or `expired` if their shelf life ran out while they were out. | `RequestService::cancel`, `UnitRepository::recall` |
| Hospitals may cancel only **their own pending** requests. Staff may cancel pending or in-transit ones. | `RequestService::cancel` |
| Completed and cancelled requests are final. | `RequestStatus::canTransitionTo` |
| Every state change writes an `activity_log` row with the actor, in the same transaction. | `ActivityRepository::log` |

### Donations

| Rule | Where |
|---|---|
| Recording a donation creates one **quarantined** unit per bag; nothing reaches stock until screening. A **returning donor** (same first name, last name and phone, ignoring case, spacing and punctuation) is reused rather than duplicated. | `DonationService::record`, `DonationRepository::findReturningDonor` |
| A returning donor must not be deferred, must wait `donation_interval_days` (56) since their last donation, and must be on record with the same blood group. | `DonationService::checkReturningDonor` |
| Donor details can always be edited. **Blood group and bag count can only change while every unit is still in quarantine** (the units are recreated). After screening, corrections are explicit: re-group at screening, or discard a unit. | `DonationService::update` |
| A donation can be deleted only before screening; its units go with it. | `DonationService::delete` |
| Deleting a donor's last donation also deletes the donor. | `DonationRepository::deleteDonorIfOrphaned` |

### Screening and discards

| Rule | Where |
|---|---|
| **Pass** (staff only): the lab-confirmed group is chosen. If it differs from the recorded one, the donation, its units and the donor are re-grouped. Units are released to `available`, or `expired` if they ran out of shelf life while waiting. | `ScreeningService::pass` |
| **Fail** (staff only) with a reason: every unit is discarded as "Failed screening: …". HIV and hepatitis B/C defer the donor **permanently**; syphilis and malaria for **365 days**; a sample problem doesn't defer. | `ScreeningService::fail`, `ScreeningFailure` |
| A donation can only be screened once. | `ScreeningService::lockQuarantined` |
| **Discard** (staff only): only quarantined or available units, always with a reason (damaged, cold-chain breach, haemolysed, clotted, labelling, other) plus an optional note. Logged, and it triggers low-stock alerts. | `InventoryService::discard` |

### Notifications

Each notification is written in the same transaction as the change that caused it, so a rolled-back action never leaves a notification behind.

| Event | Recipient | Type |
|---|---|---|
| Hospital submits a request | All staff | `request_created`, or `request_emergency` for emergencies |
| Hospital confirms receipt | All staff | `request_received` |
| Staff file a request for a hospital | That hospital | `request_filed` |
| Request dispatched (the body names any substitute group and any partial fill) | Hospital | `request_dispatched` |
| Staff record receipt on the hospital's behalf | Hospital | `request_delivered` |
| Staff cancel a request (an in-transit cancel says the dispatch was recalled) | Hospital | `request_cancelled` |
| Hospital withdraws its own request | All staff | `request_withdrawn` |
| Hospital logs a walk-in donor | All staff | `donation_logged` |
| A group drops to the low-stock threshold, or to 0 (dispatch, discard or expiry) | All staff | `stock_low` / `stock_out` |
| Units enter the expiry-warning window | All staff | `units_expiring` |
| Units expire | All staff | `units_expired` |

Stock alerts fire only when a group *crosses* the threshold, not on every later change.

**Delivery to the browser:**

- The layout embeds the newest notification ID and the unread count.
- `app.js` polls `GET /notifications/poll?after=<id>` every 30 seconds while the tab is visible, and immediately when it becomes visible again.
- The response holds the unread count, the pending-request count (staff only) and any new unread items. These update the nav badges, the mobile bell and the tab title, and show a toast for each new item.
- Polling stops if the session has ended.

Opening a notification is a POST to `/notifications/{id}/open`. It marks the notification read and redirects to its link, which jumps to the request card. Ownership is checked in the `UPDATE`, so an ID from another inbox does nothing. Read notifications older than 90 days are pruned whenever the inbox is opened.

## Security

| Concern | Measure |
|---|---|
| SQL injection | Native prepared statements everywhere (`ATTR_EMULATE_PREPARES = false`). `LIKE` wildcards in search input are escaped. |
| XSS | Every value printed in a view goes through `e()` (`htmlspecialchars`, `ENT_QUOTES`). |
| CSRF | A per-session token is checked on every POST by the router. Logout is POST-only. |
| Passwords | `password_hash` / `password_verify`. Login errors don't reveal whether the username exists. Five failures within 60 seconds trigger a lockout. |
| Sessions | `HttpOnly`, `SameSite=Lax` and `Secure` when on HTTPS. Strict mode is on, and the session ID is regenerated on login. |
| Authorization | Route guards plus ownership checks in the services (hospitals only see and cancel their own requests). |
| Errors | Warnings are promoted to exceptions. Users see a friendly error page, and stack traces appear only when `APP_DEBUG=true`. |
| Exposure | Only `public/` is web-facing. The root `.htaccess` keeps source, config and SQL files out of reach on Apache. |

## Front end

- **No build step.** The CSS uses custom properties (design tokens on `:root`) and redefines them for dark mode.
- **Mobile-first breakpoints:** 560px (forms go to two columns), 768px (cards become tables), 1024px (fixed sidebar instead of a drawer).
- **Native `<dialog>`** is used for every modal. On phones they become bottom sheets with safe-area padding.
- **Progressive enhancement.** Every action is a real `<form>` POST, and JavaScript only adds polish: confirmation dialogs, filters that submit on change, the live stock warning and double-submit protection.
- **Icons** are an inline SVG sprite (`views/partials/icons.php`), so no external icon requests are made.

## Configuration

All settings live in `config/config.php`, and each can be overridden from `.env` or an environment variable:

| Key | Default |
|---|---|
| `DB_HOST`, `DB_PORT`, `DB_DATABASE` | `127.0.0.1`, `5432`, `bloodbank` |
| `DB_USERNAME`, `DB_PASSWORD` | `postgres`, `postgres` |
| `APP_DEBUG` | `true` (set to `false` in production) |
| `APP_TIMEZONE` | `Asia/Manila` |
| `APP_BASE_PATH` | auto-detected |
| `LOW_STOCK_THRESHOLD` | `5` bags |
| `DONATION_INTERVAL_DAYS` | `56` days between donations by the same donor |
| `SHELF_LIFE_DAYS` | `35` days from collection (whole blood, CPDA-1) |
| `EXPIRY_WARNING_DAYS` | `7`: units expiring within this many days are flagged and alerted |
