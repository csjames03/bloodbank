-- ---------------------------------------------------------------------------
-- LifeLine Blood Bank — PostgreSQL schema
-- Run through `php database/setup.php` (recommended) or psql directly.
-- ---------------------------------------------------------------------------

DROP TABLE IF EXISTS activity_log        CASCADE;
DROP TABLE IF EXISTS request_allocations CASCADE;
DROP TABLE IF EXISTS notifications  CASCADE;
DROP TABLE IF EXISTS blood_requests CASCADE;
DROP TABLE IF EXISTS donations      CASCADE;
DROP TABLE IF EXISTS donors         CASCADE;
DROP TABLE IF EXISTS blood_types    CASCADE;
DROP TABLE IF EXISTS hospitals      CASCADE;
DROP TABLE IF EXISTS admins         CASCADE;

-- Blood bank staff.
CREATE TABLE admins (
    id            SERIAL PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    full_name     VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT now()
);

-- Partner hospitals that request blood.
CREATE TABLE hospitals (
    id            SERIAL PRIMARY KEY,
    name          VARCHAR(150) NOT NULL UNIQUE,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    address       VARCHAR(255) NOT NULL,
    contact       VARCHAR(30),
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT now()
);

-- One row per ABO/Rh group. `bags` is the live, available inventory.
CREATE TABLE blood_types (
    id   SMALLSERIAL PRIMARY KEY,
    code VARCHAR(3) NOT NULL UNIQUE,
    abo  VARCHAR(2) NOT NULL CHECK (abo IN ('A', 'B', 'AB', 'O')),
    rh   CHAR(1)    NOT NULL CHECK (rh IN ('+', '-')),
    bags INTEGER    NOT NULL DEFAULT 0 CHECK (bags >= 0),
    UNIQUE (abo, rh)
);

CREATE TABLE donors (
    id             SERIAL PRIMARY KEY,
    first_name     VARCHAR(60)  NOT NULL,
    middle_name    VARCHAR(60),
    last_name      VARCHAR(60)  NOT NULL,
    sex            VARCHAR(6)   NOT NULL CHECK (sex IN ('Male', 'Female')),
    age            SMALLINT     NOT NULL CHECK (age BETWEEN 16 AND 65),
    contact        VARCHAR(20)  NOT NULL,
    address        VARCHAR(255) NOT NULL,
    blood_type_id  SMALLINT     NOT NULL REFERENCES blood_types (id),
    medical_status VARCHAR(255) NOT NULL DEFAULT 'Fit to donate',
    created_at     TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at     TIMESTAMPTZ  NOT NULL DEFAULT now()
);

-- Each collection event. `blood_type_id` is the group the bags were stocked under.
CREATE TABLE donations (
    id            SERIAL PRIMARY KEY,
    donor_id      INTEGER     NOT NULL REFERENCES donors (id) ON DELETE CASCADE,
    blood_type_id SMALLINT    NOT NULL REFERENCES blood_types (id),
    hospital_id   INTEGER     REFERENCES hospitals (id) ON DELETE SET NULL,
    bags          SMALLINT    NOT NULL CHECK (bags BETWEEN 1 AND 2),
    donated_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- Hospital requests and their delivery lifecycle:
--   pending -> in_transit -> completed (received by the hospital)
--   pending | in_transit -> cancelled
-- `blood_type_id` is the patient's group; what was actually sent (possibly a
-- compatible substitute, possibly fewer bags) lives in request_allocations.
CREATE TABLE blood_requests (
    id            SERIAL PRIMARY KEY,
    hospital_id   INTEGER      NOT NULL REFERENCES hospitals (id) ON DELETE CASCADE,
    blood_type_id SMALLINT     NOT NULL REFERENCES blood_types (id),
    doctor_name   VARCHAR(100) NOT NULL,
    bags          SMALLINT     NOT NULL CHECK (bags BETWEEN 1 AND 20),
    priority      VARCHAR(10)  NOT NULL DEFAULT 'routine'
                  CHECK (priority IN ('routine', 'urgent', 'emergency')),
    needed_by     TIMESTAMPTZ,
    status        VARCHAR(12)  NOT NULL DEFAULT 'pending'
                  CHECK (status IN ('pending', 'in_transit', 'completed', 'cancelled')),
    requested_at  TIMESTAMPTZ  NOT NULL DEFAULT now(),
    approved_at   TIMESTAMPTZ,
    completed_at  TIMESTAMPTZ,
    cancelled_at  TIMESTAMPTZ
);

-- Bags dispatched for a request, per donor group. Counted against stock while
-- the request is in_transit or completed; kept as history if it is cancelled.
CREATE TABLE request_allocations (
    request_id    INTEGER  NOT NULL REFERENCES blood_requests (id) ON DELETE CASCADE,
    blood_type_id SMALLINT NOT NULL REFERENCES blood_types (id),
    bags          SMALLINT NOT NULL CHECK (bags > 0),
    PRIMARY KEY (request_id, blood_type_id)
);

-- Audit trail: who did what, and when. Actor name is copied so the history
-- survives renamed or deleted accounts.
CREATE TABLE activity_log (
    id         BIGSERIAL PRIMARY KEY,
    subject    VARCHAR(20)  NOT NULL CHECK (subject IN ('request', 'donation')),
    subject_id INTEGER      NOT NULL,
    action     VARCHAR(20)  NOT NULL,
    actor_role VARCHAR(8)   NOT NULL CHECK (actor_role IN ('admin', 'hospital')),
    actor_id   INTEGER      NOT NULL,
    actor_name VARCHAR(150) NOT NULL,
    note       VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMPTZ  NOT NULL DEFAULT now()
);

-- In-app notifications, one row per recipient (staff alerts fan out to every
-- admin). `recipient_role` + `recipient_id` point at admins.id or hospitals.id.
CREATE TABLE notifications (
    id             BIGSERIAL PRIMARY KEY,
    recipient_role VARCHAR(8)   NOT NULL CHECK (recipient_role IN ('admin', 'hospital')),
    recipient_id   INTEGER      NOT NULL,
    type           VARCHAR(30)  NOT NULL,
    title          VARCHAR(160) NOT NULL,
    body           VARCHAR(255) NOT NULL DEFAULT '',
    link           VARCHAR(255),
    request_id     INTEGER      REFERENCES blood_requests (id) ON DELETE CASCADE,
    created_at     TIMESTAMPTZ  NOT NULL DEFAULT now(),
    read_at        TIMESTAMPTZ
);

CREATE INDEX idx_donations_donated_at  ON donations (donated_at DESC);
CREATE INDEX idx_donations_blood_type   ON donations (blood_type_id);
CREATE INDEX idx_requests_status        ON blood_requests (status);
CREATE INDEX idx_requests_hospital      ON blood_requests (hospital_id);
CREATE INDEX idx_requests_requested_at  ON blood_requests (requested_at DESC);
CREATE INDEX idx_notifications_inbox    ON notifications (recipient_role, recipient_id, id DESC);
CREATE INDEX idx_notifications_unread   ON notifications (recipient_role, recipient_id) WHERE read_at IS NULL;
CREATE INDEX idx_activity_subject       ON activity_log (subject, subject_id);
CREATE INDEX idx_activity_created_at    ON activity_log (created_at DESC);

INSERT INTO blood_types (code, abo, rh) VALUES
    ('A+', 'A', '+'), ('A-', 'A', '-'),
    ('B+', 'B', '+'), ('B-', 'B', '-'),
    ('AB+', 'AB', '+'), ('AB-', 'AB', '-'),
    ('O+', 'O', '+'), ('O-', 'O', '-');
