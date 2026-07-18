-- ============================================================
-- CMSP Full Database Setup (PostgreSQL)
-- Run this once in your PostgreSQL database.
-- ============================================================

CREATE TYPE IF NOT EXISTS user_role AS ENUM ('admin', 'operator', 'member');
CREATE TYPE IF NOT EXISTS payment_status AS ENUM ('pending', 'approved', 'rejected');
CREATE TYPE IF NOT EXISTS payment_type AS ENUM ('application', 'dues', 'platform_charge');

OR

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_type
        WHERE typname = 'user_role'
    ) THEN
        CREATE TYPE user_role AS ENUM (
            'admin',
            'operator',
            'member'
        );
    END IF;
END $$;


CREATE TABLE IF NOT EXISTS users (
    id                SERIAL PRIMARY KEY,
    name              VARCHAR(100) NOT NULL,
    email             VARCHAR(100) UNIQUE NOT NULL,
    phone             VARCHAR(20),
    address           TEXT,
    password          VARCHAR(255) NOT NULL,
    role              user_role NOT NULL DEFAULT 'member',
    profession        VARCHAR(50),
    school            VARCHAR(100),
    year_graduation   INT,
    year_registration INT,
    license_number    VARCHAR(50),
    photo             VARCHAR(255) DEFAULT 'default.jpg',
    status            public.payments_status_enum NOT NULL DEFAULT 'pending',
    balance_due       NUMERIC(10,2) DEFAULT 0.00,
    platform_charge   NUMERIC(10,2) DEFAULT 4000.00,
    created_at        TIMESTAMP DEFAULT CURRENT_
)

CREATE TABLE IF NOT EXISTS payments (
    id             SERIAL PRIMARY KEY,
    user_id        INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    amount         NUMERIC(10,2) NOT NULL,
    payment_type   payment_type NOT NULL DEFAULT 'dues',
    proof_image    VARCHAR(255),
    transaction_id VARCHAR(255),
    sender_name    VARCHAR(255),
    sender_phone   VARCHAR(50),
    status         payment_status NOT NULL DEFAULT 'pending',
    created_at     TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- If upgrading an existing PostgreSQL database, run these instead:
-- ============================================================
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS school VARCHAR(100);
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS platform_charge NUMERIC(10,2) DEFAULT 4000.00;
-- ALTER TABLE payments ADD COLUMN IF NOT EXISTS transaction_id VARCHAR(255);
-- ALTER TABLE payments ADD COLUMN IF NOT EXISTS sender_name VARCHAR(255);
-- ALTER TABLE payments ADD COLUMN IF NOT EXISTS sender_phone VARCHAR(50);
-- ============================================================
