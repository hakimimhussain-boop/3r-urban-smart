/* 3R Urban Admin JS */
(function() {
  'use strict';

  // ── Sidebar toggle ──────────────────────────────────────────
  window.truAdminToggle = function() {
    document.getElementById('truAdminSidebar').classList.toggle('open');
  };

  // Close sidebar on overlay click (mobile)
  document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('truAdminSidebar');
    if (!sidebar) return;
    if (sidebar.classList.contains('open') &&
        !sidebar.contains(e.target) &&
        !e.target.closest('.tru-admin-hamburger')) {
      sidebar.classList.remove('open');
    }
  });

  // ── Active nav highlight ────────────────────────────────────
  document.querySelectorAll('.tru-admin-nav-link').forEach(function(link) {
    if (link.href === window.location.href.split('?')[0]) {
      link.classList.add('active');
    }
  });

  // ── Flash auto-dismiss ──────────────────────────────────────
  document.querySelectorAll('.tru-admin-flash').forEach(function(el) {
    setTimeout(function() {
      el.style.transition = 'opacity .4s';
      el.style.opacity = '0';
      setTimeout(function() { el.remove(); }, 400);
    }, 5000);
  });

  // ── Range ↔ number sync (thresholds page) ──────────────────
  document.querySelectorAll('.tru-range').forEach(function(range) {
    const id = range.id.replace('Range', 'Val');
    const numInput = document.getElementById(id);
    if (!numInput) return;

    range.addEventListener('input', function() {
      numInput.value = this.value;
      syncRangeTrack(this);
    });
    numInput.addEventListener('input', function() {
      range.value = this.value;
      syncRangeTrack(range);
    });

    syncRangeTrack(range);
  });

  function syncRangeTrack(range) {
    const min = parseFloat(range.min);
    const max = parseFloat(range.max);
    const val = parseFloat(range.value);
    const pct = ((val - min) / (max - min)) * 100;
    const card = range.closest('.tru-threshold-card');
    const color = card && card.classList.contains('tru-tc-warning') ? '#f5a623' : '#e05555';
    range.style.background = `linear-gradient(to right, ${color} ${pct}%, #1c2b1c ${pct}%)`;
  }

})();
