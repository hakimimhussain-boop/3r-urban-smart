/**
 * 3R Urban Smart Solutions — Public Website JS
 * Plugin: 3r-urban-smart | Version: 1.0.0
 */
(function () {
  'use strict';

  // ── Scroll nav effect ────────────────────────────────────────
  const nav = document.getElementById('truNav');
  if (nav) {
    window.addEventListener('scroll', function () {
      nav.classList.toggle('scrolled', window.scrollY > 20);
    });
  }

  // ── Mobile menu ──────────────────────────────────────────────
  window.truToggleMenu = function () {
    const links = document.getElementById('truNavLinks');
    if (!links) return;
    const isOpen = links.style.display === 'flex';
    if (isOpen) {
      links.style.display = '';
      nav && nav.classList.remove('mobile-open');
    } else {
      links.style.cssText =
        'display:flex;flex-direction:column;position:absolute;top:60px;left:0;right:0;' +
        'background:rgba(8,12,8,0.97);border-bottom:1px solid #1c2b1c;' +
        'padding:14px;gap:4px;backdrop-filter:blur(20px);z-index:999;';
      nav && nav.classList.add('mobile-open');
    }
  };

  // ── Hero chart bars ──────────────────────────────────────────
  const chartWrap = document.getElementById('truHeroChart');
  if (chartWrap) {
    const temps  = [52,55,54,57,60,62,61,58,56,54,55,58,60,61,59,58,56,57,59,61,62,60,58,58];
    const maxVal = Math.max(...temps);
    temps.forEach(function (t, i) {
      const bar = document.createElement('div');
      bar.className = 'tru-chart-bar';
      bar.style.height = Math.round((t / maxVal) * 100) + '%';
      bar.style.animationDelay = (i * 0.03) + 's';
      bar.title = t + '°C';
      chartWrap.appendChild(bar);
    });
  }

  // ── Marquee ──────────────────────────────────────────────────
  const marqueeTrack = document.getElementById('truMarquee');
  if (marqueeTrack) {
    const items = [
      'Real-Time PLC Monitoring', 'MQTT over TLS', 'Teltonika RUT200 Native',
      'Temperature Tracking', 'Load Monitoring', 'Motor Status',
      'Threshold Alerts', 'Role-Based Access', 'PDF Reports',
      'Multi-Machine Fleet', 'Node.js Middleware', 'MySQL Storage',
      'Chart.js Dashboards', 'WP REST API',
    ];
    // Duplicate for seamless loop
    [...items, ...items].forEach(function (text) {
      const div = document.createElement('div');
      div.className = 'tru-marquee-item';
      div.innerHTML = '<span class="tru-marquee-dot"></span>' + text;
      marqueeTrack.appendChild(div);
    });
  }

  // ── Intersection observer for scroll animations ──────────────
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          e.target.style.animation = 'truFadeUp 0.55s ease forwards';
          observer.unobserve(e.target);
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll(
      '.tru-feature-card, .tru-step, .tru-mcard, .tru-team-card, .tru-value-item, .tru-about-stat'
    ).forEach(function (el) {
      el.style.opacity = '0';
      observer.observe(el);
    });
  }

  // ── AJAX form helper ─────────────────────────────────────────
  function submitForm(formEl, successEl, errorEl, submitBtn) {
    submitBtn.disabled = true;
    const label   = submitBtn.querySelector('.tru-btn-label');
    const loading = submitBtn.querySelector('.tru-btn-loading');
    if (label)   label.style.display   = 'none';
    if (loading) loading.style.display = '';

    const data = new FormData(formEl);
    // Grab nonce from hidden field if present
    const nonceField = formEl.querySelector('[name="3r_nonce"]');
    if (nonceField) data.set('nonce', nonceField.value);

    fetch(
      (typeof tru3rUrban !== 'undefined' && tru3rUrban.ajaxUrl)
        ? tru3rUrban.ajaxUrl
        : '/wp-admin/admin-ajax.php',
      { method: 'POST', body: data }
    )
      .then(function (r) { return r.json(); })
      .then(function (json) {
        if (json.success) {
          formEl.style.display = 'none';
          successEl.style.display = '';
        } else {
          const msg = (json.data && json.data.message) ? json.data.message : 'Something went wrong. Please try again.';
          errorEl.textContent = msg;
          errorEl.style.display = '';
          submitBtn.disabled = false;
          if (label)   label.style.display   = '';
          if (loading) loading.style.display  = 'none';
        }
      })
      .catch(function () {
        errorEl.textContent = 'Network error. Please check your connection and try again.';
        errorEl.style.display = '';
        submitBtn.disabled = false;
        if (label)   label.style.display   = '';
        if (loading) loading.style.display  = 'none';
      });
  }

  // ── Demo form ────────────────────────────────────────────────
  const demoForm = document.getElementById('truDemoForm');
  if (demoForm) {
    demoForm.addEventListener('submit', function (e) {
      e.preventDefault();
      submitForm(
        demoForm,
        document.getElementById('truDemoSuccess'),
        document.getElementById('truDemoError'),
        document.getElementById('truDemoSubmit')
      );
    });
  }

  // ── Contact form ─────────────────────────────────────────────
  const contactForm = document.getElementById('truContactForm');
  if (contactForm) {
    contactForm.addEventListener('submit', function (e) {
      e.preventDefault();
      submitForm(
        contactForm,
        document.getElementById('truContactSuccess'),
        document.getElementById('truContactError'),
        document.getElementById('truContactSubmit')
      );
    });
  }

})();

// ── Portal dropdown ──────────────────────────────────────────
function truTogglePortalMenu() {
  document.getElementById('truPortalDrop')?.classList.toggle('open');
}
// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
  const portal = document.getElementById('truNavPortal');
  if ( portal && !portal.contains(e.target) ) {
    document.getElementById('truPortalDrop')?.classList.remove('open');
  }
});
