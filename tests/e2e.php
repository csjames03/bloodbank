<?php

declare(strict_types=1);

/*
 * End-to-end test: drives the running app over HTTP like a browser would
 * (cookies, CSRF tokens, redirects) and checks the database after each step.
 *
 *   php -S 127.0.0.1:8000 -t public public/index.php   (in another terminal)
 *   php tests/e2e.php [base-url]
 *
 * The suite resets the database with demo data before and after running.
 */

require dirname(__DIR__) . '/src/autoload.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\Env;

const ROOT = __DIR__ . '/..';

Env::load(ROOT . '/.env');
Config::set(require ROOT . '/config/config.php');
// Same clock as the app, so local times typed into forms mean the same thing.
date_default_timezone_set((string) Config::get('app.timezone', 'UTC'));

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8000', '/');

/* ------------------------------------------------------------------------- */

final class Browser
{
    /** @var array<string, string> */
    private array $cookies = [];
    public string $body = '';
    public int $status = 0;
    public string $url = '';

    public function __construct(private readonly string $base)
    {
    }

    public function get(string $path): self
    {
        return $this->request('GET', $path);
    }

    /** POST with the CSRF token taken from the last page. */
    public function post(string $path, array $data = [], bool $withToken = true): self
    {
        if ($withToken) {
            $data['_token'] = $this->token();
        }

        return $this->request('POST', $path, $data);
    }

    public function token(): string
    {
        preg_match('/name="_token" value="([a-f0-9]+)"/', $this->body, $m);

        return $m[1] ?? '';
    }

    /** Text of the toast shown on the current page, if any. */
    public function toast(): string
    {
        preg_match('/<div class="toast-msg">(.*?)<\/div>/s', $this->body, $m);

        return html_entity_decode(trim($m[1] ?? ''), ENT_QUOTES);
    }

    private function request(string $method, string $path, array $data = [], int $hops = 0): self
    {
        $url = str_starts_with($path, 'http') ? $path : $this->base . $path;
        $headers = ['Referer: ' . ($this->url ?: $this->base . '/')];
        if ($this->cookies) {
            $headers[] = 'Cookie: ' . implode('; ', array_map(
                static fn ($k, $v) => "$k=$v",
                array_keys($this->cookies),
                $this->cookies,
            ));
        }
        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }

        $context = stream_context_create(['http' => [
            'method'          => $method,
            'header'          => implode("\r\n", $headers),
            'content'         => $method === 'POST' ? http_build_query($data) : '',
            'follow_location' => 0,
            'ignore_errors'   => true,
        ]]);

        $body = @file_get_contents($url, false, $context);
        $responseHeaders = $http_response_header ?? [];
        preg_match('/\s(\d{3})\s/', $responseHeaders[0] ?? '', $m);
        $status = (int) ($m[1] ?? 0);

        $location = null;
        foreach ($responseHeaders as $h) {
            if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]*)/i', $h, $c)) {
                $this->cookies[$c[1]] = $c[2];
            }
            if (preg_match('/^Location:\s*(.+)$/i', $h, $l)) {
                $location = trim($l[1]);
            }
        }

        if ($location !== null && $status >= 300 && $status < 400 && $hops < 5) {
            $this->url = $url;
            $next = str_starts_with($location, 'http') ? $location : $this->base . $location;

            return $this->request('GET', $next, [], $hops + 1);
        }

        $this->status = $status;
        $this->body = (string) $body;
        $this->url = $url;

        return $this;
    }
}

/* ------------------------------------------------------------------------- */

$passed = 0;
$failed = 0;

function check(string $label, bool $ok, string $detail = ''): void
{
    global $passed, $failed;
    $ok ? $passed++ : $failed++;
    echo ($ok ? "  \033[32m✓\033[0m " : "  \033[31m✗\033[0m ") . $label . ($ok || $detail === '' ? '' : "  → {$detail}") . PHP_EOL;
}

function section(string $title): void
{
    echo PHP_EOL . "\033[1m{$title}\033[0m" . PHP_EOL;
}

function db(): Database
{
    return Database::instance();
}

/** Usable stock: available, unexpired units of a group. */
function stock(string $code): int
{
    return (int) db()->value(
        "SELECT COUNT(*) FROM blood_units u JOIN blood_types bt ON bt.id = u.blood_type_id
         WHERE bt.code = ? AND u.status = 'available' AND u.expires_at > now()",
        [$code],
    );
}

function quarantined(string $code): int
{
    return (int) db()->value(
        "SELECT COUNT(*) FROM blood_units u JOIN blood_types bt ON bt.id = u.blood_type_id WHERE bt.code = ? AND u.status = 'quarantine'",
        [$code],
    );
}

/**
 * Test helper: temporarily limit a group's usable stock to `$keep` units by
 * parking the rest (the ones expiring last). restoreStock() puts them back.
 * Real operations only ever touch the visible units, so integrity holds.
 */
function limitStock(string $code, int $keep): void
{
    db()->run(
        "UPDATE blood_units SET status = 'quarantine', discard_reason = '__parked__'
         WHERE id IN (SELECT u.id FROM blood_units u JOIN blood_types bt ON bt.id = u.blood_type_id
                      WHERE bt.code = ? AND u.status = 'available' AND u.expires_at > now()
                      ORDER BY u.expires_at, u.id OFFSET ?)",
        [$code, $keep],
    );
}

function restoreStock(): void
{
    db()->run("UPDATE blood_units SET status = 'available', discard_reason = NULL WHERE discard_reason = '__parked__'");
}

/** Pass every donation still in quarantine (as the lab would), so its units become stock. */
function screenAll(Browser $staff): void
{
    foreach (db()->all("SELECT DISTINCT dn.id, bt.code FROM blood_units u JOIN donations dn ON dn.id = u.donation_id
                        JOIN blood_types bt ON bt.id = dn.blood_type_id
                        WHERE u.status = 'quarantine' AND u.discard_reason IS DISTINCT FROM '__parked__'") as $d) {
        $staff->post("/screening/{$d['id']}/pass", ['confirmed_group' => $d['code']]);
    }
}

function noErrors(Browser $b): bool
{
    return $b->status === 200 && !preg_match('/Fatal error|Warning:|Notice:|Deprecated:|Uncaught/', $b->body);
}

function resetDb(): void
{
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(ROOT . '/database/setup.php'), $out, $code);
    if ($code !== 0) {
        fwrite(STDERR, "Database reset failed\n" . implode("\n", $out));
        exit(1);
    }
}

resetDb();

/* ------------------------------------------------------------------------- */
section('Guest access');

