/* 3R Urban — Portal JS
   Handles: Chart.js charts, sparklines, alert acknowledge, sidebar */

'use strict';

// ── Sidebar toggle ──────────────────────────────────────────
function truToggleSidebar() {
  document.getElementById('truSidebar')?.classList.toggle('open');
}
document.addEventListener('click', function(e) {
  const sidebar = document.getElementById('truSidebar');
  if (!sidebar) return;
  if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && !e.target.closest('.tru-topbar-menu')) {
    sidebar.classList.remove('open');
  }
});

// ── Alert acknowledge ───────────────────────────────────────
async function truAcknowledge(btn) {
  const alertId = btn.dataset.id;
  if (!alertId) return;
  btn.disabled = true;
  btn.textContent = '…';

  try {
    const res = await fetch(`${truPortal.restBase}3r/v1/alerts/${alertId}/acknowledge`, {
      method: 'POST',
      headers: { 'X-WP-Nonce': truPortal.nonce, 'Content-Type': 'application/json' },
    });
    const data = await res.json();
    if (data.success) {
      const row = btn.closest('[data-alert-id]');
      if (row) {
        row.style.opacity = '0.5';
        row.style.transition = 'opacity .3s';
        btn.textContent = '✓ Done';

        // Update status pill if in table
        const pill = row.querySelector('.tru-status-pill');
        if (pill) { pill.textContent = 'Acknowledged'; pill.className = 'tru-status-pill tru-status-acknowledged'; }

        // Update sidebar badge count
        const badge = document.querySelector('.tru-sidebar-badge');
        if (badge) {
          const current = parseInt(badge.textContent) || 0;
          if (current > 1) badge.textContent = current - 1;
          else badge.remove();
        }
        // Bell badge
        const bell = document.querySelector('.tru-bell-badge');
        if (bell) {
          const c = parseInt(bell.textContent) || 0;
          if (c > 1) bell.textContent = c - 1; else bell.remove();
        }
      }
    } else {
      btn.textContent = 'Retry';
      btn.disabled = false;
    }
  } catch(err) {
    console.error('Ack error:', err);
    btn.textContent = 'Retry';
    btn.disabled = false;
  }
}

// Delegate click on all ack buttons
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('tru-ack-btn') && !e.target.disabled) {
    truAcknowledge(e.target);
  }
});

// Acknowledge All Active button
document.getElementById('truAckAll')?.addEventListener('click', function() {
  document.querySelectorAll('.tru-ack-btn:not(:disabled)').forEach(btn => truAcknowledge(btn));
});

// ── Charts (machine detail page) ───────────────────────────
function truInitCharts() {
  if (typeof Chart === 'undefined' || !window.truChartData) return;

  const cd = window.truChartData;
  const grid  = { color: 'rgba(255,255,255,.05)', drawBorder: false };
  const ticks = { color: '#4a6a4a', font: { family: "'DM Mono', monospace", size: 10 }, maxRotation: 0, maxTicksLimit: 8 };

  function baseOptions(yLabel) {
    return {
      responsive: true,
      animation: { duration: 800, easing: 'easeOutQuart' },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#0d130d', borderColor: '#243824', borderWidth: 1,
          titleColor: '#4a6a4a', bodyColor: '#dceadc',
          titleFont: { family: "'DM Mono', monospace", size: 11 },
          bodyFont:  { family: "'Syne', sans-serif",   size: 13 },
          callbacks: { title: items => items[0].label }
        }
      },
      scales: {
        x: { grid, ticks },
        y: { grid, ticks: { ...ticks }, title: { display: true, text: yLabel, color: '#4a6a4a', font: { family: "'DM Mono', monospace", size: 10 } } }
      }
    };
  }

  // Temperature chart
  const tempCtx = document.getElementById('tempChart');
  if (tempCtx) {
    new Chart(tempCtx, {
      type: 'line',
      data: {
        labels: cd.labels,
        datasets: [{
          data: cd.temp,
          borderColor: '#5dd862', backgroundColor: 'rgba(93,216,98,.08)',
          borderWidth: 2, pointRadius: 0, tension: 0.4, fill: true,
        }, {
          // Threshold line at 70°C
          data: cd.labels.map(() => 70),
          borderColor: 'rgba(245,166,35,.5)', borderDash: [4,4],
          borderWidth: 1.5, pointRadius: 0, fill: false,
          label: '70°C threshold',
        }],
      },
      options: { ...baseOptions('°C'), plugins: { ...baseOptions().plugins, tooltip: { ...baseOptions().plugins?.tooltip } } },
    });
  }

  // Weight chart
  const wCtx = document.getElementById('weightChart');
  if (wCtx) {
    new Chart(wCtx, {
      type: 'bar',
      data: {
        labels: cd.labels,
        datasets: [{
          data: cd.weight,
          backgroundColor: 'rgba(93,216,98,.25)',
          borderColor: '#5dd862', borderWidth: 1, borderRadius: 2,
        }],
      },
      options: baseOptions('kg'),
    });
  }

  // RPM chart
  const rCtx = document.getElementById('rpmChart');
  if (rCtx) {
    new Chart(rCtx, {
      type: 'line',
      data: {
        labels: cd.labels,
        datasets: [{
          data: cd.rpm,
          borderColor: '#c8a96e', backgroundColor: 'rgba(200,169,110,.08)',
          borderWidth: 2, pointRadius: 0, tension: 0.4, fill: true,
        }, {
          data: cd.labels.map(() => 38),
          borderColor: 'rgba(200,169,110,.4)', borderDash: [4,4],
          borderWidth: 1, pointRadius: 0, fill: false,
        }, {
          data: cd.labels.map(() => 52),
          borderColor: 'rgba(200,169,110,.4)', borderDash: [4,4],
          borderWidth: 1, pointRadius: 0, fill: false,
        }],
      },
      options: baseOptions('rpm'),
    });
  }
}

