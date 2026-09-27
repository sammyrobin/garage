-- Real car makers (shown as styled text chips, never as logos).
-- Timestamps are UTC (SQLite CURRENT_TIMESTAMP); the trigger keeps updated_at current.
CREATE TABLE IF NOT EXISTS brands (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    name         TEXT    NOT NULL COLLATE NOCASE UNIQUE CHECK (length(name) <= 80),
    slug         TEXT    NOT NULL UNIQUE CHECK (length(slug) <= 90),
    accent_color TEXT    NOT NULL DEFAULT '#141414' CHECK (length(accent_color) = 7),
    sort_order   INTEGER NOT NULL DEFAULT 0 CHECK (sort_order >= 0),
    created_at   TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER IF NOT EXISTS trg_brands_updated AFTER UPDATE ON brands FOR EACH ROW WHEN NEW.updated_at = OLD.updated_at BEGIN UPDATE brands SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id; END;
