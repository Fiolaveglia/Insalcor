(() => {
  const MONTHS_ES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

  let noticiaQuill = null;
  let productoQuill = null;
  let noticiaQuillEn = null;
  let productoQuillEn = null;
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

  /**
   * Deshabilita los botones Guardar/Publicar de un formulario y les cambia
   * el texto mientras se guarda — el guardado ahora puede tardar un par de
   * segundos extra por la traducción automática al inglés.
   */
  function setSavingState(prefix, saving) {
    const draftBtn = document.getElementById(`btn-save-${prefix}-draft`);
    const publishBtn = document.getElementById(`btn-save-${prefix}-publish`);
    [draftBtn, publishBtn].forEach((btn) => { btn.disabled = saving; });
    if (saving) {
      draftBtn.dataset.originalText = draftBtn.dataset.originalText || draftBtn.textContent;
      publishBtn.dataset.originalText = publishBtn.dataset.originalText || publishBtn.textContent;
      draftBtn.textContent = 'Guardando...';
      publishBtn.textContent = 'Guardando...';
    } else {
      if (draftBtn.dataset.originalText) draftBtn.textContent = draftBtn.dataset.originalText;
      if (publishBtn.dataset.originalText) publishBtn.textContent = publishBtn.dataset.originalText;
    }
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
    if (!noticiaQuillEn) {
      noticiaQuillEn = new Quill('#noticia-contenido-en-editor', {
        theme: 'snow',
        placeholder: 'Full content...',
        modules: { toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link'], ['clean']] },
      });
    }
    if (!productoQuillEn) {
      productoQuillEn = new Quill('#producto-descripcion-en-editor', {
        theme: 'snow',
        placeholder: 'Product description...',
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
    document.getElementById('noticia-titulo-en').value = '';
    document.getElementById('noticia-extracto').value = '';
    document.getElementById('noticia-extracto-en').value = '';
    document.getElementById('noticia-categoria').value = '';
    document.getElementById('noticia-imagen').value = '';
    noticiaQuill.setContents([]);
    noticiaQuillEn.setContents([]);
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
      document.getElementById('noticia-titulo-en').value = item.titulo_en || '';
      document.getElementById('noticia-extracto').value = item.extracto;
      document.getElementById('noticia-extracto-en').value = item.extracto_en || '';
      document.getElementById('noticia-categoria').value = item.categoria;
      document.getElementById('noticia-imagen').value = item.imagen || '';
      setImagePreview('noticia-preview', item.imagen);
      noticiaQuill.root.innerHTML = item.contenido || '';
      noticiaQuillEn.root.innerHTML = item.contenido_en || '';
    }
    openModal('modal-noticia');
  }

  async function saveNoticia(estado) {
    const id = document.getElementById('noticia-id').value;
    const payload = {
      titulo: document.getElementById('noticia-titulo').value.trim(),
      titulo_en: document.getElementById('noticia-titulo-en').value.trim(),
      extracto: document.getElementById('noticia-extracto').value.trim(),
      extracto_en: document.getElementById('noticia-extracto-en').value.trim(),
      categoria: document.getElementById('noticia-categoria').value.trim(),
      imagen: document.getElementById('noticia-imagen').value,
      contenido: noticiaQuill.root.innerHTML,
      contenido_en: noticiaQuillEn.root.innerHTML,
      estado,
    };
    if (!payload.titulo) {
      alert('El título es obligatorio');
      return;
    }
    try {
      setSavingState('noticia', true);
      await AdminAPI.saveNoticia(payload, id || null);
      closeModal('modal-noticia');
      await loadNoticias();
    } catch (err) {
      alert(err.message);
    } finally {
      setSavingState('noticia', false);
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
            </div>
          </div>
        </td>
        <td>${escapeHtml(item.area_negocio)}</td>
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
    document.getElementById('producto-nombre-en').value = '';
    document.getElementById('producto-area').value = 'Nutricion Animal';
    setEspecies([]);
    document.getElementById('producto-imagen').value = '';
    document.getElementById('producto-ficha').value = '';
    document.getElementById('producto-ficha-file').value = '';
    productoQuill.setContents([]);
    productoQuillEn.setContents([]);
    setImagePreview('producto-preview', '');
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
      document.getElementById('producto-nombre-en').value = item.nombre_en || '';
      document.getElementById('producto-area').value = item.area_negocio;
      setEspecies(item.especies);
      document.getElementById('producto-imagen').value = item.imagen || '';
      document.getElementById('producto-ficha').value = item.ficha_tecnica || '';
      setImagePreview('producto-preview', item.imagen);
      productoQuill.root.innerHTML = item.descripcion || '';
      productoQuillEn.root.innerHTML = item.descripcion_en || '';
    }
    openModal('modal-producto');
  }

  async function saveProducto(estado) {
    const id = document.getElementById('producto-id').value;
    const area = document.getElementById('producto-area').value;
    const payload = {
      nombre: document.getElementById('producto-nombre').value.trim(),
      nombre_en: document.getElementById('producto-nombre-en').value.trim(),
      area_negocio: area,
      especies: getEspecies(),
      imagen: document.getElementById('producto-imagen').value,
      ficha_tecnica: document.getElementById('producto-ficha').value.trim(),
      descripcion: productoQuill.root.innerHTML,
      descripcion_en: productoQuillEn.root.innerHTML,
      estado,
    };
    if (!payload.nombre) {
      alert('El nombre es obligatorio');
      return;
    }
    try {
      setSavingState('producto', true);
      await AdminAPI.saveProducto(payload, id || null);
      closeModal('modal-producto');
      await loadProductos();
    } catch (err) {
      alert(err.message);
    } finally {
      setSavingState('producto', false);
    }
  }

  /* -------- Tutoriales -------- */

  /** Extrae el ID de YouTube de cualquier formato de URL usual (mismo criterio que el backend). */
  function extraerYoutubeId(url) {
    url = (url || '').trim();
    if (!url) return null;
    const patrones = [
      /youtu\.be\/([A-Za-z0-9_-]{11})/,
      /[?&]v=([A-Za-z0-9_-]{11})/,
      /youtube\.com\/embed\/([A-Za-z0-9_-]{11})/,
      /youtube\.com\/shorts\/([A-Za-z0-9_-]{11})/,
    ];
    for (const p of patrones) {
      const m = url.match(p);
      if (m) return m[1];
    }
    if (/^[A-Za-z0-9_-]{11}$/.test(url)) return url;
    return null;
  }

  function updateTutorialPreview() {
    const url = document.getElementById('tutorial-youtube-url').value;
    const ytId = extraerYoutubeId(url);
    const wrap = document.getElementById('tutorial-preview-wrap');
    const img = document.getElementById('tutorial-preview');
    if (ytId) {
      img.src = `https://img.youtube.com/vi/${ytId}/hqdefault.jpg`;
      wrap.style.display = '';
    } else {
      img.src = '';
      wrap.style.display = 'none';
    }
  }

  async function loadTutoriales() {
    const q = document.getElementById('tutoriales-search').value.trim();
    const data = await AdminAPI.listTutoriales(q);
    document.getElementById('tutoriales-count').textContent = data.total;
    const tbody = document.getElementById('tutoriales-tbody');
    const empty = document.getElementById('tutoriales-empty');
    tbody.innerHTML = '';
    if (!data.items.length) {
      empty.classList.remove('hidden');
      return;
    }
    empty.classList.add('hidden');
    data.items.forEach((item) => {
      const tr = document.createElement('tr');
      const thumb = item.youtube_id ? `https://img.youtube.com/vi/${item.youtube_id}/default.jpg` : '';
      tr.innerHTML = `
        <td><img class="item-thumb" src="${thumb}" alt=""></td>
        <td><strong>${escapeHtml(item.titulo)}</strong></td>
        <td>${item.youtube_url ? `<a href="${escapeHtml(item.youtube_url)}" target="_blank" rel="noopener">Ver en YouTube</a>` : '—'}</td>
        <td>${formatDate(item.published_at || item.created_at)}</td>
        <td>${badge(item.estado)}</td>
        <td>
          <div class="actions">
            <button class="icon-btn" data-edit-tutorial="${item.id}" type="button" title="Editar"><i class="fa-solid fa-pen"></i></button>
            <button class="icon-btn danger" data-del-tutorial="${item.id}" type="button" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
          </div>
        </td>`;
      tbody.appendChild(tr);
    });
  }

  function resetTutorialForm() {
    document.getElementById('tutorial-id').value = '';
    document.getElementById('tutorial-titulo').value = '';
    document.getElementById('tutorial-titulo-en').value = '';
    document.getElementById('tutorial-youtube-url').value = '';
    document.getElementById('tutorial-descripcion').value = '';
    document.getElementById('tutorial-descripcion-en').value = '';
    updateTutorialPreview();
    document.getElementById('modal-tutorial-title').textContent = 'Nuevo Tutorial';
  }

  async function openTutorialEditor(id) {
    resetTutorialForm();
    if (id) {
      const { item } = await AdminAPI.getTutorial(id);
      document.getElementById('modal-tutorial-title').textContent = 'Editar Tutorial';
      document.getElementById('tutorial-id').value = item.id;
      document.getElementById('tutorial-titulo').value = item.titulo;
      document.getElementById('tutorial-titulo-en').value = item.titulo_en || '';
      document.getElementById('tutorial-youtube-url').value = item.youtube_url || '';
      document.getElementById('tutorial-descripcion').value = item.descripcion || '';
      document.getElementById('tutorial-descripcion-en').value = item.descripcion_en || '';
      updateTutorialPreview();
    }
    openModal('modal-tutorial');
  }

  async function saveTutorial(estado) {
    const id = document.getElementById('tutorial-id').value;
    const payload = {
      titulo: document.getElementById('tutorial-titulo').value.trim(),
      titulo_en: document.getElementById('tutorial-titulo-en').value.trim(),
      youtube_url: document.getElementById('tutorial-youtube-url').value.trim(),
      descripcion: document.getElementById('tutorial-descripcion').value.trim(),
      descripcion_en: document.getElementById('tutorial-descripcion-en').value.trim(),
      estado,
    };
    if (!payload.titulo) {
      alert('El título es obligatorio');
      return;
    }
    if (!payload.youtube_url) {
      alert('La URL de YouTube es obligatoria');
      return;
    }
    try {
      setSavingState('tutorial', true);
      await AdminAPI.saveTutorial(payload, id || null);
      closeModal('modal-tutorial');
      await loadTutoriales();
    } catch (err) {
      alert(err.message);
    } finally {
      setSavingState('tutorial', false);
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
    document.getElementById('section-tutoriales').classList.toggle('hidden', name !== 'tutoriales');
    const titulos = {
      noticias: 'Gestión de Noticias',
      productos: 'Gestión de Productos',
      tutoriales: 'Gestión de Tutoriales',
    };
    document.getElementById('page-title').textContent = titulos[name] || '';
    if (name === 'noticias') loadNoticias();
    else if (name === 'productos') loadProductos();
    else loadTutoriales();
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

    document.getElementById('btn-new-tutorial').addEventListener('click', () => openTutorialEditor(null));
    document.getElementById('btn-save-tutorial-draft').addEventListener('click', () => saveTutorial('draft'));
    document.getElementById('btn-save-tutorial-publish').addEventListener('click', () => saveTutorial('published'));
    document.getElementById('tutoriales-search').addEventListener('input', debounce(loadTutoriales, 250));
    document.getElementById('tutorial-youtube-url').addEventListener('input', debounce(updateTutorialPreview, 300));

    document.getElementById('tutoriales-tbody').addEventListener('click', async (e) => {
      const edit = e.target.closest('[data-edit-tutorial]');
      const del = e.target.closest('[data-del-tutorial]');
      if (edit) openTutorialEditor(edit.dataset.editTutorial);
      if (del && confirm('¿Eliminar este tutorial?')) {
        await AdminAPI.deleteTutorial(del.dataset.delTutorial);
        loadTutoriales();
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
