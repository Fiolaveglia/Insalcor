(() => {
  const MONTHS_ES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

  let noticiaQuill = null;
  let productoQuill = null;
  let currentUser = null;

  function assetUrl(path) {
    if (!path) return '';
    if (/^https?:\/\//i.test(path)) return path;
    return '../' + path.replace(/^\//, '');
  }

  function formatDate(iso) {
    if (!iso) return '—';
    const d = new Date(iso.includes('T') ? iso : iso.replace(' ', 'T') + 'Z');
    if (Number.isNaN(d.getTime())) return iso;
    return `${d.getUTCDate()} ${MONTHS_ES[d.getUTCMonth()]} ${d.getUTCFullYear()}`;
  }

  function badge(estado) {
    const cls = estado === 'published' ? 'badge-published' : 'badge-draft';
    const label = estado === 'published' ? 'Publicado' : 'Borrador';
    return `<span class="badge ${cls}">${label}</span>`;
  }

  function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
  }
  function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
  }

  function ensureQuills() {
    if (!noticiaQuill) {
      noticiaQuill = new Quill('#noticia-contenido-editor', {
        theme: 'snow',
        placeholder: 'Contenido completo...',
        modules: { toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link'], ['clean']] },
      });
    }
    if (!productoQuill) {
      productoQuill = new Quill('#producto-descripcion-editor', {
        theme: 'snow',
        placeholder: 'Descripción del producto...',
        modules: { toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link'], ['clean']] },
      });
    }
  }

  function setImagePreview(previewId, url) {
    const img = document.getElementById(previewId);
    if (url) {
      img.src = assetUrl(url);
      img.classList.remove('hidden');
    } else {
      img.removeAttribute('src');
      img.classList.add('hidden');
    }
  }

  function wireDropzone(dropzoneId, fileInputId, hiddenId, previewId) {
    const drop = document.getElementById(dropzoneId);
    const input = document.getElementById(fileInputId);
    drop.addEventListener('click', () => input.click());
    drop.addEventListener('dragover', (e) => { e.preventDefault(); });
    drop.addEventListener('drop', async (e) => {
      e.preventDefault();
      const file = e.dataTransfer.files?.[0];
      if (file) await uploadTo(file, hiddenId, previewId);
    });
    input.addEventListener('change', async () => {
      const file = input.files?.[0];
      if (file) await uploadTo(file, hiddenId, previewId);
    });
  }

  async function uploadTo(file, hiddenId, previewId) {
    try {
      const res = await AdminAPI.upload(file);
      document.getElementById(hiddenId).value = res.url;
      setImagePreview(previewId, res.url);
    } catch (err) {
      alert(err.message);
    }
  }

  function especieBoxes() {
    return Array.from(document.querySelectorAll('#producto-especies input[name="especie"]'));
  }

  function getEspecies() {
    return especieBoxes().filter((b) => b.checked).map((b) => b.value);
  }

  function setEspecies(list) {
    const selected = new Set(list || []);
    especieBoxes().forEach((b) => { b.checked = selected.has(b.value); });
  }

  function toggleEspecie() {
    const area = document.getElementById('producto-area').value;
    const show = area === 'Nutricion Animal';
    document.getElementById('producto-especie-wrap').classList.toggle('hidden', !show);
    if (!show) setEspecies([]);
  }

  /* -------- Noticias -------- */
  async function loadNoticias() {
    const q = document.getElementById('noticias-search').value.trim();
    const data = await AdminAPI.listNoticias(q);
    const tbody = document.getElementById('noticias-tbody');
    const empty = document.getElementById('noticias-empty');
    document.getElementById('noticias-count').textContent = data.total;
    tbody.innerHTML = '';
    if (!data.items.length) {
      empty.classList.remove('hidden');
      return;
    }
    empty.classList.add('hidden');
    data.items.forEach((item) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>
          <div class="item-cell">
            <img class="item-thumb" src="${assetUrl(item.imagen) || 'data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22/>'}" alt="">
            <div>
              <strong>${escapeHtml(item.titulo)}</strong>
              <div class="sub">${escapeHtml(item.extracto)}</div>
            </div>
          </div>
        </td>
        <td>${escapeHtml(item.autor_username || 'Admin')}</td>
        <td>${formatDate(item.published_at || item.created_at)}</td>
        <td>${badge(item.estado)}</td>
        <td>
          <div class="actions">
            <button class="icon-btn" data-edit-noticia="${item.id}" type="button" title="Editar"><i class="fa-solid fa-pen"></i></button>
            <button class="icon-btn danger" data-del-noticia="${item.id}" type="button" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
          </div>
        </td>`;
      tbody.appendChild(tr);
    });
  }

  function resetNoticiaForm() {
    ensureQuills();
    document.getElementById('noticia-id').value = '';
    document.getElementById('noticia-titulo').value = '';
    document.getElementById('noticia-extracto').value = '';
    document.getElementById('noticia-categoria').value = '';
    document.getElementById('noticia-imagen').value = '';
    noticiaQuill.setContents([]);
    setImagePreview('noticia-preview', '');
    document.getElementById('modal-noticia-title').textContent = 'Nueva Noticia';
  }

  async function openNoticiaEditor(id) {
    ensureQuills();
    resetNoticiaForm();
    if (id) {
      const { item } = await AdminAPI.getNoticia(id);
      document.getElementById('modal-noticia-title').textContent = 'Editar Noticia';
      document.getElementById('noticia-id').value = item.id;
      document.getElementById('noticia-titulo').value = item.titulo;
      document.getElementById('noticia-extracto').value = item.extracto;
      document.getElementById('noticia-categoria').value = item.categoria;
      document.getElementById('noticia-imagen').value = item.imagen || '';
      setImagePreview('noticia-preview', item.imagen);
      noticiaQuill.root.innerHTML = item.contenido || '';
    }
    openModal('modal-noticia');
  }

  async function saveNoticia(estado) {
    const id = document.getElementById('noticia-id').value;
    const payload = {
      titulo: document.getElementById('noticia-titulo').value.trim(),
      extracto: document.getElementById('noticia-extracto').value.trim(),
      categoria: document.getElementById('noticia-categoria').value.trim(),
      imagen: document.getElementById('noticia-imagen').value,
      contenido: noticiaQuill.root.innerHTML,
      estado,
    };
    if (!payload.titulo) {
      alert('El título es obligatorio');
      return;
    }
    try {
      await AdminAPI.saveNoticia(payload, id || null);
      closeModal('modal-noticia');
      await loadNoticias();
    } catch (err) {
      alert(err.message);
    }
  }

  /* -------- Productos -------- */
  async function loadProductos() {
    const q = document.getElementById('productos-search').value.trim();
    const data = await AdminAPI.listProductos(q);
    const tbody = document.getElementById('productos-tbody');
    const empty = document.getElementById('productos-empty');
    document.getElementById('productos-count').textContent = data.total;
    tbody.innerHTML = '';
    if (!data.items.length) {
      empty.classList.remove('hidden');
      return;
    }
    empty.classList.add('hidden');
    data.items.forEach((item) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>
          <div class="item-cell">
            <img class="item-thumb" src="${assetUrl(item.imagen) || ''}" alt="">
            <div>
              <strong>${escapeHtml(item.nombre)}</strong>
              <div class="sub">${escapeHtml(item.marca || '')}</div>
            </div>
          </div>
        </td>
        <td>${escapeHtml(item.area_negocio)}</td>
        <td>${escapeHtml(item.categoria || '—')}</td>
        <td>${escapeHtml((item.especies || []).join(', ') || '—')}</td>
        <td>${badge(item.estado)}</td>
        <td>
          <div class="actions">
            <button class="icon-btn" data-edit-producto="${item.id}" type="button" title="Editar"><i class="fa-solid fa-pen"></i></button>
            <button class="icon-btn danger" data-del-producto="${item.id}" type="button" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
          </div>
        </td>`;
      tbody.appendChild(tr);
    });
  }

  function resetProductoForm() {
    ensureQuills();
    document.getElementById('producto-id').value = '';
    document.getElementById('producto-nombre').value = '';
    document.getElementById('producto-area').value = 'Nutricion Animal';
    document.getElementById('producto-categoria').value = '';
    document.getElementById('producto-marca').value = '';
    setEspecies([]);
    document.getElementById('producto-imagen').value = '';
    document.getElementById('producto-ficha').value = '';
    document.getElementById('producto-ficha-file').value = '';
    productoQuill.setContents([]);
    setImagePreview('producto-preview', '');
    toggleEspecie();
    document.getElementById('modal-producto-title').textContent = 'Nuevo Producto';
  }

  async function openProductoEditor(id) {
    ensureQuills();
    resetProductoForm();
    if (id) {
      const { item } = await AdminAPI.getProducto(id);
      document.getElementById('modal-producto-title').textContent = 'Editar Producto';
      document.getElementById('producto-id').value = item.id;
      document.getElementById('producto-nombre').value = item.nombre;
      document.getElementById('producto-area').value = item.area_negocio;
      document.getElementById('producto-categoria').value = item.categoria || '';
      document.getElementById('producto-marca').value = item.marca || '';
      setEspecies(item.especies);
      document.getElementById('producto-imagen').value = item.imagen || '';
      document.getElementById('producto-ficha').value = item.ficha_tecnica || '';
      setImagePreview('producto-preview', item.imagen);
      productoQuill.root.innerHTML = item.descripcion || '';
      toggleEspecie();
    }
    openModal('modal-producto');
  }

  async function saveProducto(estado) {
    const id = document.getElementById('producto-id').value;
    const area = document.getElementById('producto-area').value;
    const payload = {
      nombre: document.getElementById('producto-nombre').value.trim(),
      area_negocio: area,
      categoria: document.getElementById('producto-categoria').value,
      marca: document.getElementById('producto-marca').value,
      especies: area === 'Nutricion Animal' ? getEspecies() : [],
      imagen: document.getElementById('producto-imagen').value,
      ficha_tecnica: document.getElementById('producto-ficha').value.trim(),
      descripcion: productoQuill.root.innerHTML,
      estado,
    };
    if (!payload.nombre) {
      alert('El nombre es obligatorio');
      return;
    }
    try {
      await AdminAPI.saveProducto(payload, id || null);
      closeModal('modal-producto');
      await loadProductos();
    } catch (err) {
      alert(err.message);
    }
  }

  function escapeHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function showSection(name) {
    document.querySelectorAll('.nav-item').forEach((el) => {
      el.classList.toggle('active', el.dataset.section === name);
    });
    document.getElementById('section-noticias').classList.toggle('hidden', name !== 'noticias');
    document.getElementById('section-productos').classList.toggle('hidden', name !== 'productos');
    document.getElementById('page-title').textContent =
      name === 'noticias' ? 'Gestión de Noticias' : 'Gestión de Productos';
    if (name === 'noticias') loadNoticias();
    else loadProductos();
  }

  async function init() {
    try {
      const me = await AdminAPI.me();
      if (!me.user) {
        location.href = 'login.php';
        return;
      }
      currentUser = me.user;
      document.getElementById('user-username').textContent = currentUser.username;
      document.getElementById('app').style.display = 'flex';
    } catch {
      location.href = 'login.php';
      return;
    }

    ensureQuills();
    wireDropzone('noticia-dropzone', 'noticia-file', 'noticia-imagen', 'noticia-preview');
    wireDropzone('producto-dropzone', 'producto-file', 'producto-imagen', 'producto-preview');

    document.getElementById('producto-ficha-file').addEventListener('change', async (e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      try {
        const res = await AdminAPI.upload(file);
        document.getElementById('producto-ficha').value = res.url;
      } catch (err) {
        alert(err.message);
      }
    });

    document.querySelectorAll('.nav-item').forEach((btn) => {
      btn.addEventListener('click', () => showSection(btn.dataset.section));
    });

    document.getElementById('btn-logout').addEventListener('click', async () => {
      await AdminAPI.logout();
      location.href = 'login.php';
    });

    document.querySelectorAll('[data-close]').forEach((btn) => {
      btn.addEventListener('click', () => closeModal(btn.dataset.close));
    });

    document.getElementById('btn-new-noticia').addEventListener('click', () => openNoticiaEditor(null));
    document.getElementById('btn-save-noticia-draft').addEventListener('click', () => saveNoticia('draft'));
    document.getElementById('btn-save-noticia-publish').addEventListener('click', () => saveNoticia('published'));
    document.getElementById('noticias-search').addEventListener('input', debounce(loadNoticias, 250));

    document.getElementById('btn-new-producto').addEventListener('click', () => openProductoEditor(null));
    document.getElementById('btn-save-producto-draft').addEventListener('click', () => saveProducto('draft'));
    document.getElementById('btn-save-producto-publish').addEventListener('click', () => saveProducto('published'));
    document.getElementById('productos-search').addEventListener('input', debounce(loadProductos, 250));
    document.getElementById('producto-area').addEventListener('change', toggleEspecie);

    document.getElementById('noticias-tbody').addEventListener('click', async (e) => {
      const edit = e.target.closest('[data-edit-noticia]');
      const del = e.target.closest('[data-del-noticia]');
      if (edit) openNoticiaEditor(edit.dataset.editNoticia);
      if (del && confirm('¿Eliminar esta noticia?')) {
        await AdminAPI.deleteNoticia(del.dataset.delNoticia);
        loadNoticias();
      }
    });

    document.getElementById('productos-tbody').addEventListener('click', async (e) => {
      const edit = e.target.closest('[data-edit-producto]');
      const del = e.target.closest('[data-del-producto]');
      if (edit) openProductoEditor(edit.dataset.editProducto);
      if (del && confirm('¿Eliminar este producto?')) {
        await AdminAPI.deleteProducto(del.dataset.delProducto);
        loadProductos();
      }
    });

    showSection('noticias');
  }

  function debounce(fn, ms) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), ms);
    };
  }

  init();
})();
