/**
 * Insalcor i18n — loads assets/i18n/{lang}.json and applies to [data-i18n*].
 * DB/business content is never translated here.
 */
const I18n = (() => {
  const COOKIE_KEY = 'insalcor_lang';
  const SUPPORTED = ['es', 'en'];

  function readCookie(name) {
    const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
  }

  function writeCookie(name, value) {
    document.cookie =
      name + '=' + encodeURIComponent(value) + '; path=/; max-age=' + 60 * 60 * 24 * 365 + '; samesite=lax';
  }

  // Language source of truth is shared with the server-rendered .php pages:
  // ?lang= param (a switch just happened) → cookie → default 'es'.
  function initialLang() {
    const param = new URLSearchParams(location.search).get('lang');
    if (param && SUPPORTED.includes(param)) {
      writeCookie(COOKIE_KEY, param);
      return param;
    }
    const cookie = readCookie(COOKIE_KEY);
    return cookie && SUPPORTED.includes(cookie) ? cookie : 'es';
  }

  let lang = initialLang();
  let dict = {};
  let readyPromise = null;

  function basePath() {
    const el = document.querySelector('[data-i18n-base]');
    if (el) return el.getAttribute('data-i18n-base').replace(/\/$/, '');
    const scripts = document.querySelectorAll('script[src*="i18n.js"]');
    if (scripts.length) {
      const src = scripts[scripts.length - 1].getAttribute('src') || '';
      return src.replace(/\/js\/i18n\.js.*$/, '/i18n');
    }
    return 'assets/i18n';
  }

  function t(key, vars) {
    let value = Object.prototype.hasOwnProperty.call(dict, key) ? dict[key] : null;
    if (value == null) value = key;
    if (vars && typeof value === 'string') {
      Object.keys(vars).forEach((k) => {
        value = value.replace(new RegExp(`\\{${k}\\}`, 'g'), String(vars[k]));
      });
    }
    return value;
  }

  function apply(root = document) {
    root.querySelectorAll('[data-i18n]').forEach((el) => {
      const key = el.getAttribute('data-i18n');
      if (key) el.textContent = t(key);
    });
    root.querySelectorAll('[data-i18n-html]').forEach((el) => {
      const key = el.getAttribute('data-i18n-html');
      if (key) el.innerHTML = t(key);
    });
    root.querySelectorAll('[data-i18n-placeholder]').forEach((el) => {
      const key = el.getAttribute('data-i18n-placeholder');
      if (key) el.setAttribute('placeholder', t(key));
    });
    root.querySelectorAll('[data-i18n-aria]').forEach((el) => {
      const key = el.getAttribute('data-i18n-aria');
      if (key) el.setAttribute('aria-label', t(key));
    });

    document.documentElement.lang = lang;
    if (document.body) document.body.dataset.lang = lang;

    document.querySelectorAll('.module-language').forEach((mod) => {
      const selected = mod.querySelector('.selected span');
      const selectedImg = mod.querySelector('.selected img');
      if (selected) selected.textContent = t('lang.name');
      if (selectedImg) {
        const flag = lang === 'en' ? 'en.png' : 'uy.png';
        const src = selectedImg.getAttribute('src') || '';
        selectedImg.setAttribute(
          'src',
          src.includes('/')
            ? src.replace(/[^/]+$/, flag)
            : `assets/images/module-language/${flag}`
        );
      }
      mod.querySelectorAll('[data-set-lang]').forEach((btn) => {
        btn.classList.toggle('is-active-lang', btn.getAttribute('data-set-lang') === lang);
      });
    });

    // Only notify global listeners on full-document applies (avoids render loops)
    if (root === document || root === document.body) {
      document.dispatchEvent(new CustomEvent('i18n:changed', { detail: { lang } }));
    }
  }

  async function load(nextLang) {
    const target = SUPPORTED.includes(nextLang) ? nextLang : 'es';
    const url = `${basePath()}/${target}.json`;
    const res = await fetch(url, { credentials: 'same-origin' });
    if (!res.ok) throw new Error(`i18n load failed: ${url}`);
    dict = await res.json();
    lang = target;
    writeCookie(COOKIE_KEY, lang);
    apply();
    return lang;
  }

  function whenDomReady() {
    if (document.readyState === 'loading') {
      return new Promise((resolve) => {
        document.addEventListener('DOMContentLoaded', resolve, { once: true });
      });
    }
    return Promise.resolve();
  }

  function wireSwitcher() {
    document.querySelectorAll('[data-set-lang]').forEach((el) => {
      el.addEventListener('click', (e) => {
        e.preventDefault();
        const next = el.getAttribute('data-set-lang');
        if (next && next !== lang) setLang(next);
      });
    });
  }

  function ensureInit() {
    if (readyPromise) return readyPromise;
    readyPromise = (async () => {
      await whenDomReady();
      wireSwitcher();
      try {
        await load(lang);
      } catch (err) {
        console.error(err);
        dict = {};
        apply();
      }
      return lang;
    })();
    return readyPromise;
  }

  function setLang(nextLang) {
    readyPromise = (async () => {
      await whenDomReady();
      wireSwitcher();
      try {
        await load(nextLang);
      } catch (err) {
        console.error(err);
      }
      return lang;
    })();
    return readyPromise;
  }

  // Kick off immediately so early callers can await a real load.
  ensureInit();

  return {
    t,
    apply,
    setLang,
    getLang: () => lang,
    ready: () => ensureInit(),
  };
})();