$guest = new Browser($base);
$guest->get('/');
check('Dashboard redirects guests to hospital login', str_ends_with($guest->url, '/login') && noErrors($guest));
$guest->get('/stocks');
check('Admin pages redirect guests to staff login', str_ends_with($guest->url, '/admin/login'));
$guest->get('/does-not-exist');
check('Unknown URL returns a 404 page', $guest->status === 404 && str_contains($guest->body, 'find that page'));

/* ------------------------------------------------------------------------- */
section('Hospital portal');

$h = new Browser($base);
$h->get('/login');
$h->post('/login', ['username' => 'riverside', 'password' => 'wrong']);
check('Wrong password is rejected with a generic message', str_contains($h->body, 'Incorrect username or password'));
check('Username is kept after a failed login', str_contains($h->body, 'value="riverside"'));

$h->post('/login', ['username' => 'RIVERSIDE', 'password' => 'hospital123']);
check('Hospital signs in (username is case-insensitive)', noErrors($h) && str_contains($h->body, 'Riverside General Hospital'));
check('Hospital dashboard shows availability and deliveries', str_contains($h->body, 'Available now') && str_contains($h->body, 'Your deliveries'));

$h->get('/stocks');
check('Hospital cannot open admin stock page (403)', $h->status === 403);
$h->get('/donations');
check('Hospital cannot open donation records (403)', $h->status === 403);

$h->get('/');
$aPosStart = stock('A+');
$h->post('/requests', ['doctor_name' => 'Jane Cruz', 'blood_type' => 'A+', 'bags' => '2']);
$newId = (int) db()->value('SELECT MAX(id) FROM blood_requests');
$row = db()->one('SELECT * FROM blood_requests WHERE id = ?', [$newId]);
check('Hospital submits a request', str_contains($h->toast(), 'submitted') && $row['status'] === 'pending');
check('“Dr.” prefix is added to the doctor name', $row['doctor_name'] === 'Dr. Jane Cruz');
check('Request is tied to the signed-in hospital', (int) $row['hospital_id'] === (int) db()->value("SELECT id FROM hospitals WHERE username='riverside'"));
check('Submitting a request does not touch stock', stock('A+') === $aPosStart);

check('Priority defaults to routine', $row['priority'] === 'routine' && $row['needed_by'] === null);

// Demand above stock is accepted — the blood bank decides how to fill it.
$abNegStart = stock('AB-');
$bigBags = $abNegStart + 2;
$neededBy = (new DateTimeImmutable('+3 hours'))->format('Y-m-d\TH:i');
$h->post('/requests', ['doctor_name' => 'Jane Cruz', 'blood_type' => 'AB-', 'bags' => (string) $bigBags, 'priority' => 'emergency', 'needed_by' => $neededBy]);
$bigId = (int) db()->value('SELECT MAX(id) FROM blood_requests');
$big = db()->one('SELECT * FROM blood_requests WHERE id = ?', [$bigId]);
check('Request larger than stock is accepted', $bigId > $newId && $big['status'] === 'pending' && stock('AB-') === $abNegStart, $h->toast());
check('…with a heads-up that it may be substituted or partly filled', str_contains($h->toast(), "Only {$abNegStart} bags of AB-") && str_contains($h->toast(), 'compatible group'), $h->toast());
check('Priority and “needed by” are stored', $big['priority'] === 'emergency' && substr((new DateTimeImmutable($big['needed_by']))->format('Y-m-d\TH:i'), 0, 16) === $neededBy);

$h->get('/');
$h->post('/requests', ['doctor_name' => 'Late', 'blood_type' => 'A+', 'bags' => '1', 'needed_by' => (new DateTimeImmutable('-2 hours'))->format('Y-m-d\TH:i')]);
check('A “needed by” time in the past is rejected', (int) db()->value('SELECT MAX(id) FROM blood_requests') === $bigId && str_contains($h->toast(), 'in the past'));
$h->post('/requests', ['doctor_name' => 'Odd', 'blood_type' => 'A+', 'bags' => '1', 'priority' => 'whenever']);
check('An unknown priority is rejected', (int) db()->value('SELECT MAX(id) FROM blood_requests') === $bigId);

$h->post('/requests', ['doctor_name' => '', 'blood_type' => 'ZZ', 'bags' => '99']);
check('Invalid request input is rejected', (int) db()->value('SELECT MAX(id) FROM blood_requests') === $bigId);
check('Validation re-opens the form with errors', str_contains($h->body, 'data-reopen-dialog="request-create"') && str_contains($h->body, 'is required'));

$h->get('/requests');
check('“My requests” lists only this hospital', noErrors($h) && !str_contains($h->body, 'Dr. Benjamin Co') && str_contains($h->body, 'Dr. Jane Cruz'));

$h->post("/requests/{$newId}/cancel");
check('Hospital cancels its own pending request', db()->value('SELECT status FROM blood_requests WHERE id = ?', [$newId]) === 'cancelled');

$otherId = (int) db()->value("SELECT r.id FROM blood_requests r JOIN hospitals h ON h.id = r.hospital_id WHERE h.username = 'northpoint' AND r.status = 'pending' ORDER BY r.id LIMIT 1");
$h->post("/requests/{$otherId}/cancel");
check('Hospital cannot cancel another hospital’s request', db()->value('SELECT status FROM blood_requests WHERE id = ?', [$otherId]) === 'pending');

$transitId = (int) db()->value("SELECT r.id FROM blood_requests r JOIN hospitals h ON h.id = r.hospital_id WHERE h.username = 'riverside' AND r.status = 'in_transit' LIMIT 1");
$h->post("/requests/{$transitId}/cancel");
check('Hospital cannot cancel a dispatched request', db()->value('SELECT status FROM blood_requests WHERE id = ?', [$transitId]) === 'in_transit');

$h->post('/requests/' . $otherId . '/approve');
check('Hospital cannot approve requests (403)', $h->status === 403 && db()->value('SELECT status FROM blood_requests WHERE id = ?', [$otherId]) === 'pending');

$before = (int) db()->value('SELECT COUNT(*) FROM blood_requests');
$h->get('/');
$h->post('/requests', ['doctor_name' => 'X', 'blood_type' => 'A+', 'bags' => '1'], withToken: false);
check('POST without CSRF token is rejected', (int) db()->value('SELECT COUNT(*) FROM blood_requests') === $before && str_contains($h->toast(), 'session expired'));

$h->get('/');
$oNegStart = stock('O-');
$oNegQuarantine = quarantined('O-');
$h->post('/donations', [
    'first_name' => 'Walk', 'middle_name' => '', 'last_name' => 'In', 'sex' => 'Male', 'age' => '30',
    'contact' => '0917 000 0000', 'address' => 'Angeles City', 'blood_type' => 'O-', 'bags' => '1', 'medical_status' => '',
]);
check('Hospital logs a walk-in donation', str_contains($h->toast(), 'Donation recorded') && str_contains($h->toast(), 'quarantine'), $h->toast());
check('…its unit is quarantined, not stock, until screening', stock('O-') === $oNegStart && quarantined('O-') === $oNegQuarantine + 1);
check('Walk-in donation records the collecting hospital', db()->value("SELECT h.username FROM donations d JOIN hospitals h ON h.id = d.hospital_id ORDER BY d.id DESC LIMIT 1") === 'riverside');

