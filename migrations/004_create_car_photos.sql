-- Up to five photos per car, one per angle. "front" is the main photo.
-- file_key is random; files live in uploads/{key}-{lg|md|sm}.webp (original is never stored).
CREATE TABLE IF NOT EXISTS car_photos (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    car_id     INTEGER NOT NULL REFERENCES cars (id) ON DELETE CASCADE,
    angle      TEXT    NOT NULL CHECK (angle IN ('front','back','left','right','top')),
    file_key   TEXT    NOT NULL UNIQUE CHECK (length(file_key) = 32),
    width      INTEGER NOT NULL,
    height     INTEGER NOT NULL,
    bytes      INTEGER NOT NULL,
    created_at TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (car_id, angle)
);
