# Changelog

## 2.4.0 — Days of supply, donor recall and live updates (2026-11-20)

The app now looks ahead: it measures how long stock will last, points staff to the donors to call, and pushes updates as they happen.

### Changed

- **Stock alerts are based on days of supply, not a fixed bag count.** Each group's usable units are divided by its average daily issue over the last 30 days. Groups are rated Healthy (7+ days), Low (under 7), Critical (under 3) or Out. Alerts fire only when a group's level gets worse, state the days left and the number of eligible donors, and link to the recall list. A new `stock_critical` alert sits between Low and Out.
- **Hospitals see availability levels, not counts:** Available, Limited, Critical or Unavailable. Exact numbers are no longer in hospital pages, request-form data or confirmation messages.
- The out-of-stock alert no longer says new requests will be refused. Since 2.2.0 they never are.
- **Redesigned sign-in page.** It has a brand panel with an animated heartbeat line and current feature highlights (a compact header on phones), and a card with a portal switch and icon inputs. A failed sign-in shows one inline error banner instead of a toast plus a field error, and there is a Caps Lock warning. Demo-account chips fill in the credentials in one tap, and a link switches to the other portal. The animation respects reduced-motion settings.

### Added

- **Days of supply** on the stock page (with average daily use per group), on dashboard tiles, and as an "N groups need a recall" figure.
- **Donor recall page** (`/recall`, with a nav badge):
  - opens on the worst group, with a strip showing every group's days of supply;
  - lists eligible donors (not deferred, 56+ days since their last donation), not-recently-called first, then most loyal, then longest rested;
  - click-to-call phone numbers and a call log (booked, reached, no answer, declined; with a note). Called donors drop down the list for 7 days;
  - CSV export of the call list.
- New `donor_contacts` table. Calls appear in the audit log.
- **Real-time updates over Server-Sent Events** (`REALTIME_MODE=sse`): `/notifications/stream` waits on PostgreSQL `LISTEN`/`NOTIFY`, which `NotificationRepository` signals whenever it stores a notification. Pushes are only delivered on commit. The stream reconnects automatically with `Last-Event-ID` and falls back to polling. It is disabled automatically on the single-threaded `php -S`.
- `Database` now connects with `PDO::connect()` (giving `Pdo\Pgsql` with `getNotify()`) and has `listen()` / `waitForNotify()`.
- Config: `SUPPLY_WINDOW_DAYS`, `SUPPLY_LOW_DAYS`, `SUPPLY_CRITICAL_DAYS`, `RECALL_COOLDOWN_DAYS`, `REALTIME_MODE`, `POLL_SECONDS`, `STREAM_SECONDS`.
- **Demo data:** 40 lapsed donors who make up the recall lists, and 150 recent donors whose units were issued over the past month. This gives real usage history, and O− (in heavy demand as the universal donor) shows as Low.

### Tests

- The e2e suite grew from 169 to 194 checks:
  - days-of-supply display and the Low alert (built from live usage data);
  - recall eligibility, ranking, call logging and its refusals, the audit entry, the CSV export, and staff-only access;
  - hospitals never receiving stock counts;
  - the stream's SSE format and guest refusal;
  - a direct test that `NOTIFY` reaches listeners only after commit, never after rollback.
- The unit suite grew from 33 to 45 checks (supply levels and boundaries, labels, day formatting).

### Fixed

- A dispatch was being measured against a usage rate that already included itself, which could hide the alert it should trigger.
- Inline action panels (Log call, Discard) now take the full row width on phones.
## 2.3.0 — Unit-level inventory, screening and expiry (2026-11-19)

Stock is no longer a number per blood group. Every bag is tracked individually from collection to dispatch, so the stock figure reflects only blood that is screened, in date and usable.

### Changed

- **Stock is calculated, not stored.** `blood_types.bags` is gone. Stock is the number of units with status `available` and an expiry in the future, counted live. Nothing can drift, and a unit stops counting the moment it expires.
- **Donations no longer go straight into stock.** Each bag becomes a quarantined unit until it passes lab screening.
- **Dispatch issues specific units, first-expired-first-out (FEFO).** It uses `SELECT … FOR UPDATE SKIP LOCKED`, so two staff dispatching at once can never issue the same bag. Too few units in any group rolls back the whole dispatch.
- **Recall returns the actual units:** back to stock, or marked expired if their shelf life ran out while they were out.
- **Donation corrections are now explicit.** Blood group and bag count can change only before screening, and only an unscreened donation can be deleted. After screening, correct the group at screening or discard a unit with a reason. Donor details stay editable.

### Added

- **`blood_units` table:** unit code (`LL000123`), group, status (`quarantine`, `available`, `dispatched`, `discarded`, `expired`), collection and expiry dates (35-day shelf life), screening time, the request it was issued to, discard reason and expiry-warning flag.
- **Lab screening page** (`/screening`, with a badge in the menu):
  - Pass with the lab-confirmed group. A different group re-groups the donation, its units and the donor.
  - Fail with a reason. Every unit is discarded and the donor deferred: HIV and hepatitis B/C permanently, syphilis and malaria for 12 months, sample problems not at all.