$h->get('/requests');
check('Request cards show priority and “needed by”', str_contains($h->body, 'badge badge-danger">Emergency') && str_contains($h->body, 'Needed by'));

$yapId = (int) db()->value("SELECT id FROM blood_requests WHERE doctor_name = 'Dr. Celine Yap'");
$h->post("/requests/{$yapId}/receive");
check('Hospital cannot confirm another hospital’s delivery', db()->value('SELECT status FROM blood_requests WHERE id = ?', [$yapId]) === 'in_transit' && str_contains($h->toast(), 'not found'));
$h->post("/requests/{$bigId}/receive");
check('A pending request cannot be marked received', db()->value('SELECT status FROM blood_requests WHERE id = ?', [$bigId]) === 'pending');

$h->post('/logout');
check('Hospital signs out', str_ends_with($h->url, '/login') && str_contains($h->toast(), 'signed out'));
$h->get('/');
check('Session is gone after sign-out', str_ends_with($h->url, '/login'));

/* ------------------------------------------------------------------------- */
section('Admin — requests');

$a = new Browser($base);
$a->get('/admin/login');
$a->post('/admin/login', ['username' => 'riverside', 'password' => 'hospital123']);
check('Hospital credentials do not work on the staff portal', str_contains($a->body, 'Incorrect username or password'));
$a->post('/admin/login', ['username' => 'admin', 'password' => 'admin123']);
check('Admin signs in', noErrors($a) && str_contains($a->body, 'Stock by blood group'));

foreach (['/', '/requests', '/requests?status=pending', '/requests?type=A%2B&q=co', '/donations', '/donations?q=maria', '/donations?type=O-', '/donations?page=2', '/stocks',
          '/screening', '/units', '/units?status=expiring', '/units?status=dispatched&type=O%2B', '/units?q=LL0000', '/activity'] as $page) {
    $a->get($page);
    check("Admin page {$page} renders cleanly", noErrors($a), "HTTP {$a->status}");
}

$a->get('/donations?q=maria');
check('Donor search finds “Maria Santos Reyes”', str_contains($a->body, 'Maria Santos Reyes') && !str_contains($a->body, 'Jose Dela Cruz'));
$a->get('/requests?q=northpoint');
check('Request search matches hospital name', str_contains($a->body, 'Dr. Benjamin Co') && !str_contains($a->body, 'Dr. Celine Yap'));

$a->get('/requests');
$bNegPendingId = (int) db()->value("SELECT r.id FROM blood_requests r JOIN blood_types bt ON bt.id = r.blood_type_id WHERE bt.code = 'B-' AND r.status = 'pending'");
$pos = static fn (int $id) => strpos($a->body, 'id="request-' . $id . '"');
check(
    'Queue is triaged: emergency, then urgent, then routine',
    $pos($bigId) !== false && $pos($bigId) < $pos($bNegPendingId) && $pos($bNegPendingId) < $pos($otherId),
);
check('Pending requests offer “Allocate & dispatch” with a suggestion', str_contains($a->body, 'data-allocate=') && str_contains($a->body, 'name="alloc[A+]" value="2"'));

$aPlus = stock('A+');
$fefo = array_map('intval', array_column(db()->all(
    "SELECT u.id FROM blood_units u JOIN blood_types bt ON bt.id = u.blood_type_id
     WHERE bt.code = 'A+' AND u.status = 'available' AND u.expires_at > now() ORDER BY u.expires_at, u.id LIMIT 2"
), 'id'));
$a->post("/requests/{$otherId}/approve");
$req = db()->one('SELECT * FROM blood_requests WHERE id = ?', [$otherId]);
check('Approving dispatches the request', $req['status'] === 'in_transit' && $req['approved_at'] !== null);
check('Approval deducts stock (A+ ' . $aPlus . ' → ' . ($aPlus - (int) $req['bags']) . ')', stock('A+') === $aPlus - (int) $req['bags']);

$a->post("/requests/{$otherId}/approve");
check('A request cannot be approved twice', stock('A+') === $aPlus - (int) $req['bags'] && str_contains($a->toast(), 'already in transit'));

check('Approval records what was sent', db()->value('SELECT bags FROM request_allocations WHERE request_id = ?', [$otherId]) == $req['bags']);
$issued = array_map('intval', array_column(db()->all('SELECT id FROM blood_units WHERE request_id = ? ORDER BY expires_at, id', [$otherId]), 'id'));
check('Dispatch issues the soonest-expiring units first (FEFO)', $issued === $fefo, json_encode([$issued, $fefo]));

$a->post("/requests/{$otherId}/receive");
check('Staff can record receipt on the hospital’s behalf', db()->value('SELECT status FROM blood_requests WHERE id = ?', [$otherId]) === 'completed');
check(
    '…and the audit log says it was recorded by staff',
    str_contains((string) db()->value("SELECT note FROM activity_log WHERE subject = 'request' AND subject_id = ? AND action = 'received'", [$otherId]), 'on behalf'),
);

$a->post("/requests/{$otherId}/cancel");
check('Completed requests cannot be cancelled', db()->value('SELECT status FROM blood_requests WHERE id = ?', [$otherId]) === 'completed');

$bPlus = stock('B+');
$transitBags = (int) db()->value('SELECT bags FROM blood_requests WHERE id = ?', [$transitId]);
$a->post("/requests/{$transitId}/cancel");
check('Cancelling an in-transit request returns its bags to stock', stock('B+') === $bPlus + $transitBags, $a->toast());
check('…and the recalled units are no longer linked to it', (int) db()->value('SELECT COUNT(*) FROM blood_units WHERE request_id = ?', [$transitId]) === 0);

// Allocation: what staff send is validated against compatibility, stock and the amount requested.
$snapshot = static fn () => [stock('AB-'), stock('A-'), stock('O-'), stock('A+')];
$before = $snapshot();
$a->post("/requests/{$bigId}/approve", ['alloc' => ['AB-' => '1', 'A+' => '1']]);
check('Incompatible groups cannot be allocated (A+ to an AB- patient)', $snapshot() === $before && str_contains($a->toast(), 'not compatible'), $a->toast());
$a->post("/requests/{$bigId}/approve", ['alloc' => ['AB-' => (string) ($bigBags - 1), 'O-' => '2']]);
check('Cannot send more bags than requested', $snapshot() === $before && str_contains($a->toast(), "only {$bigBags} were requested"), $a->toast());
$a->post("/requests/{$bigId}/approve", ['alloc' => ['AB-' => '-1']]);
check('Negative allocations are rejected', $snapshot() === $before && db()->value('SELECT status FROM blood_requests WHERE id = ?', [$bigId]) === 'pending');
$a->post("/requests/{$bigId}/approve", ['alloc' => ['AB-' => (string) ($before[0] + 1)]]);
check('Cannot allocate more units than are in stock', $snapshot() === $before && str_contains($a->toast(), 'Not enough AB-'), $a->toast());

