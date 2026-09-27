-- New cars waiting to be announced by e-mail (grouped into one summary).
-- claim_token lets exactly one request/cron run take a batch; attempts caps retries.
CREATE TABLE IF NOT EXISTS notification_queue (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    car_id      INTEGER NOT NULL REFERENCES cars (id) ON DELETE CASCADE,
    queued_at   TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    claim_token TEXT    NULL,
    claimed_at  TEXT    NULL,
    attempts    INTEGER NOT NULL DEFAULT 0,
    sent_at     TEXT    NULL
);

CREATE INDEX IF NOT EXISTS idx_notification_queue_pending ON notification_queue (sent_at, queued_at);
CREATE INDEX IF NOT EXISTS idx_notification_queue_claim ON notification_queue (claim_token);
