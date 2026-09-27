-- One row per die-cast car. Only name, brand and model are required.
-- cost_mxn is PRIVATE: never rendered without an owner session.
CREATE TABLE IF NOT EXISTS cars (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    slug              TEXT    NOT NULL UNIQUE CHECK (length(slug) <= 170),
    name              TEXT    NOT NULL CHECK (length(name) <= 150),
    brand_id          INTEGER NOT NULL REFERENCES brands (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    model             TEXT    NOT NULL CHECK (length(model) <= 150),
    cost_mxn          NUMERIC NULL CHECK (cost_mxn IS NULL OR cost_mxn >= 0),
    series_id         INTEGER NULL REFERENCES series (id) ON UPDATE CASCADE ON DELETE SET NULL,
    real_year         INTEGER NULL,
    casting_year      INTEGER NULL,
    collection_number TEXT    NULL CHECK (collection_number IS NULL OR length(collection_number) <= 20),
    color             TEXT    NULL CHECK (color IS NULL OR length(color) <= 60),
    rarity            TEXT    NULL CHECK (rarity IS NULL OR rarity IN ('mainline','treasure_hunt','super_treasure_hunt','premium','red_line_club','limited','other')),
    item_condition    TEXT    NULL CHECK (item_condition IS NULL OR item_condition IN ('carded','loose','damaged')),
    acquired_at       TEXT    NULL,
    notes             TEXT    NULL,
    is_favorite       INTEGER NOT NULL DEFAULT 0 CHECK (is_favorite IN (0, 1)),
    created_at        TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_cars_brand ON cars (brand_id);
CREATE INDEX IF NOT EXISTS idx_cars_series ON cars (series_id);
CREATE INDEX IF NOT EXISTS idx_cars_rarity ON cars (rarity);
CREATE INDEX IF NOT EXISTS idx_cars_real_year ON cars (real_year);
CREATE INDEX IF NOT EXISTS idx_cars_created ON cars (created_at);
CREATE INDEX IF NOT EXISTS idx_cars_favorite ON cars (is_favorite);

CREATE TRIGGER IF NOT EXISTS trg_cars_updated AFTER UPDATE ON cars FOR EACH ROW WHEN NEW.updated_at = OLD.updated_at BEGIN UPDATE cars SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id; END;