[$abNeg0, $aNeg0, $oNeg0] = $before;
$a->post("/requests/{$bigId}/approve", ['alloc' => ['AB-' => '2', 'A-' => '1', 'O-' => '1']]);
check(
    'Staff can fill a request with compatible substitutes',
    db()->value('SELECT status FROM blood_requests WHERE id = ?', [$bigId]) === 'in_transit'
        && stock('AB-') === $abNeg0 - 2 && stock('A-') === $aNeg0 - 1 && stock('O-') === $oNeg0 - 1,
    $a->toast(),
);
check('…and the split is stored per group', (int) db()->value('SELECT COUNT(*) FROM request_allocations WHERE request_id = ?', [$bigId]) === 3);
$a->get('/requests?status=in_transit');
check('Card shows what was sent and who dispatched it', str_contains($a->body, 'compatible substitute') && str_contains($a->body, 'by Administrator'));
check('Card lists the issued unit codes for traceability', str_contains($a->body, 'class="unit-trace"') && (bool) preg_match('/LL\d{6}/', $a->body));

$a->post("/requests/{$bigId}/cancel");
check(
    'Recalling a substituted dispatch returns every bag to its own group',
    stock('AB-') === $abNeg0 && stock('A-') === $aNeg0 && stock('O-') === $oNeg0,
);

// Partial fills.
$riverside = (int) db()->value("SELECT id FROM hospitals WHERE username = 'riverside'");
$a->get('/requests');
$a->post('/requests', ['hospital_id' => (string) $riverside, 'doctor_name' => 'Partial', 'blood_type' => 'O+', 'bags' => '3', 'priority' => 'urgent']);
$partialId = (int) db()->value('SELECT MAX(id) FROM blood_requests');
$oPos0 = stock('O+');
$a->post("/requests/{$partialId}/approve", ['alloc' => ['O+' => '2']]);
check('A partial fill dispatches fewer bags than requested', db()->value('SELECT status FROM blood_requests WHERE id = ?', [$partialId]) === 'in_transit' && stock('O+') === $oPos0 - 2);
check(
    '…and the hospital is told it is partial',
    str_contains((string) db()->value("SELECT body FROM notifications WHERE type = 'request_dispatched' AND request_id = ?", [$partialId]), 'Partial fill: 2 of 3'),
);

// With no allocation given (no JavaScript), the suggested split is used.
$abPending = (int) db()->value("SELECT r.id FROM blood_requests r JOIN blood_types bt ON bt.id = r.blood_type_id WHERE bt.code = 'AB+' AND r.status = 'pending'");
limitStock('AB+', 0);
$a->post("/requests/{$abPending}/approve", ['alloc' => ['AB+' => '1']]);
check('Approval is refused when the chosen group has run out', db()->value('SELECT status FROM blood_requests WHERE id = ?', [$abPending]) === 'pending' && stock('AB+') === 0, $a->toast());
$a->post("/requests/{$abPending}/approve");
check(
    'Default allocation substitutes a compatible group when the patient’s group is out',
    db()->value('SELECT status FROM blood_requests WHERE id = ?', [$abPending]) === 'in_transit'
        && db()->value('SELECT bt.code FROM request_allocations a JOIN blood_types bt ON bt.id = a.blood_type_id WHERE a.request_id = ?', [$abPending]) !== 'AB+',
    $a->toast(),
);
check('…but never reaches for O- while other groups have stock', db()->value('SELECT bt.code FROM request_allocations a JOIN blood_types bt ON bt.id = a.blood_type_id WHERE a.request_id = ?', [$abPending]) !== 'O-');
restoreStock();

$a->get('/requests');
$a->post('/requests', ['hospital_id' => '999', 'doctor_name' => 'Who', 'blood_type' => 'O+', 'bags' => '1']);
check('Admin request with unknown hospital is rejected', str_contains($a->toast(), 'valid hospital'));
$staclara = (int) db()->value("SELECT id FROM hospitals WHERE username = 'staclara'");
$a->post('/requests', ['hospital_id' => (string) $staclara, 'doctor_name' => 'Dr. Lee', 'blood_type' => 'O+', 'bags' => '1']);
check('Admin files a request on behalf of a hospital', (int) db()->value('SELECT hospital_id FROM blood_requests ORDER BY id DESC LIMIT 1') === $staclara);
check('…without doubling the “Dr.” prefix', db()->value('SELECT doctor_name FROM blood_requests ORDER BY id DESC LIMIT 1') === 'Dr. Lee');

/* ------------------------------------------------------------------------- */
section('Admin — donations');

$a->get('/donations');
$oNeg = stock('O-');
$oNegQ = quarantined('O-');
$donor = [
    'first_name' => 'Test', 'middle_name' => 'Q', 'last_name' => 'Donor', 'sex' => 'Female', 'age' => '29',
    'contact' => '09170001111', 'address' => 'Clark', 'blood_type' => 'O-', 'bags' => '1', 'medical_status' => 'Fit',
];
$a->post('/donations', $donor);
$donationId = (int) db()->value('SELECT MAX(id) FROM donations');
check('Recording a donation creates one quarantined unit per bag', quarantined('O-') === $oNegQ + 1 && stock('O-') === $oNeg);
$unit = db()->one('SELECT * FROM blood_units WHERE donation_id = ?', [$donationId]);
check(
    'Each unit gets a code and an expiry date one shelf life after collection',
    (bool) preg_match('/^LL\d{6}$/', $unit['unit_code'])
        && (new DateTimeImmutable($unit['expires_at']))->getTimestamp() - (new DateTimeImmutable($unit['collected_at']))->getTimestamp() === 35 * 86400,
);

$a->post('/donations', [...$donor, 'age' => '12', 'contact' => 'abc']);
check('Underage donor / bad contact is rejected', (int) db()->value('SELECT MAX(id) FROM donations') === $donationId && str_contains($a->body, 'between 16 and 65'));
check('…and the form keeps what was typed', str_contains($a->body, 'value="Test"'));

