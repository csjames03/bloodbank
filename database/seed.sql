-- ---------------------------------------------------------------------------
-- Demo data. Admin and hospital accounts are inserted by database/setup.php
-- (passwords need PHP's password_hash), so this file runs after them.
-- ---------------------------------------------------------------------------

INSERT INTO donors (first_name, middle_name, last_name, sex, age, contact, address, blood_type_id, medical_status, created_at)
SELECT d.first_name, d.middle_name, d.last_name, d.sex, d.age, d.contact, d.address,
       (SELECT id FROM blood_types WHERE code = d.code), d.medical_status, now() - INTERVAL '400 days'
FROM (VALUES
    ('Maria',    'Santos',  'Reyes',      'Female', 28, '09171234501', 'Angeles City, Pampanga',     'A+',  'Fit to donate'),
    ('Jose',     NULL,      'Dela Cruz',  'Male',   34, '09171234502', 'San Fernando, Pampanga',     'O+',  'Fit to donate'),
    ('Ana',      'Lim',     'Garcia',     'Female', 22, '09171234503', 'Mabalacat, Pampanga',        'B+',  'Fit to donate'),
    ('Mark',     NULL,      'Villanueva', 'Male',   41, '09171234504', 'Tarlac City, Tarlac',        'O+',  'Mild hypertension, cleared'),
    ('Grace',    'Tan',     'Mendoza',    'Female', 30, '09171234505', 'Clark Freeport Zone',        'A+',  'Fit to donate'),
    ('Paolo',    NULL,      'Ramos',      'Male',   26, '09171234506', 'Porac, Pampanga',            'AB+', 'Fit to donate'),
    ('Kristine', 'Uy',      'Navarro',    'Female', 35, '09171234507', 'Angeles City, Pampanga',     'O-',  'Fit to donate'),
    ('Daniel',   NULL,      'Aquino',     'Male',   45, '09171234508', 'Magalang, Pampanga',         'B+',  'Fit to donate'),
    ('Liza',     'Ong',     'Bautista',   'Female', 29, '09171234509', 'Capas, Tarlac',              'A-',  'Iron supplements, cleared'),
    ('Carlo',    NULL,      'Fernandez',  'Male',   38, '09171234510', 'San Fernando, Pampanga',     'O+',  'Fit to donate'),
    ('Joy',      'Cruz',    'Castillo',   'Female', 24, '09171234511', 'Mexico, Pampanga',           'B-',  'Fit to donate'),
    ('Miguel',   NULL,      'Torres',     'Male',   31, '09171234512', 'Bamban, Tarlac',             'AB-', 'Fit to donate'),
    ('Bea',      'Reyes',   'Soriano',    'Female', 27, '09171234513', 'Angeles City, Pampanga',     'A+',  'Fit to donate'),
    ('Rafael',   NULL,      'Gomez',      'Male',   50, '09171234514', 'Concepcion, Tarlac',         'O+',  'Fit to donate'),
    ('Camille',  'Sy',      'Rivera',     'Female', 33, '09171234515', 'Mabalacat, Pampanga',        'B+',  'Fit to donate'),
    ('Enzo',     NULL,      'Pascual',    'Male',   23, '09171234516', 'Clark Freeport Zone',        'A+',  'Fit to donate'),
    ('Hannah',   'Go',      'Cruz',       'Female', 26, '09171234517', 'San Fernando, Pampanga',     'O-',  'Fit to donate'),
    ('Victor',   NULL,      'Lopez',      'Male',   37, '09171234518', 'Angeles City, Pampanga',     'AB+', 'Fit to donate'),
    ('Nina',     'Chua',    'Flores',     'Female', 32, '09171234519', 'Tarlac City, Tarlac',        'A-',  'Fit to donate')
) AS d (first_name, middle_name, last_name, sex, age, contact, address, code, medical_status);

-- Every regular donor has given 3–5 times, roughly every 90 days.
INSERT INTO donations (donor_id, blood_type_id, bags, donated_at)
SELECT dn.id, dn.blood_type_id, 1,
       now() - make_interval(days => s * 90 + dn.id, hours => (dn.id * 7) % 24)
FROM donors dn
CROSS JOIN LATERAL generate_series(0, (dn.id % 3) + 2) AS s;

-- 72 more recent donors. Whole blood keeps only 35 days, so live stock comes
-- from the last few weeks of collections. Groups follow a typical mix (O+ most common).
INSERT INTO donors (first_name, last_name, sex, age, contact, address, blood_type_id, medical_status, created_at)
SELECT f.names[1 + (k - 1) % 24],
       l.names[1 + (k * 5) % 20],
       CASE WHEN (k - 1) % 24 < 12 THEN 'Female' ELSE 'Male' END,
       18 + (k * 7) % 40,
       '0918' || lpad((5550000 + k)::text, 7, '0'),
       t.towns[1 + k % 8],
       (SELECT id FROM blood_types WHERE code = g.codes[1 + (k - 1) % 16]),
       'Fit to donate',
       now() - make_interval(days => 60 + k)
FROM generate_series(1, 72) AS k,
     (SELECT ARRAY['Andrea','Bianca','Carla','Diana','Elena','Faith','Gina','Hazel','Iris','Jasmine','Katrina','Lara',
                   'Adrian','Bryan','Cedric','Dennis','Edgar','Felix','Gabriel','Henry','Ivan','Joel','Kevin','Leo']) AS f(names),
     (SELECT ARRAY['Santos','Reyes','Cruz','Bautista','Ocampo','Garcia','Mendoza','Torres','Flores','Villanueva',
                   'Ramos','Aquino','Castro','Rivera','Navarro','Salazar','Domingo','Pascual','Mercado','Lim']) AS l(names),
     (SELECT ARRAY['Angeles City, Pampanga','San Fernando, Pampanga','Mabalacat, Pampanga','Tarlac City, Tarlac',
                   'Clark Freeport Zone','Porac, Pampanga','Capas, Tarlac','Magalang, Pampanga']) AS t(towns),
     (SELECT ARRAY['O+','A+','B+','O+','A+','O+','B+','AB+','O-','A-','O+','A+','B-','AB-','O+','B+']) AS g(codes);

-- Each of them gave once in the last 34 days.
INSERT INTO donations (donor_id, blood_type_id, bags, donated_at)
SELECT d.id, d.blood_type_id, CASE WHEN d.k % 3 = 0 THEN 2 ELSE 1 END, now() - make_interval(days => ((d.k * 7) % 34)::int, hours => ((d.k * 5) % 24)::int)
FROM (SELECT id, blood_type_id, row_number() OVER (ORDER BY id) AS k FROM donors WHERE contact LIKE '0918555%') AS d;

-- One unit per bag. The last 36 hours of collections are still in quarantine
-- awaiting screening; earlier ones were screened a day after collection.
INSERT INTO blood_units (donation_id, blood_type_id, status, collected_at, expires_at, screened_at)
SELECT dn.id, dn.blood_type_id,
       CASE WHEN dn.donated_at > now() - INTERVAL '36 hours' THEN 'quarantine' ELSE 'available' END,
       dn.donated_at,
       dn.donated_at + INTERVAL '35 days',
       CASE WHEN dn.donated_at > now() - INTERVAL '36 hours' THEN NULL ELSE dn.donated_at + INTERVAL '1 day' END
FROM donations dn
CROSS JOIN LATERAL generate_series(1, dn.bags);

-- One donation failed screening (hepatitis B): discarded, donor permanently deferred.
UPDATE blood_units u SET status = 'discarded', discarded_at = u.screened_at,
       discard_reason = 'Failed screening: Hepatitis B reactive'
FROM donations dn JOIN donors d ON d.id = dn.donor_id
WHERE u.donation_id = dn.id AND d.contact = '09185550012';
UPDATE donors SET deferred_permanently = true, deferral_reason = 'Hepatitis B reactive' WHERE contact = '09185550012';

-- Older units: most were issued to hospitals as weekly ward restocks; a few
-- expired on the shelf.
UPDATE blood_units SET status = 'expired' WHERE status = 'available' AND expires_at <= now() AND id % 8 = 0;

CREATE TEMP TABLE seed_history AS
SELECT nextval(pg_get_serial_sequence('blood_requests', 'id')) AS rid, b.*
FROM (
    SELECT blood_type_id, date_trunc('week', collected_at) AS wk,
           array_agg(id ORDER BY id) AS ids, COUNT(*) AS n, MAX(collected_at) AS last_collected
    FROM blood_units WHERE status = 'available' AND expires_at <= now()
    GROUP BY 1, 2
) AS b;

INSERT INTO blood_requests (id, hospital_id, blood_type_id, doctor_name, bags, priority, status,
                            requested_at, approved_at, completed_at)
SELECT h.rid, (SELECT id FROM hospitals ORDER BY id OFFSET (h.rid % 3) LIMIT 1), h.blood_type_id,
       'Ward stock replenishment', h.n, 'routine', 'completed',
       h.last_collected + INTERVAL '2 days', h.last_collected + INTERVAL '2 days 2 hours', h.last_collected + INTERVAL '2 days 6 hours'
FROM seed_history h;

INSERT INTO request_allocations (request_id, blood_type_id, bags)
SELECT rid, blood_type_id, n FROM seed_history;

UPDATE blood_units u SET status = 'dispatched', request_id = h.rid, dispatched_at = h.last_collected + INTERVAL '2 days 2 hours'
FROM seed_history h WHERE u.id = ANY (h.ids);

DROP TABLE seed_history;

INSERT INTO blood_requests (hospital_id, blood_type_id, doctor_name, bags, priority, needed_by, status,
                            requested_at, approved_at, completed_at, cancelled_at)
SELECT (SELECT id FROM hospitals WHERE username = r.hospital),
       (SELECT id FROM blood_types WHERE code = r.code),
       r.doctor, r.bags, r.priority,
       CASE WHEN r.needed_in IS NOT NULL THEN now() + r.needed_in END,
       r.status,
       now() - r.age,
       CASE WHEN r.status IN ('in_transit', 'completed') THEN now() - r.age + INTERVAL '2 hours' END,
       CASE WHEN r.status = 'completed' THEN now() - r.age + INTERVAL '6 hours' END,
       CASE WHEN r.status = 'cancelled' THEN now() - r.age + INTERVAL '1 hour' END
FROM (VALUES
    ('riverside',  'A+',  'Dr. Andrea Salazar',  3, 'routine',   NULL::interval,       'completed',  INTERVAL '9 days'),
    ('northpoint', 'O+',  'Dr. Benjamin Co',     4, 'urgent',    NULL,                 'completed',  INTERVAL '6 days'),
    ('riverside',  'O-',  'Dr. Andrea Salazar',  2, 'routine',   NULL,                 'cancelled',  INTERVAL '5 days'),
    ('riverside',  'B+',  'Dr. Paul Manalo',     2, 'routine',   NULL,                 'in_transit', INTERVAL '2 days'),
    ('staclara',   'O+',  'Dr. Celine Yap',      2, 'urgent',    NULL,                 'in_transit', INTERVAL '1 day'),
    ('northpoint', 'A+',  'Dr. Benjamin Co',     2, 'routine',   INTERVAL '2 days',    'pending',    INTERVAL '8 hours'),
    ('staclara',   'AB+', 'Dr. Ramon David',     1, 'emergency', INTERVAL '1 hour',    'pending',    INTERVAL '3 hours'),
    ('northpoint', 'B-',  'Dr. Irene Santiago',  1, 'urgent',    INTERVAL '5 hours',   'pending',    INTERVAL '40 minutes')
) AS r (hospital, code, doctor, bags, priority, needed_in, status, age);

-- What was sent for dispatched requests: normally the patient's own group…
INSERT INTO request_allocations (request_id, blood_type_id, bags)
SELECT id, blood_type_id, bags FROM blood_requests WHERE status IN ('in_transit', 'completed', 'cancelled')
  AND approved_at IS NOT NULL AND doctor_name <> 'Ward stock replenishment';

-- …but Dr. Yap's O+ request was filled with 1 O+ and 1 O- (compatible substitute),
UPDATE request_allocations SET bags = 1
WHERE request_id = (SELECT id FROM blood_requests WHERE doctor_name = 'Dr. Celine Yap');
INSERT INTO request_allocations (request_id, blood_type_id, bags)
SELECT id, (SELECT id FROM blood_types WHERE code = 'O-'), 1 FROM blood_requests WHERE doctor_name = 'Dr. Celine Yap';

-- …and Northpoint's urgent O+ request was a partial fill (3 of 4 bags).
UPDATE request_allocations SET bags = 3
WHERE request_id = (SELECT r.id FROM blood_requests r JOIN blood_types bt ON bt.id = r.blood_type_id
                    WHERE r.doctor_name = 'Dr. Benjamin Co' AND bt.code = 'O+');

-- Issue the actual units for those dispatches, first-expired-first-out.
WITH need AS (
    SELECT a.request_id, a.blood_type_id, a.bags,
           SUM(a.bags) OVER (PARTITION BY a.blood_type_id ORDER BY a.request_id) - a.bags AS start
    FROM request_allocations a
    JOIN blood_requests r ON r.id = a.request_id
    WHERE r.status IN ('in_transit', 'completed') AND r.doctor_name <> 'Ward stock replenishment'
), ranked AS (
    SELECT id, blood_type_id, row_number() OVER (PARTITION BY blood_type_id ORDER BY expires_at, id) AS rn
    FROM blood_units WHERE status = 'available' AND expires_at > now()
)
UPDATE blood_units u SET status = 'dispatched', request_id = n.request_id, dispatched_at = r.approved_at
FROM ranked k
JOIN need n ON n.blood_type_id = k.blood_type_id AND k.rn > n.start AND k.rn <= n.start + n.bags
JOIN blood_requests r ON r.id = n.request_id
WHERE u.id = k.id;

-- A bag found leaking at the last inventory check.
UPDATE blood_units SET status = 'discarded', discarded_at = now() - INTERVAL '2 days',
       discard_reason = 'Bag damaged or leaking — seam leak found at inventory check'
WHERE id = (SELECT u.id FROM blood_units u JOIN blood_types bt ON bt.id = u.blood_type_id
            WHERE bt.code = 'O+' AND u.status = 'available' ORDER BY u.expires_at DESC LIMIT 1);

-- Audit trail for the demo history.
INSERT INTO activity_log (subject, subject_id, action, actor_role, actor_id, actor_name, note, created_at)
SELECT 'request', r.id, e.action, e.role,
       CASE e.role WHEN 'hospital' THEN h.id ELSE (SELECT id FROM admins WHERE username = 'admin') END,
       CASE e.role WHEN 'hospital' THEN h.name ELSE (SELECT full_name FROM admins WHERE username = 'admin') END,
       e.note, e.at
FROM blood_requests r
JOIN hospitals h ON h.id = r.hospital_id
CROSS JOIN LATERAL (VALUES
    ('created',   'hospital', CASE WHEN r.priority = 'routine' THEN '' ELSE initcap(r.priority) END, r.requested_at),
    ('approved',  'admin',    'Sent ' || (SELECT string_agg(a.bags || ' ' || bt.code, ', ' ORDER BY a.bags DESC)
                                          FROM request_allocations a JOIN blood_types bt ON bt.id = a.blood_type_id
                                          WHERE a.request_id = r.id)
                              || CASE WHEN (SELECT SUM(bags) FROM request_allocations WHERE request_id = r.id) < r.bags
                                      THEN ' (partial: ' || (SELECT SUM(bags) FROM request_allocations WHERE request_id = r.id) || ' of ' || r.bags || ')'
                                      ELSE '' END, r.approved_at),
    ('received',  'hospital', '', r.completed_at),
    ('cancelled', 'hospital', '', r.cancelled_at)
) AS e (action, role, note, at)
WHERE e.at IS NOT NULL;

-- Screening and inventory history.
INSERT INTO activity_log (subject, subject_id, action, actor_role, actor_id, actor_name, note, created_at)
SELECT 'donation', dn.id, 'screened', 'admin', a.id, a.full_name,
       'Failed: Hepatitis B reactive; 1 unit discarded; donor permanently deferred', u.screened_at
FROM blood_units u JOIN donations dn ON dn.id = u.donation_id JOIN donors d ON d.id = dn.donor_id
CROSS JOIN (SELECT id, full_name FROM admins WHERE username = 'admin') a
WHERE d.contact = '09185550012';

INSERT INTO activity_log (subject, subject_id, action, actor_role, actor_id, actor_name, note, created_at)
SELECT 'unit', u.id, 'discarded', 'admin', a.id, a.full_name, 'O+: ' || u.discard_reason, u.discarded_at
FROM blood_units u CROSS JOIN (SELECT id, full_name FROM admins WHERE username = 'admin') a
WHERE u.discard_reason LIKE 'Bag damaged%';

-- Notifications matching the demo history (older ones already read).
INSERT INTO notifications (recipient_role, recipient_id, type, title, body, link, request_id, created_at, read_at)
SELECT 'admin', a.id, 'request_created',
       'New request from ' || h.name,
       r.doctor_name || ' needs ' || r.bags || CASE WHEN r.bags = 1 THEN ' bag' ELSE ' bags' END || ' of ' || bt.code || '.',
       '/requests?status=' || r.status || '#request-' || r.id, r.id, r.requested_at,
       CASE WHEN r.status <> 'pending' THEN r.requested_at + INTERVAL '30 minutes' END
FROM blood_requests r
JOIN hospitals h ON h.id = r.hospital_id
JOIN blood_types bt ON bt.id = r.blood_type_id
CROSS JOIN admins a
WHERE r.doctor_name <> 'Ward stock replenishment';

INSERT INTO notifications (recipient_role, recipient_id, type, title, body, link, request_id, created_at, read_at)
SELECT 'admin', a.id, 'request_withdrawn',
       h.name || ' withdrew a request',
       r.bags || CASE WHEN r.bags = 1 THEN ' bag' ELSE ' bags' END || ' of ' || bt.code || ' for ' || r.doctor_name || '.',
       '/requests?status=cancelled#request-' || r.id, r.id, r.cancelled_at, r.cancelled_at + INTERVAL '1 hour'
FROM blood_requests r
JOIN hospitals h ON h.id = r.hospital_id
JOIN blood_types bt ON bt.id = r.blood_type_id
CROSS JOIN admins a
WHERE r.status = 'cancelled';

INSERT INTO notifications (recipient_role, recipient_id, type, title, body, link, created_at)
SELECT 'admin', a.id, 'stock_low', bt.code || ' is running low',
       'Only ' || (SELECT COUNT(*) FROM blood_units u WHERE u.blood_type_id = bt.id AND u.status = 'available' AND u.expires_at > now()) || ' bags left.',
       '/stocks', now() - INTERVAL '20 minutes'
FROM blood_types bt CROSS JOIN admins a
WHERE bt.code = 'AB-';

INSERT INTO notifications (recipient_role, recipient_id, type, title, body, link, request_id, created_at, read_at)
SELECT 'hospital', r.hospital_id, 'request_dispatched',
       'Your ' || bt.code || ' request is on the way',
       r.bags || CASE WHEN r.bags = 1 THEN ' bag' ELSE ' bags' END || ' for ' || r.doctor_name || ' left the blood bank.',
       '/requests?status=' || r.status || '#request-' || r.id, r.id, r.approved_at,
       CASE WHEN r.status = 'completed' THEN r.approved_at + INTERVAL '1 hour' END
FROM blood_requests r JOIN blood_types bt ON bt.id = r.blood_type_id
WHERE r.approved_at IS NOT NULL AND r.doctor_name <> 'Ward stock replenishment';

INSERT INTO notifications (recipient_role, recipient_id, type, title, body, link, request_id, created_at, read_at)
SELECT 'hospital', r.hospital_id, 'request_delivered',
       bt.code || ' delivery recorded as received',
       r.bags || CASE WHEN r.bags = 1 THEN ' bag' ELSE ' bags' END || ' for ' || r.doctor_name || '. You confirmed receipt.',
       '/requests?status=completed#request-' || r.id, r.id, r.completed_at, r.completed_at + INTERVAL '2 hours'
FROM blood_requests r JOIN blood_types bt ON bt.id = r.blood_type_id
WHERE r.status = 'completed' AND r.doctor_name <> 'Ward stock replenishment';
