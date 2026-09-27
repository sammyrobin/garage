-- Failed owner-password attempts, keyed by HMAC of the real visitor IP (never the raw IP).
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_hash      TEXT NOT NULL CHECK (length(ip_hash) = 64),
    attempted_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_login_attempts_ip_time ON login_attempts (ip_hash, attempted_at);