$a->get('/donations');
$a->post("/donations/{$donationId}", [...$donor, '_id' => $donationId, 'bags' => '2', 'first_name' => 'Tess']);
check('Before screening, bags can be corrected (units follow)', (int) db()->value('SELECT COUNT(*) FROM blood_units WHERE donation_id = ?', [$donationId]) === 2 && quarantined('O-') === $oNegQ + 2);
check('Editing updates donor details', db()->value('SELECT d.first_name FROM donors d JOIN donations dn ON dn.donor_id = d.id WHERE dn.id = ?', [$donationId]) === 'Tess');

$abNeg = stock('AB-');
$abNegQ = quarantined('AB-');
$a->post("/donations/{$donationId}", [...$donor, '_id' => $donationId, 'bags' => '2', 'blood_type' => 'AB-']);
check('Before screening, the blood type can be corrected', quarantined('O-') === $oNegQ && quarantined('AB-') === $abNegQ + 2 && stock('AB-') === $abNeg);

/* ------------------------------------------------------------------------- */
section('Screening');

$a->get('/screening');
check('Screening queue lists the quarantined donation with its unit codes', noErrors($a) && str_contains($a->body, 'id="donation-' . $donationId . '"') && str_contains($a->body, 'Test Q Donor'));

$a->post("/screening/{$donationId}/pass", ['confirmed_group' => 'AB-']);
check('Passing screening releases the units to stock', stock('AB-') === $abNeg + 2 && quarantined('AB-') === $abNegQ, $a->toast());
check('…and records when they were screened', (int) db()->value('SELECT COUNT(*) FROM blood_units WHERE donation_id = ? AND screened_at IS NOT NULL', [$donationId]) === 2);
$a->post("/screening/{$donationId}/pass", ['confirmed_group' => 'AB-']);
check('A donation cannot be screened twice', stock('AB-') === $abNeg + 2 && str_contains($a->toast(), 'already been screened'));

$a->get('/donations');
$a->post("/donations/{$donationId}", [...$donor, '_id' => $donationId, 'bags' => '1', 'blood_type' => 'AB-', 'first_name' => 'Tess']);
check('After screening, bags can no longer be edited', (int) db()->value('SELECT bags FROM donations WHERE id = ?', [$donationId]) === 2 && stock('AB-') === $abNeg + 2 && str_contains($a->toast(), 'already been screened'), $a->toast());
$a->post("/donations/{$donationId}", [...$donor, '_id' => $donationId, 'bags' => '2', 'blood_type' => 'AB-', 'address' => 'Clark Freeport Zone', 'first_name' => 'Tess']);
check('…but donor details still can', db()->value('SELECT d.address FROM donors d JOIN donations dn ON dn.donor_id = d.id WHERE dn.id = ?', [$donationId]) === 'Clark Freeport Zone');
$a->post("/donations/{$donationId}/delete");
check('A screened donation cannot be deleted', (int) db()->value('SELECT COUNT(*) FROM donations WHERE id = ?', [$donationId]) === 1 && str_contains($a->toast(), 'discard the unit'), $a->toast());

// Re-grouping at screening.
$a->post('/donations', [...$donor, 'first_name' => 'Regroup', 'contact' => '09170002222', 'blood_type' => 'A+', 'bags' => '1']);
$regroupId = (int) db()->value('SELECT MAX(id) FROM donations');
$bPos = stock('B+');
$a->post("/screening/{$regroupId}/pass", ['confirmed_group' => 'B+']);
check(
    'The lab can correct the blood group at screening (donation, units and donor re-grouped)',
    stock('B+') === $bPos + 1
        && db()->value('SELECT bt.code FROM donations dn JOIN blood_types bt ON bt.id = dn.blood_type_id WHERE dn.id = ?', [$regroupId]) === 'B+'
        && db()->value('SELECT bt.code FROM donors d JOIN donations dn ON dn.donor_id = d.id JOIN blood_types bt ON bt.id = d.blood_type_id WHERE dn.id = ?', [$regroupId]) === 'B+',
    $a->toast(),
);

// Failing screening.
$a->post('/donations', [...$donor, 'first_name' => 'Reactive', 'contact' => '09170003333', 'blood_type' => 'O+', 'bags' => '2']);
$failId = (int) db()->value('SELECT MAX(id) FROM donations');
$oPosBefore = stock('O+');
$a->post("/screening/{$failId}/fail", ['reason' => 'hcv']);
check('Failing screening discards every unit with the reason', (int) db()->value("SELECT COUNT(*) FROM blood_units WHERE donation_id = ? AND status = 'discarded' AND discard_reason LIKE 'Failed screening: Hepatitis C%'", [$failId]) === 2 && stock('O+') === $oPosBefore);
check('…and permanently defers the donor for hepatitis C', db()->value('SELECT d.deferred_permanently FROM donors d JOIN donations dn ON dn.donor_id = d.id WHERE dn.id = ?', [$failId]) === true);
db()->run("UPDATE donations SET donated_at = donated_at - INTERVAL '120 days' WHERE id = ?", [$failId]);
$a->post('/donations', [...$donor, 'first_name' => 'Reactive', 'contact' => '0917 000 3333', 'blood_type' => 'O+', 'bags' => '1']);
check('A permanently deferred donor is refused', (int) db()->value('SELECT MAX(id) FROM donations') === $failId && str_contains($a->toast(), 'permanently deferred'), $a->toast());
$a->post("/screening/{$failId}/fail", ['reason' => 'nonsense']);
check('An unknown failure reason is rejected', str_contains($a->toast(), 'Choose why'));

// Deleting before screening.
$a->post('/donations', [...$donor, 'first_name' => 'Oops', 'contact' => '09170004444', 'blood_type' => 'A-', 'bags' => '1']);
$oopsId = (int) db()->value('SELECT MAX(id) FROM donations');
$a->post("/donations/{$oopsId}/delete");
check('A donation still in quarantine can be deleted, with its units', (int) db()->value('SELECT COUNT(*) FROM donations WHERE id = ?', [$oopsId]) === 0
    && (int) db()->value('SELECT COUNT(*) FROM blood_units WHERE donation_id = ?', [$oopsId]) === 0);
check('Donor without donations is cleaned up', (int) db()->value("SELECT COUNT(*) FROM donors WHERE first_name = 'Oops'") === 0);

$a->post('/donations/999999', [...$donor, '_id' => 999999]);
check('Editing a missing donation shows an error', str_contains($a->toast(), 'not found'), $a->toast());

$h2 = new Browser($base);
$h2->get('/login');
$h2->post('/login', ['username' => 'riverside', 'password' => 'hospital123']);
$h2->get('/screening');
check('Hospitals cannot open the screening queue (403)', $h2->status === 403);
$h2->get('/units');
check('Hospitals cannot open the unit list (403)', $h2->status === 403);

/* ------------------------------------------------------------------------- */
section('Units, discards & expiry');

