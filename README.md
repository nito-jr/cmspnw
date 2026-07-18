# cmspnw

## Deployment and Database Configuration

This project is a PHP/PostgreSQL application designed to run inside Docker and work with Neon.

### Render deployment

- The repository includes `Dockerfile` and `render.yaml` for Render Docker deployment.
- Render should expose the app on port `80` and serve the PHP site from the repo root.
- Create a Render Web Service using the Docker environment and point it to this repository.

### Required environment variables

Set these values in Render's environment settings:

- `DB_HOST` — database host
- `DB_USER` — database username
- `DB_PASS` — database password
- `DB_NAME` — database name
- `DB_PORT` — database port (optional, default `5432`)
- `DATABASE_URL` — optional full PostgreSQL connection URL, e.g. `postgres://user:pass@host:5432/dbname`

Other optional app config values:

- `MESOMB_APPLICATION_KEY`
- `MESOMB_ACCESS_KEY`
- `MESOMB_SECRET_KEY`
- `COUNCIL_MOMO_NUMBER`
- `COUNCIL_OM_NUMBER`
- `COUNCIL_ACCOUNT_NAME`

### Neon compatibility

- Neon is a PostgreSQL database service.
- The project now uses PostgreSQL-compatible schema and a legacy wrapper for the PHP DB layer.
- You can use Neon or any PostgreSQL-compatible service for the database.

### What you need to provide

1. Database host name
2. Database username
3. Database password
4. Database name
5. Database port (if not `5432`)
6. Either individual DB env vars or a single `DATABASE_URL`

## Notes

- Uploads are stored in `uploads/`. On Render, use persistent storage or external object storage if you need uploads to survive deploys.
# ==================================
# Neon Script
# ==================================

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