// ── Sparklines on fleet cards ───────────────────────────────
function truInitSparklines() {
  document.querySelectorAll('.tru-fleet-spark').forEach(el => {
    const uid = el.dataset.uid;
    if (!uid) return;

    // Generate demo sparkline data (replace with API call when live)
    const points = 24;
    const base   = uid.includes('002') ? 72 : 58;
    const vals   = Array.from({length: points}, () => base + (Math.random() - 0.5) * 12);
    const min    = Math.min(...vals);
    const max    = Math.max(...vals);
    const range  = max - min || 1;
    const w      = el.offsetWidth || 280;
    const h      = 32;
    const step   = w / (points - 1);

    const pts = vals.map((v, i) => {
      const x = i * step;
      const y = h - ((v - min) / range) * (h - 4) - 2;
      return `${x},${y}`;
    }).join(' ');

    const color = uid.includes('002') ? '#f5a623' : '#5dd862';
    el.innerHTML = `
      <svg width="100%" height="${h}" viewBox="0 0 ${w} ${h}" preserveAspectRatio="none">
        <polyline points="${pts}" fill="none" stroke="${color}" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round" opacity=".8"/>
      </svg>`;
  });
}

// ── Init on DOM ready ───────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
  truInitSparklines();
  truInitCharts();
});

/* ── Live Panel polling (read-only GET) ─────────────────────────────────── */
(function () {
  var root = document.querySelector('.tru-panel');
  if (!root || typeof truPortal === 'undefined') return;
  var uid = root.getAttribute('data-uid');
  function set(id, v) { var e = document.getElementById(id); if (e && v !== undefined) e.textContent = v; }
  function pill(el, on, text, bad) {
    el.textContent = text;
    el.className = 'tru-pill ' + (bad ? 'bad' : on ? 'on' : 'off');
  }
  function refresh() {
    fetch(truPortal.restBase + '3r/v1/machines/' + encodeURIComponent(uid) + '/panel', {
      headers: { 'X-WP-Nonce': truPortal.nonce }, credentials: 'same-origin'
    }).then(function (r) { return r.ok ? r.json() : null; }).then(function (d) {
      if (!d) return;
      set('pTemp', d.live.temperature_c);
      set('pKwh', Number(d.live.last_cycle_kwh).toFixed(3));
      set('pWaste', d.live.waste_added_kg);
      set('pCycle', d.live.cycle_time);
      set('pUpdated', d.updated_at);
      var h = document.getElementById('pHealth');
      h.textContent = d.status.label;
      h.className = 'tru-panel-health ' + (d.status.healthy ? 'ok' : 'bad');
      root.querySelectorAll('[data-out]').forEach(function (el) {
        var v = String(d.outputs[el.getAttribute('data-out')] || '');
        pill(el, v === 'ON' || v === 'OPEN' || v === 'FWD' || v === 'REV', v, false);
      });
      root.querySelectorAll('[data-alarm]').forEach(function (el) {
        var a = !!d.alarms[el.getAttribute('data-alarm')];
        pill(el, false, a ? 'ACTIVE' : 'OK', a);
      });
      var demo = document.getElementById('truPanelDemo');
      if (demo && !d.demo) demo.style.display = 'none';
    }).catch(function () {});
  }
  setInterval(refresh, 30000);
})();
