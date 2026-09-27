-- Product line / series (Mainline, Car Culture, Premium...).
CREATE TABLE IF NOT EXISTS series (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT    NOT NULL COLLATE NOCASE UNIQUE CHECK (length(name) <= 80),
    slug       TEXT    NOT NULL UNIQUE CHECK (length(slug) <= 90),
    sort_order INTEGER NOT NULL DEFAULT 0 CHECK (sort_order >= 0),
    created_at TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER IF NOT EXISTS trg_series_updated AFTER UPDATE ON series FOR EACH ROW WHEN NEW.updated_at = OLD.updated_at BEGIN UPDATE series SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id; END;
