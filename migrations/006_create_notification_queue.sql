-- New cars waiting to be announced by e-mail (grouped into one summary).
-- claim_token lets exactly one request/cron run take a batch; attempts caps retries.
CREATE TABLE IF NOT EXISTS notification_queue (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    car_id      INT UNSIGNED NOT NULL,
    queued_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    claim_token CHAR(32)     NULL,
    claimed_at  DATETIME     NULL,
    attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    sent_at     DATETIME     NULL,
    PRIMARY KEY (id),
    KEY idx_notification_queue_pending (sent_at, queued_at),
    KEY idx_notification_queue_claim (claim_token),
    CONSTRAINT fk_notification_queue_car FOREIGN KEY (car_id) REFERENCES cars (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
