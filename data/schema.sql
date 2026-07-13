CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS noticias (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  titulo TEXT NOT NULL,
  extracto TEXT NOT NULL DEFAULT '',
  contenido TEXT NOT NULL DEFAULT '',
  imagen TEXT NOT NULL DEFAULT '',
  categoria TEXT NOT NULL DEFAULT '',
  estado TEXT NOT NULL DEFAULT 'draft' CHECK (estado IN ('draft', 'published')),
  autor_id INTEGER,
  vistas INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now')),
  published_at TEXT,
  FOREIGN KEY (autor_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS productos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre TEXT NOT NULL,
  descripcion TEXT NOT NULL DEFAULT '',
  imagen TEXT NOT NULL DEFAULT '',
  area_negocio TEXT NOT NULL CHECK (area_negocio IN ('Nutricion Animal', 'Pharma', 'VetPharma')),
  categoria TEXT NOT NULL DEFAULT '',
  marca TEXT NOT NULL DEFAULT '',
  especie TEXT,
  ficha_tecnica TEXT NOT NULL DEFAULT '',
  estado TEXT NOT NULL DEFAULT 'draft' CHECK (estado IN ('draft', 'published')),
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_noticias_estado ON noticias(estado);
CREATE INDEX IF NOT EXISTS idx_productos_estado ON productos(estado);
CREATE INDEX IF NOT EXISTS idx_productos_area ON productos(area_negocio);