$victim = db()->one("SELECT u.id, bt.code FROM blood_units u JOIN blood_types bt ON bt.id = u.blood_type_id
                     WHERE u.status = 'available' AND u.expires_at > now() AND bt.code = 'A+' ORDER BY u.id LIMIT 1");
$aPosBefore = stock('A+');
$a->get('/units');
$a->post("/units/{$victim['id']}/discard", ['reason' => 'cold_chain', 'note' => 'Fridge 2 alarm']);
check('Staff can discard a unit with a reason', db()->value('SELECT status FROM blood_units WHERE id = ?', [$victim['id']]) === 'discarded' && stock('A+') === $aPosBefore - 1, $a->toast());
check('…the reason and note are kept', str_contains((string) db()->value('SELECT discard_reason FROM blood_units WHERE id = ?', [$victim['id']]), 'Cold-chain breach (temperature) — Fridge 2 alarm'));
check('…and the discard is in the audit log', (int) db()->value("SELECT COUNT(*) FROM activity_log WHERE subject = 'unit' AND subject_id = ? AND action = 'discarded'", [$victim['id']]) === 1);
$a->post("/units/{$victim['id']}/discard", ['reason' => 'damaged']);
check('A discarded unit cannot be discarded again', str_contains($a->toast(), 'cannot be discarded'));
$dispatched = (int) db()->value("SELECT id FROM blood_units WHERE status = 'dispatched' LIMIT 1");
$a->post("/units/{$dispatched}/discard", ['reason' => 'damaged']);
check('A dispatched unit cannot be discarded', db()->value('SELECT status FROM blood_units WHERE id = ?', [$dispatched]) === 'dispatched');
$a->post("/units/{$victim['id']}/discard", ['reason' => '']);
check('A discard needs a reason', str_contains($a->toast(), 'Choose why'));

// Expiry: make two available units go out of date, then load any page.
$expiring = array_map('intval', array_column(db()->all("SELECT u.id FROM blood_units u JOIN blood_types bt ON bt.id = u.blood_type_id
    WHERE u.status = 'available' AND u.expires_at > now() AND bt.code = 'O+' ORDER BY u.expires_at LIMIT 2"), 'id'));
$oPosBefore = stock('O+');
db()->run("UPDATE blood_units SET expires_at = now() - INTERVAL '1 minute', collected_at = now() - INTERVAL '36 days' WHERE id = ANY (?::int[])", ['{' . implode(',', $expiring) . '}']);
check('An out-of-date unit never counts as stock, even before the sweep', stock('O+') === $oPosBefore - 2);
$a->get('/');
check('The sweep marks out-of-date units as expired', (int) db()->value("SELECT COUNT(*) FROM blood_units WHERE id = ANY (?::int[]) AND status = 'expired'", ['{' . implode(',', $expiring) . '}']) === 2);
check('…and alerts staff', (int) db()->value("SELECT COUNT(*) FROM notifications WHERE type = 'units_expired' AND title = '2 units expired'") >= 1);
check('Expired units are logged by the system', (int) db()->value("SELECT COUNT(*) FROM activity_log WHERE subject = 'unit' AND action = 'expired' AND actor_role = 'system'") >= 1);
$a->get('/');
check('The sweep handles each unit once (no duplicate alerts)', (int) db()->value("SELECT COUNT(*) FROM notifications WHERE type = 'units_expired' AND title = '2 units expired'") === 1);

$warnedBefore = (int) db()->value("SELECT COUNT(*) FROM notifications WHERE type = 'units_expiring'");
$soon = (int) db()->value("SELECT u.id FROM blood_units u JOIN blood_types bt ON bt.id = u.blood_type_id
    WHERE u.status = 'available' AND u.expiry_warned_at IS NULL AND u.expires_at > now() + INTERVAL '8 days' AND bt.code = 'A-' LIMIT 1");
db()->run("UPDATE blood_units SET expires_at = now() + INTERVAL '3 days', collected_at = now() - INTERVAL '32 days' WHERE id = ?", [$soon]);
$a->get('/units?status=expiring');
check('Units entering the 7-day window raise an “expiring” alert', (int) db()->value("SELECT COUNT(*) FROM notifications WHERE type = 'units_expiring'") === $warnedBefore + 1
    && db()->value('SELECT expiry_warned_at FROM blood_units WHERE id = ?', [$soon]) !== null);
check('The expiring filter lists it', str_contains($a->body, (string) db()->value('SELECT unit_code FROM blood_units WHERE id = ?', [$soon])));
$a->get('/stocks');
check('Stock page shows expiring, quarantine and waste figures', noErrors($a) && str_contains($a->body, 'Expiring ≤ 7 days') && str_contains($a->body, 'In quarantine') && str_contains($a->body, 'Wasted (30 days)'));
$a->get('/');
check('Dashboard lists units expiring soon', str_contains($a->body, 'Expiring within 7 days'));

/* ------------------------------------------------------------------------- */
section('Returning donors');

$maria = db()->one("SELECT id, (SELECT COUNT(*) FROM donations WHERE donor_id = donors.id) AS n FROM donors WHERE first_name = 'Maria' AND last_name = 'Reyes'");
$donorCount = (int) db()->value('SELECT COUNT(*) FROM donors');
$donationCount = (int) db()->value('SELECT COUNT(*) FROM donations');
$mariaForm = [
    'first_name' => '  maria ', 'middle_name' => 'Santos', 'last_name' => 'REYES', 'sex' => 'Female', 'age' => '28',
    'contact' => '0917-123-4501', 'address' => 'Angeles City, Pampanga', 'blood_type' => 'A+', 'bags' => '1', 'medical_status' => 'Fit to donate',
];

$a->get('/donations');
$a->post('/donations', [...$mariaForm, 'blood_type' => 'O+']);
check('A returning donor must keep the blood group on record', (int) db()->value('SELECT COUNT(*) FROM donations') === $donationCount && str_contains($a->toast(), 'on record as A+'), $a->toast());

$a->post('/donations', $mariaForm);
check(
    'A returning donor is matched despite case, spacing and phone formatting…',
    str_contains($a->toast(), 'Maria Reyes last donated'),
    $a->toast(),
);
check('…and cannot donate again before the interval is up', (int) db()->value('SELECT COUNT(*) FROM donations') === $donationCount && str_contains($a->toast(), 'can give again from'));

db()->run("UPDATE donations SET donated_at = donated_at - INTERVAL '60 days' WHERE donor_id = ?", [$maria['id']]);
$aPosQ = quarantined('A+');
$a->post('/donations', $mariaForm);
check('Once eligible, the donation is added to the existing donor', (int) db()->value('SELECT COUNT(*) FROM donors') === $donorCount
    && (int) db()->value('SELECT COUNT(*) FROM donations WHERE donor_id = ?', [$maria['id']]) === (int) $maria['n'] + 1);
check('…its unit goes to screening and the donor is welcomed back', quarantined('A+') === $aPosQ + 1 && str_contains($a->toast(), 'Welcome back, maria') && str_contains($a->toast(), 'donation #' . ((int) $maria['n'] + 1)), $a->toast());

/* ------------------------------------------------------------------------- */
section('Receipt & audit trail');

$sc = new Browser($base);
$sc->get('/login');
$sc->post('/login', ['username' => 'staclara', 'password' => 'hospital123']);
$sc->get('/requests');
check('Hospital sees “Confirm receipt” on deliveries in transit', str_contains($sc->body, "/requests/{$yapId}/receive") && str_contains($sc->body, 'Confirm receipt'));
$sc->post("/requests/{$yapId}/receive");
check('Hospital confirms receipt of its own delivery', db()->value('SELECT status FROM blood_requests WHERE id = ?', [$yapId]) === 'completed' && str_contains($sc->toast(), 'confirmed'));
check('Staff are told the delivery arrived', (int) db()->value("SELECT COUNT(*) FROM notifications WHERE type = 'request_received' AND request_id = ?", [$yapId]) >= 1);
check(
    'Receipt is attributed to the hospital in the audit log',
    db()->value("SELECT actor_name FROM activity_log WHERE subject = 'request' AND subject_id = ? AND action = 'received'", [$yapId]) === 'Sta. Clara Community Hospital',
);
$sc->get('/requests?status=completed');
check('Tracker shows who confirmed receipt', str_contains($sc->body, 'by Sta. Clara Community Hospital'));

$sc->get('/activity');
check('Hospitals cannot open the activity log (403)', $sc->status === 403);

$a->get('/activity');
check('Staff activity log renders every action with its actor', noErrors($a) && str_contains($a->body, 'Administrator') && str_contains($a->body, 'dispatched') && str_contains($a->body, 'returning donor'));
$a->get('/activity?subject=donation&actor=admin');
check('Activity log filters by type and actor', noErrors($a) && str_contains($a->body, 'recorded') && !str_contains($a->body, 'confirmed receipt of'));
check(
    'Every request action is logged with an actor',
    (int) db()->value("SELECT COUNT(*) FROM activity_log WHERE subject = 'request' AND action = 'approved' AND actor_name = 'Administrator'") >= 4,
);

/* ------------------------------------------------------------------------- */
section('Notifications');

/** Number of notifications of `$type` for a recipient, optionally about one request. */
function notified(string $role, int $recipient, string $type, ?int $requestId = null): int
{
    return (int) db()->value(
        'SELECT COUNT(*) FROM notifications WHERE recipient_role = ? AND recipient_id = ? AND type = ?'
        . ($requestId ? ' AND request_id = ?' : ''),
        array_filter([$role, $recipient, $type, $requestId], static fn ($v) => $v !== null),
    );
}

$adminId = (int) db()->value("SELECT id FROM admins WHERE username = 'admin'");
$riverside = (int) db()->value("SELECT id FROM hospitals WHERE username = 'riverside'");
$northpoint = (int) db()->value("SELECT id FROM hospitals WHERE username = 'northpoint'");
$leeId = (int) db()->value("SELECT id FROM blood_requests WHERE doctor_name = 'Dr. Lee'");

check('Staff are told about a new hospital request', notified('admin', $adminId, 'request_created', $newId) === 1);
check(
    'Emergency requests raise an emergency alert',
    notified('admin', $adminId, 'request_emergency', $bigId) === 1
        && str_starts_with((string) db()->value("SELECT title FROM notifications WHERE type = 'request_emergency' AND request_id = ?", [$bigId]), 'EMERGENCY'),
);
check('Staff are told when a hospital withdraws a request', notified('admin', $adminId, 'request_withdrawn', $newId) === 1);
check('Staff are told about walk-in donors', notified('admin', $adminId, 'donation_logged') === 1);
check('Hospital is told its request was dispatched', notified('hospital', $northpoint, 'request_dispatched', $otherId) === 1);
check('Hospital is told its delivery completed', notified('hospital', $northpoint, 'request_delivered', $otherId) === 1);
check(
    'Hospital is told staff cancelled its in-transit request',
    notified('hospital', $riverside, 'request_cancelled', $transitId) === 1
        && str_contains((string) db()->value("SELECT body FROM notifications WHERE type = 'request_cancelled' AND request_id = ?", [$transitId]), 'recalled'),
);
check(
    'Request filed by staff notifies the hospital, not staff',
    notified('hospital', $staclara, 'request_filed', $leeId) === 1 && notified('admin', $adminId, 'request_created', $leeId) === 0,
);
check(
    'Refused actions create no notifications',
    // Four refused allocation attempts on $bigId, then one success: exactly one notification.
    (int) db()->value("SELECT COUNT(*) FROM notifications WHERE type = 'request_dispatched' AND request_id = ?", [$bigId]) === 1
        && (int) db()->value("SELECT COUNT(*) FROM notifications WHERE type = 'request_dispatched' AND request_id = ?", [$otherId]) === 1
        && (int) db()->value("SELECT COUNT(*) FROM notifications WHERE type = 'request_cancelled' AND request_id = ?", [$otherId]) === 0,
);

// Crossing the low-stock threshold alerts staff once.
$bNegPending = (int) db()->value("SELECT r.id FROM blood_requests r JOIN blood_types bt ON bt.id = r.blood_type_id WHERE bt.code = 'B-' AND r.status = 'pending'");
$bNeg = stock('B-');
limitStock('B-', (int) config('inventory.low_stock_threshold') + 1);
$lowBefore = (int) db()->value("SELECT COUNT(*) FROM notifications WHERE type = 'stock_low' AND title LIKE 'B-%'");
$a->post("/requests/{$bNegPending}/approve");
check('Stock dropping to the low threshold alerts staff', (int) db()->value("SELECT COUNT(*) FROM notifications WHERE type = 'stock_low' AND title LIKE 'B-%'") === $lowBefore + 1);
restoreStock();

$oPos = stock('O+');
$oPosPending = (int) db()->value("SELECT id FROM blood_requests WHERE doctor_name = 'Dr. Lee'");
limitStock('O+', 1);
$a->post("/requests/{$oPosPending}/approve");
check('Stock running out alerts staff', (int) db()->value("SELECT COUNT(*) FROM notifications WHERE type = 'stock_out' AND title LIKE 'O+%'") === 1);
restoreStock();

// Inbox UI.
$a->get('/notifications');
check('Staff inbox renders', noErrors($a) && str_contains($a->body, 'New request from') && str_contains($a->body, 'data-notif-count'));
$a->get('/notifications?filter=unread');
check('Unread filter renders', noErrors($a));

$hb = new Browser($base);
$hb->get('/login');
$hb->post('/login', ['username' => 'northpoint', 'password' => 'hospital123']);
$unread = (int) db()->value("SELECT COUNT(*) FROM notifications WHERE recipient_role = 'hospital' AND recipient_id = ? AND read_at IS NULL", [$northpoint]);
check('Unread count shows in the navigation', str_contains($hb->body, 'data-notif-count title="Unread notifications" >' . $unread . '<'));

$hb->get('/notifications/poll?after=0');
$poll = json_decode($hb->body, true);
check('Poll endpoint returns the unread count as JSON', is_array($poll) && $poll['unread'] === $unread && $poll['items'] === []);
$firstId = (int) db()->value("SELECT MIN(id) FROM notifications WHERE recipient_role = 'hospital' AND recipient_id = ?", [$northpoint]);
$hb->get('/notifications/poll?after=' . $firstId);
$poll = json_decode($hb->body, true);
check('Poll returns unread notifications newer than the last seen id', count($poll['items'] ?? []) > 0 && isset($poll['items'][0]['title'], $poll['items'][0]['tone']));

$mine = (int) db()->value("SELECT id FROM notifications WHERE recipient_role = 'hospital' AND recipient_id = ? AND type = 'request_dispatched' AND request_id = ?", [$northpoint, $otherId]);
$hb->get('/notifications');
$hb->post("/notifications/{$mine}/open");
check('Opening a notification marks it read', db()->value('SELECT read_at FROM notifications WHERE id = ?', [$mine]) !== null);
check('…and goes to the request it is about', str_contains($hb->url, '/requests?status=in_transit') && noErrors($hb));

$theirs = (int) db()->value("SELECT id FROM notifications WHERE recipient_role = 'hospital' AND recipient_id = ? AND read_at IS NULL LIMIT 1", [$riverside]);
$hb->post("/notifications/{$theirs}/open");
check('A hospital cannot open another hospital’s notification', db()->value('SELECT read_at FROM notifications WHERE id = ?', [$theirs]) === null && str_contains($hb->toast(), 'not found'));

$hb->post('/notifications/read-all');
check('Mark all as read clears the inbox', (int) db()->value("SELECT COUNT(*) FROM notifications WHERE recipient_role = 'hospital' AND recipient_id = ? AND read_at IS NULL", [$northpoint]) === 0 && str_contains($hb->toast(), 'as read'));
check('…and other inboxes are untouched', db()->value('SELECT read_at FROM notifications WHERE id = ?', [$theirs]) === null);

$g = new Browser($base);
$g->get('/notifications/poll');
check('Guests cannot poll notifications', str_ends_with($g->url, '/login'));

/* ------------------------------------------------------------------------- */
section('Integrity');

// Concurrency: a unit locked by another in-flight dispatch is skipped, never issued twice.
$cfg = Config::get('db');
$other = new PDO(sprintf('pgsql:host=%s;port=%d;dbname=%s', $cfg['host'], $cfg['port'], $cfg['database']), $cfg['username'], $cfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$oPosType = (int) db()->value("SELECT id FROM blood_types WHERE code = 'O+'");
$other->beginTransaction();
$lockedByOther = (int) $other->query("SELECT id FROM blood_units WHERE blood_type_id = {$oPosType} AND status = 'available' AND expires_at > now() ORDER BY expires_at, id LIMIT 1 FOR UPDATE")->fetchColumn();
$picked = db()->transaction(static fn () => (new App\Repositories\UnitRepository(db()))->pickFefo($oPosType, 1));
$other->rollBack();
check('Concurrent dispatches never pick the same unit (locked rows are skipped)', $picked !== [] && $picked[0] !== $lockedByOther, json_encode([$picked, $lockedByOther]));

check('No test-parked units are left behind', (int) db()->value("SELECT COUNT(*) FROM blood_units WHERE discard_reason = '__parked__'") === 0);
check(
    'Every dispatched unit belongs to an in-transit or received request',
    (int) db()->value("SELECT COUNT(*) FROM blood_units u LEFT JOIN blood_requests r ON r.id = u.request_id
                       WHERE u.status = 'dispatched' AND (r.id IS NULL OR r.status NOT IN ('in_transit', 'completed'))") === 0,
);
check(
    'Units issued per request and group match the recorded allocation exactly',
    (int) db()->value("SELECT COUNT(*) FROM request_allocations a JOIN blood_requests r ON r.id = a.request_id
                       WHERE r.status IN ('in_transit', 'completed')
                         AND a.bags <> (SELECT COUNT(*) FROM blood_units u WHERE u.request_id = a.request_id AND u.blood_type_id = a.blood_type_id)") === 0,
);
check(
    'Every donation has exactly one unit per bag',
    (int) db()->value('SELECT COUNT(*) FROM donations dn WHERE dn.bags <> (SELECT COUNT(*) FROM blood_units u WHERE u.donation_id = dn.id)') === 0,
);
check(
    'No unit reached stock or a hospital without being screened',
    (int) db()->value("SELECT COUNT(*) FROM blood_units WHERE status IN ('available', 'dispatched') AND screened_at IS NULL") === 0,
);
check(
    'No out-of-date unit is still marked available',
    (int) db()->value("SELECT COUNT(*) FROM blood_units WHERE status = 'available' AND expires_at <= now()") === 0,
);
check(
    'Every discarded unit has a reason',
    (int) db()->value("SELECT COUNT(*) FROM blood_units WHERE status = 'discarded' AND COALESCE(discard_reason, '') = ''") === 0,
);
check(
    'No dispatched request is missing its allocation',
    (int) db()->value("SELECT COUNT(*) FROM blood_requests r WHERE r.status IN ('in_transit','completed')
                       AND NOT EXISTS (SELECT 1 FROM request_allocations a WHERE a.request_id = r.id)") === 0,
);
check(
    'No request was sent more than it asked for',
    (int) db()->value('SELECT COUNT(*) FROM blood_requests r WHERE (SELECT COALESCE(SUM(bags), 0) FROM request_allocations WHERE request_id = r.id) > r.bags') === 0,
);

$x = new Browser($base);
$x->get('/login');
for ($i = 0; $i < 5; $i++) {
    $x->post('/login', ['username' => 'staclara', 'password' => 'nope' . $i]);
}
$x->post('/login', ['username' => 'staclara', 'password' => 'hospital123']);
check('Login is throttled after 5 failed attempts', str_contains($x->body, 'Too many failed attempts'));

/* ------------------------------------------------------------------------- */
resetDb();

echo PHP_EOL . ($failed === 0 ? "\033[32m" : "\033[31m") . "{$passed} passed, {$failed} failed\033[0m" . PHP_EOL;
exit($failed === 0 ? 0 : 1);
