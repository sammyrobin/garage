-- Up to five photos per car, one per angle. "front" is the main photo.
-- file_key is random; files live in uploads/{key}-{lg|md|sm}.webp (original is never stored).
CREATE TABLE IF NOT EXISTS car_photos (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    car_id     INT UNSIGNED NOT NULL,
    angle      ENUM('front','back','left','right','top') NOT NULL,
    file_key   CHAR(32)     NOT NULL,
    width      SMALLINT UNSIGNED NOT NULL,
    height     SMALLINT UNSIGNED NOT NULL,
    bytes      INT UNSIGNED NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_car_photos_angle (car_id, angle),
    UNIQUE KEY uq_car_photos_key (file_key),
    CONSTRAINT fk_car_photos_car FOREIGN KEY (car_id) REFERENCES cars (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
