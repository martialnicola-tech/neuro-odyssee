/* ============================================
   LA NEURO-ODYSSÉE — Main JavaScript
   ============================================ */

document.addEventListener('DOMContentLoaded', function() {

  // ============================================
  // NAVBAR: scroll effect
  // ============================================
  const navbar = document.getElementById('navbar');

  function handleNavbarScroll() {
    if (!navbar) return;
    if (window.scrollY > 20) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  }

  window.addEventListener('scroll', handleNavbarScroll, { passive: true });
  handleNavbarScroll();

  // ============================================
  // MOBILE MENU TOGGLE
  // ============================================
  const hamburger = document.getElementById('hamburger');
  const mobileMenu = document.getElementById('mobileMenu');
  const mobileMenuClose = document.getElementById('mobileMenuClose');

  function openMenu() {
    if (mobileMenu) {
      mobileMenu.classList.add('open');
      document.body.style.overflow = 'hidden';
      if (hamburger) hamburger.classList.add('open');
    }
  }

  function closeMenu() {
    if (mobileMenu) {
      mobileMenu.classList.remove('open');
      document.body.style.overflow = '';
      if (hamburger) hamburger.classList.remove('open');
    }
  }

  if (hamburger) hamburger.addEventListener('click', openMenu);
  if (mobileMenuClose) mobileMenuClose.addEventListener('click', closeMenu);

  // Close menu on ESC
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeMenu();
  });

  // Close on mobile link click
  document.querySelectorAll('.mobile-nav-link').forEach(link => {
    link.addEventListener('click', closeMenu);
  });

  // ============================================
  // ACTIVE NAV LINK (based on current page)
  // ============================================
  const currentPage = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-link[data-page]').forEach(link => {
    if (link.getAttribute('data-page') === currentPage) {
      link.classList.add('active');
    }
  });

  // ============================================
  // SMOOTH SCROLL for anchor links
  // ============================================
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      const targetId = this.getAttribute('href').slice(1);
      const target = document.getElementById(targetId);
      if (target) {
        e.preventDefault();
        const navHeight = navbar ? navbar.offsetHeight : 80;
        const top = target.getBoundingClientRect().top + window.scrollY - navHeight - 20;
        window.scrollTo({ top, behavior: 'smooth' });
        closeMenu();
      }
    });
  });

  // ============================================
  // INTERSECTION OBSERVER — reveal animations
  // ============================================
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

  document.querySelectorAll('.reveal, .reveal-left, .reveal-right').forEach(el => {
    revealObserver.observe(el);
  });

  // ============================================
  // ANIMATED COUNTERS
  // ============================================
  function animateCounter(el, target, suffix, duration) {
    const start = performance.now();
    const isFloat = String(target).includes('.');
    const decimals = isFloat ? String(target).split('.')[1].length : 0;

    function update(now) {
      const elapsed = now - start;
      const progress = Math.min(elapsed / duration, 1);
      // Ease out cubic
      const eased = 1 - Math.pow(1 - progress, 3);
      const current = target * eased;
      el.textContent = (isFloat ? current.toFixed(decimals) : Math.round(current)).toLocaleString('fr-FR') + (suffix || '');
      if (progress < 1) requestAnimationFrame(update);
    }

    requestAnimationFrame(update);
  }

  const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const el = entry.target;
        const target = parseFloat(el.getAttribute('data-target') || '0');
        const suffix = el.getAttribute('data-suffix') || '';
        const duration = parseInt(el.getAttribute('data-duration') || '2000');
        animateCounter(el, target, suffix, duration);
        counterObserver.unobserve(el);
      }
    });
  }, { threshold: 0.5 });

  document.querySelectorAll('[data-target]').forEach(el => {
    counterObserver.observe(el);
  });

  // ============================================
  // PROGRESS BAR animation on scroll
  // ============================================
  const progressBars = document.querySelectorAll('.progress-bar[data-progress]');

  const progressObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const bar = entry.target;
        const progress = bar.getAttribute('data-progress') || '0';
        setTimeout(() => {
          bar.style.width = progress + '%';
        }, 200);
        progressObserver.unobserve(bar);
      }
    });
  }, { threshold: 0.3 });

  progressBars.forEach(bar => {
    bar.style.width = '0%';
    progressObserver.observe(bar);
  });

  // ============================================
  // FAQ ACCORDION
  // ============================================
  document.querySelectorAll('.faq-question').forEach(btn => {
    btn.addEventListener('click', function() {
      const item = this.closest('.faq-item');
      const isOpen = item.classList.contains('open');

      // Close all
      document.querySelectorAll('.faq-item.open').forEach(openItem => {
        openItem.classList.remove('open');
      });

      // Open this one if it was closed
      if (!isOpen) {
        item.classList.add('open');
      }
    });
  });

  // ============================================
  // SPONSORING: KM CALCULATOR
  // ============================================
  const kmSlider = document.getElementById('kmSlider');
  const kmInput = document.getElementById('kmInput');
  const kmPrice = document.getElementById('kmPrice');
  const kmTierBadge = document.getElementById('kmTierBadge');
  const kmTierDesc = document.getElementById('kmTierDesc');

  function updateKmCalculator(km) {
    km = Math.max(1, Math.min(1600, parseInt(km) || 1));
    const price = km * 10;

    if (kmPrice) kmPrice.textContent = price.toLocaleString('fr-FR') + ' €';

    // Update slider gradient
    if (kmSlider) {
      const pct = (km / 1600) * 100;
      kmSlider.style.background = `linear-gradient(to right, var(--green-mid) 0%, var(--green-mid) ${pct}%, rgba(45,140,122,0.15) ${pct}%, rgba(45,140,122,0.15) 100%)`;
    }

    // Determine tier
    let tier = '', tierClass = '', desc = '';
    if (km <= 50) {
      tier = 'Bronze';
      tierClass = 'bronze';
      desc = 'Logo sur le site + mention dans les vidéos YouTube';
    } else if (km <= 200) {
      tier = 'Argent';
      tierClass = 'silver';
      desc = 'Logo sur le site, mention vidéos + logo dans le documentaire + mention dans le livre';
    } else if (km <= 500) {
      tier = 'Or';
      tierClass = 'gold';
      desc = 'Logo premium, entretien exclusif, dédicace personnalisée + tous les avantages Argent';
    } else {
      tier = 'Platine';
      tierClass = 'platinum';
      desc = 'Partenaire officiel, co-branding, invitation à la conférence + tous les avantages Or';
    }

    if (kmTierBadge) {
      kmTierBadge.textContent = tier;
      kmTierBadge.className = `tier-color font-bold text-lg ${tierClass}`;
    }
    if (kmTierDesc) kmTierDesc.textContent = desc;

    // Highlight corresponding tier card
    document.querySelectorAll('.sponsor-tier').forEach(card => {
      card.style.outline = 'none';
    });
    const tierCard = document.querySelector(`.sponsor-tier.${tierClass}`);
    if (tierCard) {
      tierCard.style.outline = '3px solid var(--gold)';
      tierCard.style.outlineOffset = '2px';
    }
  }

  if (kmSlider) {
    kmSlider.addEventListener('input', function() {
      if (kmInput) kmInput.value = this.value;
      updateKmCalculator(this.value);
    });
  }

  if (kmInput) {
    kmInput.addEventListener('input', function() {
      if (kmSlider) kmSlider.value = this.value;
      updateKmCalculator(this.value);
    });
  }

  // ============================================
  // DONATION AMOUNT BUTTONS
  // ============================================
  const donationBtns = document.querySelectorAll('.donation-amount-btn');
  const customAmountInput = document.getElementById('customAmount');
  const donationImpact = document.getElementById('donationImpact');

  const impacts = {
    5: 'Un repas chaud en gîte',
    10: 'Une nuit en refuge',
    20: 'Un jour de ravitaillement',
    30: 'Deux nuits en gîte',
    50: 'Un équipement essentiel',
    100: 'Une semaine de marche',
    200: 'Production d\'une vidéo',
    500: 'Un chapitre du documentaire'
  };

  function updateDonationImpact(amount) {
    if (!donationImpact) return;
    const amt = parseInt(amount);
    // Find closest impact threshold
    const thresholds = Object.keys(impacts).map(Number).sort((a,b) => a-b);
    let text = '';
    for (const t of thresholds) {
      if (amt >= t) text = impacts[t];
    }
    if (text) {
      donationImpact.textContent = `= ${text}`;
      donationImpact.style.opacity = '1';
    } else {
      donationImpact.textContent = '';
    }
  }

  donationBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      donationBtns.forEach(b => b.classList.remove('selected'));
      this.classList.add('selected');
      const amount = this.getAttribute('data-amount');
      if (customAmountInput) customAmountInput.value = '';
      updateDonationImpact(amount);
    });
  });

  if (customAmountInput) {
    customAmountInput.addEventListener('input', function() {
      donationBtns.forEach(b => b.classList.remove('selected'));
      updateDonationImpact(this.value);
    });
  }

  // ============================================
  // CONTACT/SPONSORING MODAL
  // ============================================
  const modalOverlay = document.getElementById('contactModal');
  const modalTriggers = document.querySelectorAll('[data-modal="contact"]');
  const modalClose = document.querySelectorAll('.modal-close, [data-modal-close]');

  function openModal(modal) {
    if (modal) {
      modal.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeModal(modal) {
    if (modal) {
      modal.classList.remove('open');
      document.body.style.overflow = '';
    }
  }

  modalTriggers.forEach(trigger => {
    trigger.addEventListener('click', function() {
      const tierName = this.getAttribute('data-tier');
      if (modalOverlay && tierName) {
        const tierInput = modalOverlay.querySelector('[name="tier"]');
        if (tierInput) tierInput.value = tierName;
        const modalTitle = modalOverlay.querySelector('.modal-tier-name');
        if (modalTitle) modalTitle.textContent = tierName;
      }
      openModal(modalOverlay);
    });
  });

  modalClose.forEach(btn => {
    btn.addEventListener('click', function() {
      const modal = this.closest('.modal-overlay');
      closeModal(modal);
    });
  });

  // Close on backdrop click
  if (modalOverlay) {
    modalOverlay.addEventListener('click', function(e) {
      if (e.target === this) closeModal(this);
    });
  }

  // ============================================
  // BLOG FILTER TAGS
  // ============================================
  const filterBtns = document.querySelectorAll('.filter-btn');
  const blogCards = document.querySelectorAll('.blog-card[data-tag]');

  filterBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      filterBtns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');

      const filter = this.getAttribute('data-filter');

      blogCards.forEach(card => {
        if (filter === 'tous' || card.getAttribute('data-tag') === filter) {
          card.style.display = '';
          setTimeout(() => { card.style.opacity = '1'; card.style.transform = 'translateY(0)'; }, 10);
        } else {
          card.style.opacity = '0';
          card.style.transform = 'translateY(10px)';
          setTimeout(() => { card.style.display = 'none'; }, 300);
        }
      });
    });
  });

  // ============================================
  // CURRENT YEAR in footer
  // ============================================
  document.querySelectorAll('.current-year').forEach(el => {
    el.textContent = new Date().getFullYear();
  });

  // ============================================
  // NEWSLETTER FORM
  // ============================================
  const newsletterForms = document.querySelectorAll('.newsletter-form-el');
  newsletterForms.forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      const input = form.querySelector('input[type="email"]');
      const btn = form.querySelector('button');
      if (!input || !input.value) return;

      if (btn) {
        btn.textContent = 'Merci !';
        btn.disabled = true;
        btn.style.background = 'var(--green-mid)';
      }
      input.value = '';
      setTimeout(() => {
        if (btn) {
          btn.textContent = "S'inscrire";
          btn.disabled = false;
          btn.style.background = '';
        }
      }, 3000);
    });
  });

  // ============================================
  // CONTACT FORM (sponsoring / soutenir) — PHP Hostinger
  // ============================================
  const MAIL_ENDPOINT = 'send-mail.php';

  document.querySelectorAll('.contact-form-el').forEach(form => {
    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      const btn = form.querySelector('[type="submit"]');
      const originalHTML = btn ? btn.innerHTML : '';

      if (btn) {
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Envoi...';
        btn.disabled = true;
      }

      const data = new FormData(form);

      try {
        const res = await fetch(MAIL_ENDPOINT, {
          method: 'POST',
          body: data
        });

        const json = await res.json();

        if (json.ok) {
          if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Message envoyé !';
            btn.style.background = 'var(--green-mid)';
          }
          form.reset();
          setTimeout(() => {
            if (btn) {
              btn.innerHTML = originalHTML;
              btn.disabled = false;
              btn.style.background = '';
            }
          }, 5000);
        } else {
          throw new Error(json.error || 'Erreur serveur');
        }
      } catch (err) {
        if (btn) {
          btn.innerHTML = '<i class="fa-solid fa-exclamation-triangle"></i> Erreur — réessayez';
          btn.disabled = false;
          setTimeout(() => { btn.innerHTML = originalHTML; }, 4000);
        }
      }
    });
  });

  // ============================================
  // SHARE BUTTONS
  // ============================================
  document.querySelectorAll('[data-share]').forEach(btn => {
    btn.addEventListener('click', function() {
      const platform = this.getAttribute('data-share');
      const url = encodeURIComponent(window.location.origin + '/');
      const text = encodeURIComponent('La Neuro-Odyssée — 1 600 km pour se reconstruire. Découvrez ce projet inspirant !');

      const shareUrls = {
        facebook: `https://www.facebook.com/sharer/sharer.php?u=${url}`,
        twitter: `https://twitter.com/intent/tweet?url=${url}&text=${text}`,
        linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${url}`,
        whatsapp: `https://wa.me/?text=${text}%20${url}`
      };

      if (shareUrls[platform]) {
        window.open(shareUrls[platform], '_blank', 'width=600,height=400,noopener');
      }
    });
  });

  // ============================================
  // IMPACT CALCULATOR (soutenir.html)
  // ============================================
  const impactInput = document.getElementById('impactAmount');
  const impactDisplay = document.getElementById('impactDisplay');

  const impactItems = [
    { min: 5, max: 9, icon: '🍜', text: '1 repas chaud en gîte' },
    { min: 10, max: 19, icon: '🛏️', text: '1 nuit en hébergement de pèlerin' },
    { min: 20, max: 29, icon: '🎒', text: '1 jour de ravitaillement complet' },
    { min: 30, max: 49, icon: '🏕️', text: '2 nuits en gîte + ravitaillement' },
    { min: 50, max: 99, icon: '🥾', text: 'Un équipement essentiel (chaussettes, pansements...)' },
    { min: 100, max: 199, icon: '📱', text: '1 semaine de communication et partage de contenu' },
    { min: 200, max: 499, icon: '🎬', text: 'Production d\'une vidéo du documentaire' },
    { min: 500, max: 999, icon: '📖', text: 'La rédaction d\'un chapitre du livre' },
    { min: 1000, max: Infinity, icon: '🌟', text: 'Sponsor d\'une étape complète — impact exceptionnel !' }
  ];

  function updateImpactDisplay(amount) {
    if (!impactDisplay) return;
    const amt = parseInt(amount) || 0;
    if (amt < 5) {
      impactDisplay.innerHTML = `<span style="color:var(--text-light)">Entrez un montant pour voir l'impact</span>`;
      return;
    }
    const item = impactItems.find(i => amt >= i.min && amt <= i.max);
    if (item) {
      impactDisplay.innerHTML = `
        <span style="font-size:1.5rem;">${item.icon}</span>
        <div>
          <div style="font-weight:600; color:var(--green-deep);">${amt}€ = ${item.text}</div>
          <div style="font-size:0.8rem; color:var(--text-light); margin-top:0.2rem;">Merci pour votre geste</div>
        </div>
      `;
    }
  }

  if (impactInput) {
    impactInput.addEventListener('input', function() {
      updateImpactDisplay(this.value);
    });
    updateImpactDisplay(20); // Default display
  }

  // ============================================
  // KM ADOPTÉS TRACKER (données à mettre à jour manuellement)
  // ============================================
  const KM_ADOPTES = 0;       // ← Mettre à jour quand un sponsor confirme
  const NB_PARTENAIRES = 0;   // ← Nombre de partenaires confirmés

  const kmBar = document.getElementById('kmBar');
  const kmAdoptesEl = document.getElementById('kmAdoptes');
  const kmRestantsEl = document.getElementById('kmRestants');
  const nbPartenairesEl = document.getElementById('nbPartenaires');

  if (kmBar) {
    setTimeout(() => {
      const pct = (KM_ADOPTES / 1600) * 100;
      kmBar.style.width = pct + '%';
      if (kmAdoptesEl) kmAdoptesEl.textContent = KM_ADOPTES.toLocaleString('fr-CH');
      if (kmRestantsEl) kmRestantsEl.textContent = (1600 - KM_ADOPTES).toLocaleString('fr-CH');
      if (nbPartenairesEl) nbPartenairesEl.textContent = NB_PARTENAIRES;
    }, 400);
  }

  // ============================================
  // COMPTEUR JOURS AVANT LE DÉPART
  // ============================================
  const countdownEl = document.getElementById('countdown-days');
  if (countdownEl) {
    const depart = new Date('2026-08-21T00:00:00');
    const aujourd_hui = new Date();
    aujourd_hui.setHours(0, 0, 0, 0);
    const diff = Math.ceil((depart - aujourd_hui) / (1000 * 60 * 60 * 24));
    if (diff > 0) {
      countdownEl.textContent = diff.toLocaleString('fr-CH');
    } else if (diff === 0) {
      countdownEl.closest('#countdown').innerHTML =
        '<i class="fa-solid fa-person-hiking" style="color:var(--gold);"></i>' +
        '<span style="color:rgba(255,255,255,0.9); font-size:0.9rem; font-weight:600;">C\'est le grand départ !</span>';
    } else {
      countdownEl.closest('#countdown').innerHTML =
        '<i class="fa-solid fa-person-hiking" style="color:var(--gold);"></i>' +
        '<span style="color:rgba(255,255,255,0.9); font-size:0.9rem; font-weight:600;">L\'aventure est en cours !</span>';
    }
  }

  // ============================================
  // STAGE CURSOR (trajet.html)
  // ============================================
  const currentKmEl = document.getElementById('currentKm');
  const currentStageEl = document.getElementById('currentStage');

  if (currentKmEl) {
    // Animate the counter
    const target = parseInt(currentKmEl.getAttribute('data-km') || '0');
    if (target > 0) {
      let start = null;
      function animateKm(ts) {
        if (!start) start = ts;
        const progress = Math.min((ts - start) / 2000, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        currentKmEl.textContent = Math.round(target * eased).toLocaleString('fr-FR');
        if (progress < 1) requestAnimationFrame(animateKm);
      }
      requestAnimationFrame(animateKm);
    }
  }

});
