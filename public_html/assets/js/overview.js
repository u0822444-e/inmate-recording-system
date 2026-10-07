(function () {
  'use strict';

  var grid = document.getElementById('dashboardScreen');
  if (!grid) return;

  var overview = document.getElementById('dashboard-overview');
  if (!overview) return;

  /* ============================================================
     HELPERS
     ============================================================ */

  function esc(v) {
    var d = document.createElement('div');
    d.textContent = String(v == null ? '' : v);
    return d.innerHTML;
  }

  function api(action, params) {
    var url = 'index.php?route=dashboard&action=' + encodeURIComponent(action);

    if (params) {
      Object.keys(params).forEach(function (k) {
        url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
      });
    }

    return fetch(url, {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    }).then(function (r) {
      return r.json().then(function (data) {
        if (!r.ok || !data.success) throw new Error(data.message || 'Error');
        return data;
      });
    });
  }

  function relativeTime(iso) {
    var d = new Date(iso);
    if (isNaN(d.getTime())) return '';

    var seconds = Math.floor((Date.now() - d.getTime()) / 1000);
    if (seconds < 60)    return 'just now';
    if (seconds < 3600)  return Math.floor(seconds / 60) + ' min ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' hr ago';
    return Math.floor(seconds / 86400) + ' days ago';
  }

  /* ============================================================
     STATS
     ============================================================ */

  function renderStats(stats) {
    var el;

    /* Admin KPIs */
    if ((el = document.getElementById('statTotalInmates'))) el.textContent = stats.pdl ?? 0;
    if ((el = document.getElementById('statVisitors')))     el.textContent = stats.visitors ?? 0;
    if ((el = document.getElementById('statIncidents')))    el.textContent = stats.incidents ?? 0;
    if ((el = document.getElementById('statUsers')))        el.textContent = stats.users ?? 0;

    /* Staff KPIs */
    if ((el = document.getElementById('statMyEntries')))   el.textContent = stats.my_entries ?? 0;
    if ((el = document.getElementById('statMyHeadcount'))) el.textContent = stats.my_headcount ?? 0;
  }

  function loadStats() {
    api('stats').then(function (data) {
      renderStats(data.stats || {});
    }).catch(function (err) {
      console.error('Failed to load stats:', err);
    });
  }

  /* ============================================================
     UPCOMING
     ============================================================ */

  function renderUpcoming(events) {
    var container = document.getElementById('upcomingEvents');
    if (!container) return;

    if (!events.length) {
      container.innerHTML = '<li class="data-table__empty" style="border:0;">No upcoming events.</li>';
      return;
    }

    container.innerHTML = events.map(function (e) {
      var d = new Date(e.date);
      var day = isNaN(d.getTime()) ? '—' : d.getDate();
      var month = isNaN(d.getTime())
        ? '——'
        : d.toLocaleDateString('en-US', { month: 'short' }).toUpperCase();

      return '<li class="event">' +
        '<span class="event__date"><strong>' + esc(day) + '</strong><small>' + esc(month) + '</small></span>' +
        '<div class="event__body">' +
          '<strong>' + esc(e.title) + '</strong>' +
          '<span>' + esc(e.meta) + '</span>' +
        '</div>' +
      '</li>';
    }).join('');
  }

  function loadUpcoming() {
    api('upcoming', { limit: 3 }).then(function (data) {
      renderUpcoming(data.events || []);
    }).catch(function (err) {
      console.error('Failed to load upcoming:', err);
    });
  }

  /* ============================================================
     ACTIVITY
     ============================================================ */

  function activityIcon(kind) {
    switch (kind) {
      case 'inmate':    return 'bi-person-badge';
      case 'visitor':   return 'bi-person-check';
      case 'incident':  return 'bi-exclamation-diamond';
      case 'user':      return 'bi-person-plus';
      case 'headcount': return 'bi-clipboard2-check';
      default:          return 'bi-clock-history';
    }
  }

  function renderActivity(items) {
    var container = document.getElementById('activityFeed');
    if (!container) return;

    if (!items.length) {
      container.innerHTML = '<li class="data-table__empty" style="border:0;">No activity recorded.</li>';
      return;
    }

    container.innerHTML = items.map(function (a) {
      return '<li class="activity">' +
        '<span class="activity__icon"><i class="bi ' + activityIcon(a.kind) + '"></i></span>' +
        '<div class="activity__body">' +
          '<strong>' + esc(a.title) + '</strong>' +
          '<span>' + esc(a.subtitle) + ' · ' + esc(relativeTime(a.happened_at)) + '</span>' +
        '</div>' +
      '</li>';
    }).join('');
  }

  function loadActivity() {
    api('activity', { limit: 5 }).then(function (data) {
      renderActivity(data.activity || []);
    }).catch(function (err) {
      console.error('Failed to load activity:', err);
    });
  }

  /* ============================================================
     LOAD ALL
     ============================================================ */

  function loadAll() {
    loadStats();
    loadUpcoming();
    loadActivity();
  }

  document.addEventListener('section:changed', function (e) {
    if (e.detail && e.detail.section === 'overview') {
      loadAll();
    }
  });

  if (overview.classList.contains('is-visible')) {
    loadAll();
  }
})();