-- Monotonic revocation epoch for existing and future account sessions.
ALTER TABLE users
    ADD COLUMN authorization_version BIGINT UNSIGNED NOT NULL DEFAULT 1;
