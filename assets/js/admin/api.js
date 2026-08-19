/**
 * Shared fetch helpers for Insalcor admin.
 */
const AdminAPI = (() => {
  const API = '../api';
  let csrfToken = '';

  async function request(url, options = {}) {
    const opts = { credentials: 'same-origin', ...options };
    opts.headers = { ...(opts.headers || {}) };
    if (opts.body && !(opts.body instanceof FormData)) {
      opts.headers['Content-Type'] = 'application/json';
      if (typeof opts.body === 'object') {
        opts.body = JSON.stringify({ ...opts.body, csrf_token: csrfToken });
      }
    } else if (opts.body instanceof FormData) {
      opts.body.append('csrf_token', csrfToken);
    }
    if (csrfToken && opts.method && opts.method !== 'GET') {
      opts.headers['X-CSRF-Token'] = csrfToken;
    }

    const res = await fetch(url, opts);
    const data = await res.json().catch(() => ({}));
    if (data.csrf_token) csrfToken = data.csrf_token;
    if (!res.ok || data.ok === false) {
      throw new Error(data.error || 'Error de servidor');
    }
    return data;
  }

  return {
    getCsrf: () => csrfToken,
    me: () => request(`${API}/auth.php?action=me`),
    login: (body) => request(`${API}/auth.php?action=login`, { method: 'POST', body }),
    register: (body) => request(`${API}/auth.php?action=register`, { method: 'POST', body }),
    logout: () => request(`${API}/auth.php?action=logout`, { method: 'POST', body: {} }),
    listNoticias: (q = '') => request(`${API}/noticias.php?q=${encodeURIComponent(q)}`),
    getNoticia: (id) => request(`${API}/noticias.php?id=${id}`),
    saveNoticia: (payload, id) =>
      request(id ? `${API}/noticias.php?id=${id}` : `${API}/noticias.php`, {
        method: id ? 'PUT' : 'POST',
        body: payload,
      }),
    deleteNoticia: (id) =>
      request(`${API}/noticias.php?id=${id}`, { method: 'DELETE', body: {} }),
    listProductos: (q = '', area = '') => {
      const params = new URLSearchParams();
      if (q) params.set('q', q);
      if (area) params.set('area_negocio', area);
      return request(`${API}/productos.php?${params}`);
    },
    getProducto: (id) => request(`${API}/productos.php?id=${id}`),
    saveProducto: (payload, id) =>
      request(id ? `${API}/productos.php?id=${id}` : `${API}/productos.php`, {
        method: id ? 'PUT' : 'POST',
        body: payload,
      }),
    deleteProducto: (id) =>
      request(`${API}/productos.php?id=${id}`, { method: 'DELETE', body: {} }),
    upload: async (file) => {
      const fd = new FormData();
      fd.append('file', file);
      return request(`${API}/upload.php`, { method: 'POST', body: fd });
    },
  };
})();
