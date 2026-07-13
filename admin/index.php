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
    </aside>

    <div class="main">
      <header class="topbar">
        <h2 id="page-title">Gestión de Noticias</h2>
        <div class="topbar-user">
          <div>
            <strong>Admin</strong>
            <span id="user-email"></span>
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
                  <th>Categoría</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="productos-tbody"></tbody>
            </table>
            <div id="productos-empty" class="empty hidden">No hay productos todavía.</div>
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
          <input id="noticia-categoria" type="text" placeholder="Ej. Nutrición Animal">
        </div>
        <div class="form-group">
          <label>Contenido</label>
          <div id="noticia-contenido-editor"></div>
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
        <div class="grid-2">
          <div class="form-group">
            <label for="producto-area">Área de negocio</label>
            <select id="producto-area">
              <option value="Nutricion Animal">Nutricion Animal</option>
              <option value="Pharma">Pharma</option>
              <option value="VetPharma">VetPharma</option>
            </select>
          </div>
          <div class="form-group">
            <label for="producto-categoria">Categoría</label>
            <select id="producto-categoria">
              <option value="">—</option>
              <option>Premezclas</option>
              <option>Aditivos</option>
              <option>Correctores</option>
              <option>Excipientes</option>
              <option>APIS</option>
              <option>Minerales</option>
              <option>Vitaminas</option>
            </select>
          </div>
          <div class="form-group">
            <label for="producto-marca">Marca</label>
            <select id="producto-marca">
              <option value="">—</option>
              <option>Ingredion</option>
              <option>Mingtai Chemicals</option>
              <option>Kerry BioScience</option>
              <option>Research AG</option>
              <option>Otros</option>
            </select>
          </div>
          <div class="form-group" id="producto-especie-wrap">
            <label for="producto-especie">Especie</label>
            <select id="producto-especie">
              <option value="">—</option>
              <option>Aves</option>
              <option>Porcinos</option>
              <option>Ganadería</option>
              <option>Mascotas</option>
              <option>Lechería</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Descripción</label>
          <div id="producto-descripcion-editor"></div>
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

  <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
  <script src="../assets/js/admin/api.js"></script>
  <script src="../assets/js/admin/app.js"></script>
</body>
</html>
