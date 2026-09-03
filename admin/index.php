<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>INSALCOR Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <div class="admin-layout" id="app" style="display:none">
    <aside class="sidebar">
      <div class="sidebar-brand">
        <strong>INSALCOR</strong>
        <span>Admin Panel</span>
      </div>
      <button class="nav-item active" data-section="noticias" type="button">
        <i class="fa-regular fa-newspaper"></i> Noticias
      </button>
      <button class="nav-item" data-section="productos" type="button">
        <i class="fa-regular fa-image"></i> Productos
      </button>
      <button class="nav-item" data-section="tutoriales" type="button">
        <i class="fa-brands fa-youtube"></i> Tutoriales
      </button>
    </aside>

    <div class="main">
      <header class="topbar">
        <h2 id="page-title">Gestión de Noticias</h2>
        <div class="topbar-user">
          <div>
            <strong>Admin</strong>
            <span id="user-username"></span>
          </div>
          <button class="icon-btn" id="btn-logout" title="Salir" type="button"><i class="fa-solid fa-right-from-bracket"></i></button>
        </div>
      </header>

      <div class="content">
        <!-- Noticias -->
        <section id="section-noticias">
          <div class="toolbar">
            <div class="meta-count">Total de noticias: <span id="noticias-count">0</span></div>
            <div class="search-wrap">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="search" id="noticias-search" placeholder="Buscar noticias...">
            </div>
            <button class="btn btn-blue" id="btn-new-noticia" type="button"><i class="fa-solid fa-plus"></i> Nueva Noticia</button>
          </div>
          <div class="card-table">
            <table class="data">
              <thead>
                <tr>
                  <th>Noticia</th>
                  <th>Autor</th>
                  <th>Fecha</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="noticias-tbody"></tbody>
            </table>
            <div id="noticias-empty" class="empty hidden">No hay noticias todavía.</div>
          </div>
        </section>

        <!-- Productos -->
        <section id="section-productos" class="hidden">
          <div class="toolbar">
            <div class="meta-count">Total de productos: <span id="productos-count">0</span></div>
            <div class="search-wrap">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="search" id="productos-search" placeholder="Buscar productos...">
            </div>
            <button class="btn btn-blue" id="btn-new-producto" type="button"><i class="fa-solid fa-plus"></i> Nuevo Producto</button>
          </div>
          <div class="card-table">
            <table class="data">
              <thead>
                <tr>
                  <th>Producto</th>
                  <th>Área</th>
                  <th>Categorías</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="productos-tbody"></tbody>
            </table>
            <div id="productos-empty" class="empty hidden">No hay productos todavía.</div>
          </div>
        </section>

        <!-- Tutoriales -->
        <section id="section-tutoriales" class="hidden">
          <div class="toolbar">
            <div class="meta-count">Total de tutoriales: <span id="tutoriales-count">0</span></div>
            <div class="search-wrap">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="search" id="tutoriales-search" placeholder="Buscar tutoriales...">
            </div>
            <button class="btn btn-blue" id="btn-new-tutorial" type="button"><i class="fa-solid fa-plus"></i> Nuevo Tutorial</button>
          </div>
          <div class="card-table">
            <table class="data">
              <thead>
                <tr>
                  <th>Miniatura</th>
                  <th>Título</th>
                  <th>Video</th>
                  <th>Fecha</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="tutoriales-tbody"></tbody>
            </table>
            <div id="tutoriales-empty" class="empty hidden">No hay tutoriales todavía.</div>
          </div>
        </section>
      </div>
    </div>
  </div>

  <!-- Noticia Modal -->
  <div id="modal-noticia" class="modal-backdrop hidden">
    <div class="modal">
      <div class="modal-header">
        <h3 id="modal-noticia-title">Nueva Noticia</h3>
        <button class="close-btn" data-close="modal-noticia" type="button">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="noticia-id">
        <div class="form-group">
          <label for="noticia-titulo">Título</label>
          <input id="noticia-titulo" type="text" placeholder="Título de la noticia">
        </div>
        <div class="form-group">
          <label for="noticia-extracto">Extracto</label>
          <textarea id="noticia-extracto" rows="2" placeholder="Breve resumen..."></textarea>
        </div>
        <div class="form-group">
          <label for="noticia-categoria">Categoría</label>
          <select id="noticia-categoria">
            <option value="">Seleccionar categoría</option>
            <option value="Nutricion Animal">Nutrición Animal</option>
            <option value="Pharma">Pharma</option>
            <option value="VetPharma">VetPharma</option>
          </select>
        </div>
        <div class="form-group">
          <label>Contenido</label>
          <div id="noticia-contenido-editor"></div>
        </div>
        <div style="font-weight:700;text-transform:uppercase;font-size:12px;letter-spacing:.5px;color:#6b7280;border-top:1px solid #e5e7eb;padding-top:16px;margin-top:8px;margin-bottom:4px">English <span class="hint" style="text-transform:none;font-weight:400;letter-spacing:normal">(se completa solo al Guardar/Publicar con una traducción automática — revisá y ajustá si hace falta)</span></div>
        <div class="form-group">
          <label for="noticia-titulo-en">Título (Inglés)</label>
          <input id="noticia-titulo-en" type="text" placeholder="News title">
        </div>
        <div class="form-group">
          <label for="noticia-extracto-en">Extracto (Inglés)</label>
          <textarea id="noticia-extracto-en" rows="2" placeholder="Short summary..."></textarea>
        </div>
        <div class="form-group">
          <label>Contenido (Inglés)</label>
          <div id="noticia-contenido-en-editor"></div>
        </div>
        <div class="form-group">
          <label>Imagen</label>
          <div class="dropzone" id="noticia-dropzone">
            <div>Arrastra una imagen o haz clic para seleccionar</div>
            <img id="noticia-preview" class="hidden" alt="">
          </div>
          <input type="file" id="noticia-file" accept="image/*" hidden>
          <input type="hidden" id="noticia-imagen">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-close="modal-noticia" type="button">Cancelar</button>
        <button class="btn btn-secondary" id="btn-save-noticia-draft" type="button">Guardar</button>
        <button class="btn btn-blue" id="btn-save-noticia-publish" type="button">Publicar</button>
      </div>
    </div>
  </div>

  <!-- Producto Modal -->
  <div id="modal-producto" class="modal-backdrop hidden">
    <div class="modal">
      <div class="modal-header">
        <h3 id="modal-producto-title">Nuevo Producto</h3>
        <button class="close-btn" data-close="modal-producto" type="button">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="producto-id">
        <div class="form-group">
          <label for="producto-nombre">Nombre</label>
          <input id="producto-nombre" type="text" placeholder="Nombre del producto">
        </div>
        <div class="form-group">
          <label for="producto-area">Área de negocio</label>
          <select id="producto-area">
            <option value="Nutricion Animal">Nutricion Animal</option>
            <option value="Pharma">Pharma</option>
            <option value="VetPharma">VetPharma</option>
          </select>
        </div>
        <div class="form-group" id="producto-especie-wrap">
          <label>Categorías <span class="hint">(un producto puede estar en varias)</span></label>
          <div class="checkbox-grid" id="producto-especies">
            <label><input type="checkbox" name="especie" value="Aves"> Aves</label>
            <label><input type="checkbox" name="especie" value="Porcinos"> Porcinos</label>
            <label><input type="checkbox" name="especie" value="Ganadería"> Ganadería</label>
            <label><input type="checkbox" name="especie" value="Mascotas"> Mascotas</label>
            <label><input type="checkbox" name="especie" value="Lechería"> Lechería</label>
            <label><input type="checkbox" name="especie" value="Equinos"> Equinos</label>
            <label><input type="checkbox" name="especie" value="Ovinos"> Ovinos</label>
          </div>
        </div>
        <div class="form-group">
          <label>Descripción</label>
          <div id="producto-descripcion-editor"></div>
        </div>
        <div style="font-weight:700;text-transform:uppercase;font-size:12px;letter-spacing:.5px;color:#6b7280;border-top:1px solid #e5e7eb;padding-top:16px;margin-top:8px;margin-bottom:4px">English <span class="hint" style="text-transform:none;font-weight:400;letter-spacing:normal">(se completa solo al Guardar/Publicar con una traducción automática — revisá y ajustá si hace falta)</span></div>
        <div class="form-group">
          <label for="producto-nombre-en">Nombre (Inglés)</label>
          <input id="producto-nombre-en" type="text" placeholder="Product name">
        </div>
        <div class="form-group">
          <label>Descripción (Inglés)</label>
          <div id="producto-descripcion-en-editor"></div>
        </div>
        <div class="form-group">
          <label>Imagen</label>
          <div class="dropzone" id="producto-dropzone">
            <div>Arrastra una imagen o haz clic para seleccionar</div>
            <img id="producto-preview" class="hidden" alt="">
          </div>
          <input type="file" id="producto-file" accept="image/*" hidden>
          <input type="hidden" id="producto-imagen">
        </div>
        <div class="form-group">
          <label for="producto-ficha">Ficha técnica (URL o subir PDF)</label>
          <input id="producto-ficha" type="text" placeholder="uploads/docs/...">
          <input type="file" id="producto-ficha-file" accept="application/pdf" style="margin-top:8px">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-close="modal-producto" type="button">Cancelar</button>
        <button class="btn btn-secondary" id="btn-save-producto-draft" type="button">Guardar</button>
        <button class="btn btn-blue" id="btn-save-producto-publish" type="button">Publicar</button>
      </div>
    </div>
  </div>

  <!-- Tutorial Modal -->
  <div id="modal-tutorial" class="modal-backdrop hidden">
    <div class="modal">
      <div class="modal-header">
        <h3 id="modal-tutorial-title">Nuevo Tutorial</h3>
        <button class="close-btn" data-close="modal-tutorial" type="button">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="tutorial-id">
        <div class="form-group">
          <label for="tutorial-titulo">Título</label>
          <input id="tutorial-titulo" type="text" placeholder="Ej: Cómo usar A-Max Ultra">
        </div>
        <div class="form-group">
          <label for="tutorial-youtube-url">URL de YouTube</label>
          <input id="tutorial-youtube-url" type="text" placeholder="https://www.youtube.com/watch?v=...">
          <div class="hint">Formatos aceptados: youtube.com/watch?v=..., youtu.be/..., youtube.com/embed/...</div>
        </div>
        <div class="form-group" id="tutorial-preview-wrap" style="display:none">
          <label>Vista previa</label>
          <img id="tutorial-preview" alt="" style="max-width:280px;border-radius:8px;display:block">
        </div>
        <div class="form-group">
          <label for="tutorial-descripcion">Descripción</label>
          <textarea id="tutorial-descripcion" rows="4" placeholder="Texto de detalle del tutorial..."></textarea>
        </div>
        <div style="font-weight:700;text-transform:uppercase;font-size:12px;letter-spacing:.5px;color:#6b7280;border-top:1px solid #e5e7eb;padding-top:16px;margin-top:8px;margin-bottom:4px">English <span class="hint" style="text-transform:none;font-weight:400;letter-spacing:normal">(se completa solo al Guardar/Publicar con una traducción automática — revisá y ajustá si hace falta)</span></div>
        <div class="form-group">
          <label for="tutorial-titulo-en">Título (Inglés)</label>
          <input id="tutorial-titulo-en" type="text" placeholder="Ej: How to use A-Max Ultra">
        </div>
        <div class="form-group">
          <label for="tutorial-descripcion-en">Descripción (Inglés)</label>
          <textarea id="tutorial-descripcion-en" rows="4" placeholder="Tutorial detail text..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-close="modal-tutorial" type="button">Cancelar</button>
        <button class="btn btn-secondary" id="btn-save-tutorial-draft" type="button">Guardar</button>
        <button class="btn btn-blue" id="btn-save-tutorial-publish" type="button">Publicar</button>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
  <script src="../assets/js/admin/api.js"></script>
  <script src="../assets/js/admin/app.js"></script>
</body>
</html>
