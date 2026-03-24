/* ============================================
   LA NEURO-ODYSSÉE — i18n System
   ============================================ */

const I18N = {
  supported: ['fr', 'en', 'es', 'it', 'de'],
  default: 'fr',
  current: 'fr',
  translations: {},

  /**
   * Initialize: detect stored language or browser language, load translations
   */
  async init() {
    const stored = localStorage.getItem('neuro-lang');
    const browser = navigator.language ? navigator.language.split('-')[0] : null;
    const lang = stored || (this.supported.includes(browser) ? browser : this.default);
    await this.setLanguage(lang);
    this.setupLangButtons();
  },

  /**
   * Load a locale JSON file
   */
  async loadLocale(lang) {
    if (this.translations[lang]) return this.translations[lang];
    try {
      // Detect base path for locale files (works from any page depth)
      const scriptTag = document.querySelector('script[src*="i18n.js"]');
      let base = '';
      if (scriptTag) {
        const src = scriptTag.getAttribute('src');
        base = src.replace(/js\/i18n\.js.*/, '');
      }
      const res = await fetch(`${base}locales/${lang}.json`);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      this.translations[lang] = data;
      return data;
    } catch (err) {
      console.warn(`[i18n] Could not load locale "${lang}":`, err);
      if (lang !== this.default) {
        return this.loadLocale(this.default);
      }
      return {};
    }
  },

  /**
   * Set the active language and re-render the page
   */
  async setLanguage(lang) {
    if (!this.supported.includes(lang)) lang = this.default;
    this.current = lang;
    localStorage.setItem('neuro-lang', lang);
    await this.loadLocale(lang);
    document.documentElement.lang = lang;
    this.translatePage();
    this.updateLangButtons();
    document.dispatchEvent(new CustomEvent('languageChanged', { detail: { lang } }));
  },

  /**
   * Get a translation value by dot-notation key
   */
  t(key, lang) {
    lang = lang || this.current;
    const data = this.translations[lang] || this.translations[this.default] || {};
    const parts = key.split('.');
    let value = data;
    for (const part of parts) {
      if (value && typeof value === 'object' && part in value) {
        value = value[part];
      } else {
        // Fallback to default language
        if (lang !== this.default && this.translations[this.default]) {
          return this.t(key, this.default);
        }
        return key; // Return key as last resort
      }
    }
    return typeof value === 'string' ? value : key;
  },

  /**
   * Translate all elements with data-i18n attributes
   */
  translatePage() {
    // Text content
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      const translation = this.t(key);
      if (translation !== key) {
        el.textContent = translation;
      }
    });

    // Placeholder attributes
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
      const key = el.getAttribute('data-i18n-placeholder');
      const translation = this.t(key);
      if (translation !== key) {
        el.setAttribute('placeholder', translation);
      }
    });

    // HTML content (for rich text)
    document.querySelectorAll('[data-i18n-html]').forEach(el => {
      const key = el.getAttribute('data-i18n-html');
      const translation = this.t(key);
      if (translation !== key) {
        el.innerHTML = translation;
      }
    });

    // Title/aria attributes
    document.querySelectorAll('[data-i18n-title]').forEach(el => {
      const key = el.getAttribute('data-i18n-title');
      const translation = this.t(key);
      if (translation !== key) {
        el.setAttribute('title', translation);
      }
    });

    // Page title
    const titleKey = document.querySelector('meta[name="i18n-title"]');
    if (titleKey) {
      const key = titleKey.getAttribute('content');
      const translation = this.t(key);
      if (translation !== key) {
        document.title = translation + ' — La Neuro-Odyssée';
      }
    }
  },

  /**
   * Set up language button click handlers
   */
  setupLangButtons() {
    document.querySelectorAll('[data-lang]').forEach(btn => {
      btn.addEventListener('click', () => {
        const lang = btn.getAttribute('data-lang');
        this.setLanguage(lang);
      });
    });
  },

  /**
   * Update active state on language buttons
   */
  updateLangButtons() {
    document.querySelectorAll('[data-lang]').forEach(btn => {
      const lang = btn.getAttribute('data-lang');
      btn.classList.toggle('active', lang === this.current);
    });
  }
};

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => I18N.init());
} else {
  I18N.init();
}

// Export for use in other scripts
window.I18N = I18N;