- **Donor deferral:** deferred donors are refused when they come back.
- **Units page** (`/units`): every bag with its status, dates and whereabouts. Filter by status (including "expiring within 7 days"), group or unit code. Discard requires a reason (damaged, cold-chain breach, haemolysed, clotted, labelling, other) plus an optional note.
- **Expiry sweep:** runs automatically on every signed-in request, with no cron job needed. It expires out-of-date units and sends **"N units expired"** and **"N units expire within 7 days"** notifications, once per unit. Expiry and discards also trigger the low and out-of-stock alerts.
- **Dashboard:** an "Expiring within 7 days" panel, and an "Awaiting screening" figure.
- **Stock page:** available, expiring, in quarantine and wasted (last 30 days), plus per-group next-expiry dates.
- **Allocation dialog** shows the oldest expiry per group.
- **Request cards list the exact units sent** and their use-by dates, for traceability.
- **Audit log** entries for screening, discards and system expiry.
- Config: `SHELF_LIFE_DAYS` (35) and `EXPIRY_WARNING_DAYS` (7).
- **Demo data** rebuilt around units: 91 donors, recent collections, five donations awaiting screening, units close to expiry, weekly ward restocks that consumed older units, some expired units, one donation that failed screening (donor deferred) and one leaking bag that was discarded.

### Tests

- The e2e suite grew from 123 to 169 checks. New coverage:
  - quarantine, screening pass, re-group and fail;
  - deferral, and edits and deletes before and after screening;
  - FEFO unit issue, unit traceability, discards and every refusal;
  - the expiry sweep, with alerts sent once only;
  - a concurrency check proving a unit locked by another dispatch is skipped;
  - new integrity checks: every dispatched unit belongs to an active request, units issued match each allocation, one unit per bag, nothing unscreened in stock, nothing out of date still available, every discard has a reason.
- The unit suite grew from 23 to 33 checks (unit statuses, screening deferrals, expiry labels).

### Fixed

- The screening queue's group selector no longer stretches full width on desktop.
## 2.2.0 — Clinically sound request flow (2026-11-19)

The request flow was reworked so it fits how a blood bank actually works.

### Changed

- **Requests are never refused for low stock.** Previously a hospital asking for more than was in stock was blocked, which lost real demand and pushed hospitals into asking several times for small amounts. Now the request is accepted, and the hospital is told it may get a compatible substitute or a partial fill.
- **Substitution moved from the hospital to the blood bank.** Instead of telling the hospital to re-request a different group, staff choose what to send at dispatch.
- **The hospital confirms receipt**, not the blood bank. "Delivered" is now "Received". Staff can still record it on the hospital's behalf, and the audit log notes that they did.
- **Stock is counted by the group actually sent.** A new `request_allocations` table records the split, and inventory, the stock invariant and cancellation all use it.

### Added

- **Priority** (routine, urgent or emergency) and an optional **needed by** time on requests. A needed-by time in the past is rejected.
- **Triage queue:** pending requests are ordered by priority, then needed-by, then age. Emergency cards have a red edge, and overdue needed-by times show in red.
- **Emergency alerts:** a separate red `request_emergency` notification for staff.
- **Allocate & dispatch dialog:** lists every compatible group with its stock and a suggested split (own group first, O− last). It shows a live total, warns about partial fills and blocks invalid splits. Without JavaScript, the suggested split is submitted.
- **Server-side allocation rules:** groups must be compatible, the total must be between 1 and the requested bags, and each group must have the stock. A failure rolls back the whole dispatch.
- **Hospital notifications say exactly what was sent**, e.g. "Sending 1 O+, 1 O-. Compatible substitute for O+." or "Partial fill: 2 of 3 bags."
- **Receipt notification to staff** when a hospital confirms a delivery.
- **Audit trail** (`activity_log`): who created, dispatched, received or cancelled each request, and who recorded, edited or deleted each donation, with details. Request trackers show "by …" under each step, and staff have a new **Activity** page with filters.
- **Returning donors:** matched by name and phone (ignoring case, spacing and punctuation), so donation history stays together. Enforces the **56-day donation interval** (`DONATION_INTERVAL_DAYS`) and the blood group on record.
- **Unit test suite** (`tests/unit.php`, 23 checks) for compatibility, preferred-donor order and suggested allocation.
- The e2e suite grew from 83 to 123 checks.

### Fixed

- Cancelled-request badges had no colour, because the `badge-neutral` style was missing.
- PHP 8.5 deprecation: a `null` array key on the activity filters.

## 2.1.0 — Notifications (2026-11-19)

- **In-app notification inbox** for every account, at `/notifications`, with All and Unread filters, paging and *Mark all as read*.
- **Staff** are notified of new and withdrawn requests, walk-in donors logged by hospitals, and blood groups that drop to the low-stock threshold or run out.
- **Hospitals** are notified when their request is dispatched, delivered or cancelled (including recalled dispatches), and when staff file a request for them.
- **Live updates:** the unread badge (sidebar and mobile bell), the pending-requests badge and the tab title refresh every 30 seconds without a reload, and new items pop up as toasts.
- Clicking a notification marks it read and jumps to the highlighted request card.
- Notifications are written in the same database transaction as the event, so a refused action creates none.
- New `notifications` table. Run `php database/setup.php` to rebuild; the demo data includes sample notifications.
- The e2e suite grew from 62 to 83 checks.

