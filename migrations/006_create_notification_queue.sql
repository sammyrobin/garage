-- New cars waiting to be announced by e-mail (grouped into one summary).
CREATE TABLE IF NOT EXISTS notification_queue (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    car_id    INT UNSIGNED NOT NULL,
    queued_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at   DATETIME     NULL,
    PRIMARY KEY (id),
    KEY idx_notification_queue_pending (sent_at, queued_at),
    CONSTRAINT fk_notification_queue_car FOREIGN KEY (car_id) REFERENCES cars (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
