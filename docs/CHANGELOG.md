# Changelog

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
