# LifeLine — Blood Bank Management System

A web app for a regional blood bank and its partner hospitals. Staff record donations and keep **live stock for all eight blood groups**. Hospitals **request blood and follow each delivery** from request to hand-off.

Built with **PHP 8.5** and **PostgreSQL**. It has no framework and no Composer or Node dependencies, and it works on phones as well as desktops.

---

## Features

| Area | What it does |
|---|---|
| **Two portals** | Hospital sign-in (`/login`) and blood bank staff sign-in (`/admin/login`), each with its own permissions. |
| **Unit-level inventory** | Every bag is tracked individually with a unit code and a 35-day expiry. Stock is the count of screened, unexpired units, calculated live so it can't drift. Waste figures and an expiring-soon panel. |
| **Days of supply** | Each group's stock is measured against its average daily use over the last 30 days and rated Healthy, Low (under 7 days), Critical (under 3) or Out. Alerts fire when a group's rating gets worse, so 6 bags of a rarely used group isn't flagged while 6 bags of O− is. |
| **Donor recall** | For a short group, a ranked list of donors who are eligible to give again (not deferred, 56+ days since their last donation), with click-to-call, a call log (booked, reached, no answer, declined) and a CSV export for call sheets or SMS tools. |
| **Availability for hospitals** | Hospitals see Available, Limited, Critical or Unavailable per group, never exact counts, so stock levels don't trigger panic-ordering. |
| **Lab screening** | New donations are quarantined until screened. Pass (confirming or correcting the blood group) releases them to stock; fail discards them and defers the donor (permanently for HIV and hepatitis). |
| **Expiry management** | Dispatch issues the soonest-expiring units first (FEFO). An automatic sweep expires out-of-date units and alerts staff about units expiring within 7 days. Every discard needs a reason. |
| **Triage** | Requests carry a priority (routine, urgent or emergency) and an optional "needed by" time. Staff see a queue ordered by urgency, and emergencies raise an alert. |
| **Request lifecycle** | Pending → In transit → Received, or Cancelled. Requests are never refused for low stock. Staff **allocate** at dispatch: the patient's group, compatible substitutes or a partial fill. The **hospital confirms receipt**. |
| **Delivery tracker** | A three-step progress view with a timestamp for each step, shown to both hospitals and staff. |
| **Compatibility matching** | The allocation dialog lists only compatible groups and suggests a split that spares O−. A full red-cell compatibility chart is on the Stock page. |
| **Notifications** | An inbox for each account. Staff hear about new or withdrawn requests, walk-in donors, and groups that run low or out. Hospitals hear when their requests are dispatched, delivered or cancelled. Badges and a toast update live: real-time over Server-Sent Events fed by PostgreSQL `LISTEN`/`NOTIFY` on a production server, or by polling on the dev server. |
| **Donors** | Returning donors are matched by name and phone instead of being duplicated. The 56-day donation interval and the blood group on record are enforced. |
| **Donations** | Record, edit and remove donations. Stock adjusts automatically, and the app refuses changes that would remove bags already sent out. |
| **Audit trail** | Every action on requests and donations is logged with who did it. Request trackers show names, and staff have an Activity page. |
| **Search, filters, paging** | Donors by name or contact, requests by doctor or hospital. Filters by blood type and status, with paginated lists. |
| **Responsive UI** | Slide-out navigation, tables that turn into cards, and forms that open as bottom sheets on phones. Dark mode follows the OS setting. |
| **Security** | Hashed passwords, CSRF tokens on every form, prepared statements, escaped output, session hardening and a login throttle. |

## Quick start

**Requirements:** PHP 8.2+ (tested on 8.5) with the `pdo_pgsql` extension, and PostgreSQL 13+.

```bash
# 1. (Optional) copy the config — defaults are postgres/postgres on 127.0.0.1:5432
cp .env.example .env

# 2. Create the database, schema and demo data
php database/setup.php

# 3. Run it
php -S localhost:8000 -t public public/index.php
```

Then open **http://localhost:8000**.

| Portal | URL | Username | Password |
|---|---|---|---|
| Blood bank staff | `/admin/login` | `admin` | `admin123` |
| Hospital | `/login` | `riverside`, `northpoint` or `staclara` | `hospital123` |

> ⚠️ `php database/setup.php` **drops and recreates all tables**. Use `--no-seed` to create only the accounts, without demo donors or requests. Change the demo passwords before any real use.

### Running under Apache / XAMPP

Point the virtual host's document root at `public/`. Alternatively, drop the folder into `htdocs/`: the root `.htaccess` forwards requests into `public/`, and the URL prefix is detected automatically. Set `APP_BASE_PATH` in `.env` only if detection gets it wrong.

### Real-time updates

By default the browser checks for new notifications every 30 seconds (`REALTIME_MODE=poll`). That is the only mode that works on `php -S`, which handles one request at a time.

On Apache or Nginx with PHP-FPM, set `REALTIME_MODE=sse` for instant updates. Each open page keeps one PHP worker for up to `STREAM_SECONDS` (25), then reconnects, so make sure there are enough workers for the people signed in at once. On the built-in server the app falls back to polling automatically, unless `PHP_CLI_SERVER_WORKERS` is 2 or more (Linux/macOS only).

## Tests

The end-to-end suite drives the running app over HTTP the way a browser does (cookies, CSRF tokens, redirects) and checks the database after every step. It has **194 checks** covering both portals, permissions, triage, allocation (substitutes, partial fills and the rules that reject bad splits), FEFO unit issue, concurrent dispatch, screening (pass, re-group, fail, deferral), discards, the expiry sweep, days-of-supply alerts, donor recall and its CSV export, hiding stock counts from hospitals, the live stream and its commit-only push, receipt, returning donors, the audit log, notifications, validation, CSRF, the login throttle and a set of unit-level integrity checks. A separate unit suite (**45 checks**, no server needed) covers compatibility, allocation, screening outcomes, expiry labels and supply levels.

```bash
php -S 127.0.0.1:8000 -t public public/index.php   # terminal 1
php tests/e2e.php                                  # terminal 2
php tests/unit.php                                 # any time, no server or DB
```

The end-to-end suite resets the database to demo data before and after it runs.

## Documentation

- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md): project layout, request flow, data model and business rules
- [docs/USER_GUIDE.md](docs/USER_GUIDE.md): how to use each screen, for staff and hospitals
- [docs/CHANGELOG.md](docs/CHANGELOG.md): what changed in the 2.0 rewrite and which bugs it fixed

## Project layout

```
config/      configuration (env-driven)
database/    schema.sql, seed.sql, setup.php
public/      web root: index.php front controller, CSS, JS, favicon
src/
  Core/          router, request, view, session, CSRF, auth, validator, DB
  Domain/        BloodGroup (compatibility), RequestStatus (lifecycle)
  Repositories/  all SQL
  Services/      transactional business rules (requests, donations)
  Controllers/   thin HTTP handlers
views/       layouts, pages and partials (plain PHP templates)
tests/       end-to-end suite
docs/        documentation
```

## License

MIT

## Contact

For questions or help, email [csjames0303@gmail.com](mailto:csjames0303@gmail.com).
