CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  -- Se guarda siempre en minúsculas: el usuario no distingue mayúsculas.
  username TEXT NOT NULL UNIQUE,
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
  ficha_tecnica TEXT NOT NULL DEFAULT '',
  nota_blog TEXT NOT NULL DEFAULT '',
  area_negocio TEXT NOT NULL CHECK (area_negocio IN ('Nutricion Animal', 'Pharma', 'VetPharma')),
  categoria TEXT NOT NULL DEFAULT '',
  marca TEXT NOT NULL DEFAULT '',
  estado TEXT NOT NULL DEFAULT 'draft' CHECK (estado IN ('draft', 'published')),
  -- Checklist de contenido que viene de la planilla (NULL = sin dato).
  tiene_imagen INTEGER,
  tiene_ficha INTEGER,
  tiene_blog INTEGER,
  producto_terminado INTEGER,
  -- Nota "REPLICAR EN ..." de la planilla: instrucción de carga, no una categoría.
  nota_replicar TEXT NOT NULL DEFAULT '',
  -- Procedencia del registro, ej. 'import:AVES'. Vacío = cargado a mano.
  origen TEXT NOT NULL DEFAULT '',
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Un producto vive en N especies. Sólo aplica a Nutrición Animal; los productos
-- de Pharma/VetPharma simplemente no tienen filas acá.
CREATE TABLE IF NOT EXISTS producto_especies (
  producto_id INTEGER NOT NULL REFERENCES productos(id) ON DELETE CASCADE,
  especie TEXT NOT NULL CHECK (especie IN ('Aves', 'Porcinos', 'Ganadería', 'Mascotas',
                                           'Lechería', 'Equinos', 'Ovinos')),
  PRIMARY KEY (producto_id, especie)
);

CREATE INDEX IF NOT EXISTS idx_noticias_estado ON noticias(estado);
CREATE INDEX IF NOT EXISTS idx_productos_estado ON productos(estado);
CREATE INDEX IF NOT EXISTS idx_productos_area ON productos(area_negocio);
CREATE INDEX IF NOT EXISTS idx_producto_especies_especie ON producto_especies(especie);

-- Identidad de un producto: mismo nombre + área es la misma ficha.
-- Permite que la importación sea idempotente (re-correrla no duplica).
CREATE UNIQUE INDEX IF NOT EXISTS idx_productos_unico
  ON productos(nombre, area_negocio);