## 2.0.0 — Rewrite (2026-11-19)

The app was rebuilt for current PHP. Business rules were fixed and moved out of the views, and the UI was redesigned to work on every screen size. The feature set is the same as before, with additions noted below.

### Platform

- **PHP 8.5** with `strict_types` throughout. Uses enums, readonly properties, `match`, first-class callables and constructor promotion.
- **MySQL → PostgreSQL**, with a proper schema: foreign keys, `CHECK` constraints (stock can never go negative) and indexes.
- **Structure:** front controller, router, controllers, services and repositories, with all SQL in repositories. This replaces procedural controllers that mixed SQL, HTML and logic.
- One-command setup with `php database/setup.php`, configured through `.env`.
- Removed the jQuery, SweetAlert (two versions) and Tailwind Play CDN dependencies, along with the committed `node_modules/`. The app now uses its own CSS and about 250 lines of vanilla JS.

### Logic bugs fixed

| # | Old behaviour | Now |
|---|---|---|
| 1 | Approving a request marked it **In Progress even when stock was insufficient**. The status changed, but no stock was deducted. | Approval runs in one transaction: lock, check, deduct, then change status. Insufficient stock is refused. |
| 2 | `bagsUpdate()` refused to bring a group to exactly **0 bags**, so the last bags could never be dispatched. | A conditional atomic `UPDATE` allows anything ≥ 0. |
| 3 | Approving the same request twice deducted stock twice. | Status transitions are enforced and the request row is locked during the change. |
| 4 | Cancelling a dispatched request lost the bags. | Cancelling an in-transit request returns its bags to stock. |
| 5 | Editing a donation updated **every** donation by that donor, and stock never changed. | Only that donation is edited, and stock is adjusted by the difference (or moved between groups). |
| 6 | Dashboard donation form: the **O and AB options submitted "A" and "B"**. | Every blood type is submitted correctly. |
| 7 | Stock page total used `$totalStocks += $totalStocks` (always 0), and every card showed A+ figures. | Totals and per-group figures are computed in SQL. |
| 8 | Requests page summed **all requests, including cancelled ones**, as "stock". | Separate metrics for available, collected, dispatched and pending. |
| 9 | Donations page tested stock against `$type` left over from a previous loop. | Validation reads the selected group. |
| 10 | Request forms trusted a client-supplied hospital value (an empty hidden field on the dashboard), and nothing checked that the hospital existed. | Hospitals always request as themselves, from the session; staff must pick a hospital that exists. |
| 11 | Search by hospital only matched an exact full name. | Partial, case-insensitive search on doctor or hospital. |
| 12 | Leftover `var_dump(); die();` in the dashboard controller could crash the page. | Removed. |
| 13 | Hard-coded `http://localhost/bloodbank/...` redirects and `/bloodbank/` routes. | Base path is detected automatically, so the app runs at `/` or in a subfolder. |
| 14 | Image paths with the wrong letter case (`truck-kun.png` vs `Truck-kun.png`) broke on Linux. | Images replaced by an inline SVG sprite. |

### Security fixes

- **Admin credentials were in plain text** in `adminconfig.php`, and hospitals signed in with their *name* plus *numeric ID* as the password. Both are replaced by hashed passwords in the database.
- The login page revealed whether a hospital name or admin username existed. It now shows one generic message, and there is a throttle after 5 failures.
- Added CSRF protection on every form. Logout changed from GET to POST.
- Output escaping everywhere. Previously, donor and request data was printed raw, which allowed stored XSS.
- `FetchAll($table)` concatenated a table name into SQL. Removed; all queries are fixed and prepared.
- Database errors were echoed to users. They are now logged, and users see a friendly page.
- Session ID is regenerated on login, with hardened cookie flags.
- Hospitals could reach admin pages by URL, because pages were only hidden with CSS. Server-side guards now return 403.

### UI / UX

- New visual design: tokens, consistent spacing and type, and dark mode.
- **Mobile responsive:** drawer navigation, tables that become cards, bottom-sheet forms and a 16px gutter. Tested at 390px.
- Delivery tracker with a timestamp for each step, replacing fixed-width progress bars.
- Stock tiles with fill meters and Low/Out flags, and status badges.
- Request form shows stock for each blood type and warns live, suggesting compatible groups.
- Forms keep what was typed and reopen with inline errors after a validation failure.
- Confirmation dialogs for approve, cancel and delete; toasts for outcomes; empty states; pagination; search and filter chips with counts.
- Accessibility: semantic landmarks, labels on every input, `aria-current`, visible focus rings, reduced-motion support and screen-reader text for icon buttons.

### New

- Red-cell **compatibility chart** and compatible-group suggestions ("Patient-Blood Matching" from the original feature list).
- Hospitals can cancel their own pending requests and log walk-in donors.
- Staff can delete donations (stock-safe).
- End-to-end test suite (`tests/e2e.php`, 62 checks at 2.0.0).
