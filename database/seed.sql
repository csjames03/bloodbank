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

-- Every donor has given 3–5 times, roughly every 90 days.
INSERT INTO donations (donor_id, blood_type_id, bags, donated_at)
SELECT dn.id, dn.blood_type_id, 1,
       now() - make_interval(days => s * 90 + dn.id, hours => (dn.id * 7) % 24)
FROM donors dn
CROSS JOIN LATERAL generate_series(0, (dn.id % 3) + 2) AS s;

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
  AND approved_at IS NOT NULL;

-- …but Dr. Yap's O+ request was filled with 1 O+ and 1 O- (compatible substitute),
UPDATE request_allocations SET bags = 1
WHERE request_id = (SELECT id FROM blood_requests WHERE doctor_name = 'Dr. Celine Yap');
INSERT INTO request_allocations (request_id, blood_type_id, bags)
SELECT id, (SELECT id FROM blood_types WHERE code = 'O-'), 1 FROM blood_requests WHERE doctor_name = 'Dr. Celine Yap';

-- …and Northpoint's urgent O+ request was a partial fill (3 of 4 bags).
UPDATE request_allocations SET bags = 3
WHERE request_id = (SELECT r.id FROM blood_requests r JOIN blood_types bt ON bt.id = r.blood_type_id
                    WHERE r.doctor_name = 'Dr. Benjamin Co' AND bt.code = 'O+');

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

-- Live inventory = everything donated minus everything dispatched (by the group actually sent).
UPDATE blood_types bt
SET bags = COALESCE((SELECT SUM(bags) FROM donations WHERE blood_type_id = bt.id), 0)
         - COALESCE((SELECT SUM(a.bags) FROM request_allocations a
                     JOIN blood_requests r ON r.id = a.request_id
                     WHERE a.blood_type_id = bt.id AND r.status IN ('in_transit', 'completed')), 0);

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
CROSS JOIN admins a;

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
SELECT 'admin', a.id, 'stock_low', bt.code || ' is running low', 'Only ' || bt.bags || ' bags left.', '/stocks', now() - INTERVAL '20 minutes'
FROM blood_types bt CROSS JOIN admins a
WHERE bt.code = 'AB-';

INSERT INTO notifications (recipient_role, recipient_id, type, title, body, link, request_id, created_at, read_at)
SELECT 'hospital', r.hospital_id, 'request_dispatched',
       'Your ' || bt.code || ' request is on the way',
       r.bags || CASE WHEN r.bags = 1 THEN ' bag' ELSE ' bags' END || ' for ' || r.doctor_name || ' left the blood bank.',
       '/requests?status=' || r.status || '#request-' || r.id, r.id, r.approved_at,
       CASE WHEN r.status = 'completed' THEN r.approved_at + INTERVAL '1 hour' END
FROM blood_requests r JOIN blood_types bt ON bt.id = r.blood_type_id
WHERE r.approved_at IS NOT NULL;

INSERT INTO notifications (recipient_role, recipient_id, type, title, body, link, request_id, created_at, read_at)
SELECT 'hospital', r.hospital_id, 'request_delivered',
       bt.code || ' delivery recorded as received',
       r.bags || CASE WHEN r.bags = 1 THEN ' bag' ELSE ' bags' END || ' for ' || r.doctor_name || '. You confirmed receipt.',
       '/requests?status=completed#request-' || r.id, r.id, r.completed_at, r.completed_at + INTERVAL '2 hours'
FROM blood_requests r JOIN blood_types bt ON bt.id = r.blood_type_id
WHERE r.status = 'completed';
