(function () {
  'use strict';

  var section = document.getElementById('dashboard-reports');
  if (!section) return;

  var tabs       = section.querySelectorAll('.report-tab');
  var output     = document.getElementById('reportOutput');
  var monthInput = document.getElementById('reportMonth');
  var exportBtn  = document.getElementById('reportExportBtn');

  var currentReport = 'population';
  var lastLoaded    = false;

  /* ============================================================
     HELPERS
     ============================================================ */

  function esc(v) {
    var d = document.createElement('div');
    d.textContent = String(v == null ? '' : v);
    return d.innerHTML;
  }

  function num(v) {
    var n = Number(v);
    return isNaN(n) ? 0 : n;
  }

  function api(action, params) {
    var url = 'index.php?route=report&action=' + encodeURIComponent(action);
    if (params) {
      Object.keys(params).forEach(function (k) {
        if (params[k] !== '' && params[k] !== null && params[k] !== undefined) {
          url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
        }
      });
    }
    return fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) {
        return r.json().then(function (data) {
          if (!r.ok || !data.success) throw new Error(data.message || 'Error');
          return data;
        });
      });
  }

  function showLoading() {
    output.innerHTML = '<div class="data-table__empty">Loading…</div>';
  }

  function showError(msg) {
    output.innerHTML = '<div class="data-table__empty">' + esc(msg) + '</div>';
  }

  function formatMonthLabel(yyyymm) {
    if (!yyyymm) return '';
    var parts = yyyymm.split('-');
    if (parts.length !== 2) return yyyymm;
    var d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, 1);
    if (isNaN(d.getTime())) return yyyymm;
    return d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
  }

  function formatDayLabel(iso) {
    if (!iso) return '';
    var d = new Date(iso);
    if (isNaN(d.getTime())) return iso;
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
  }

  /* ============================================================
     SHARED LAYOUT PIECES
     ============================================================ */

  function renderReportHeader(meta) {
    if (!meta) return '';
    return '<header class="report-header">' +
      '<h2 class="report-header__title">' + esc(meta.title) + '</h2>' +
      '<p class="report-header__subtitle">' + esc(meta.facility) + ' · ' + esc(meta.subtitle) + '</p>' +
      '<p class="report-header__meta">Generated: ' + esc(meta.generated) + '</p>' +
    '</header>';
  }

  function renderReportFooter(percents) {
    if (!percents || !percents.items || !percents.items.length || !percents.total) return '';

    var parts = percents.items.map(function (p) {
      return p.percentage.toFixed(2) + '% ' + p.classification;
    });

    return '<footer class="report-footer">' +
      '<span class="report-footer__label">Percentage:</span> ' +
      '<span class="report-footer__value">' + esc(parts.join(' · ')) + '</span>' +
    '</footer>';
  }

  /* ============================================================
     RENDERERS
     ============================================================ */

  function renderPopulation(data) {
    var rows     = data.rows     || [];
    var totals   = data.totals   || { male: 0, female: 0, total: 0 };
    var percents = data.percents || null;

    var html = renderReportHeader(data.meta);

    if (!rows.length) {
      output.innerHTML = html +
        '<div class="data-table__empty">No in-custody PDL records yet.</div>';
      return;
    }

    html +=
      '<div class="table-wrap report-table__wrap">' +
      '<table class="report-table">' +
        '<thead>' +
          '<tr>' +
            '<th rowspan="2">Classification</th>' +
            '<th colspan="2" class="report-table__group">Sex</th>' +
            '<th rowspan="2" class="ta-right">Total</th>' +
          '</tr>' +
          '<tr>' +
            '<th class="ta-right">Male</th>' +
            '<th class="ta-right">Female</th>' +
          '</tr>' +
        '</thead>' +
        '<tbody>';

    rows.forEach(function (r) {
      html +=
        '<tr>' +
          '<td>' + esc(r.classification) + '</td>' +
          '<td class="ta-right">' + num(r.male) + '</td>' +
          '<td class="ta-right">' + num(r.female) + '</td>' +
          '<td class="ta-right"><strong>' + num(r.total) + '</strong></td>' +
        '</tr>';
    });

    html +=
        '</tbody>' +
        '<tfoot>' +
          '<tr>' +
            '<td><strong>TOTAL</strong></td>' +
            '<td class="ta-right"><strong>' + num(totals.male) + '</strong></td>' +
            '<td class="ta-right"><strong>' + num(totals.female) + '</strong></td>' +
            '<td class="ta-right"><strong>' + num(totals.total) + '</strong></td>' +
          '</tr>' +
        '</tfoot>' +
      '</table>' +
      '</div>';

    html += renderReportFooter(percents);
    output.innerHTML = html;
  }

  function renderFlow(data) {
    var rows   = data.rows   || [];
    var totals = data.totals || {};
    var month  = data.month  || '';

    var html = renderReportHeader(data.meta);

    if (!rows.length) {
      output.innerHTML = html +
        '<div class="data-table__empty">No commits or releases for ' +
        esc(formatMonthLabel(month)) + '.</div>';
      return;
    }

    html +=
      '<div class="table-wrap report-table__wrap">' +
      '<table class="report-table">' +
        '<thead>' +
          '<tr>' +
            '<th rowspan="2">Date</th>' +
            '<th colspan="3" class="report-table__group">Committed</th>' +
            '<th colspan="3" class="report-table__group">Released</th>' +
          '</tr>' +
          '<tr>' +
            '<th class="ta-right">Male</th>' +
            '<th class="ta-right">Female</th>' +
            '<th class="ta-right">Total</th>' +
            '<th class="ta-right">Male</th>' +
            '<th class="ta-right">Female</th>' +
            '<th class="ta-right">Total</th>' +
          '</tr>' +
        '</thead>' +
        '<tbody>';

    rows.forEach(function (r) {
      html +=
        '<tr>' +
          '<td>' + esc(formatDayLabel(r.day)) + '</td>' +
          '<td class="ta-right">' + num(r.committed_male) + '</td>' +
          '<td class="ta-right">' + num(r.committed_female) + '</td>' +
          '<td class="ta-right"><strong>' + num(r.committed_total) + '</strong></td>' +
          '<td class="ta-right">' + num(r.released_male) + '</td>' +
          '<td class="ta-right">' + num(r.released_female) + '</td>' +
          '<td class="ta-right"><strong>' + num(r.released_total) + '</strong></td>' +
        '</tr>';
    });

    html +=
        '</tbody>' +
        '<tfoot>' +
          '<tr>' +
            '<td><strong>TOTAL</strong></td>' +
            '<td class="ta-right"><strong>' + num(totals.committed_male) + '</strong></td>' +
            '<td class="ta-right"><strong>' + num(totals.committed_female) + '</strong></td>' +
            '<td class="ta-right"><strong>' + num(totals.committed_total) + '</strong></td>' +
            '<td class="ta-right"><strong>' + num(totals.released_male) + '</strong></td>' +
            '<td class="ta-right"><strong>' + num(totals.released_female) + '</strong></td>' +
            '<td class="ta-right"><strong>' + num(totals.released_total) + '</strong></td>' +
          '</tr>' +
        '</tfoot>' +
      '</table>' +
      '</div>';

    output.innerHTML = html;
  }

  function renderDrugCases(data) {
    var rows   = data.rows   || [];
    var totals = data.totals || { male: 0, female: 0, total: 0 };

    var html = renderReportHeader(data.meta);

    if (!rows.length) {
      output.innerHTML = html +
        '<div class="data-table__empty">No drug-related PDL records.</div>';
      return;
    }

    html +=
      '<div class="table-wrap report-table__wrap">' +
      '<table class="report-table">' +
        '<thead>' +
          '<tr>' +
            '<th rowspan="2">Classification</th>' +
            '<th colspan="2" class="report-table__group">Sex</th>' +
            '<th rowspan="2" class="ta-right">Total</th>' +
          '</tr>' +
          '<tr>' +
            '<th class="ta-right">Male</th>' +
            '<th class="ta-right">Female</th>' +
          '</tr>' +
        '</thead>' +
        '<tbody>';

    rows.forEach(function (r) {
      html +=
        '<tr>' +
          '<td>' + esc(r.classification) + '</td>' +
          '<td class="ta-right">' + num(r.male) + '</td>' +
          '<td class="ta-right">' + num(r.female) + '</td>' +
          '<td class="ta-right"><strong>' + num(r.total) + '</strong></td>' +
        '</tr>';
    });

    html +=
        '</tbody>' +
        '<tfoot>' +
          '<tr>' +
            '<td><strong>TOTAL</strong></td>' +
            '<td class="ta-right"><strong>' + num(totals.male) + '</strong></td>' +
            '<td class="ta-right"><strong>' + num(totals.female) + '</strong></td>' +
            '<td class="ta-right"><strong>' + num(totals.total) + '</strong></td>' +
          '</tr>' +
        '</tfoot>' +
      '</table>' +
      '</div>';

    output.innerHTML = html;
  }

  /* ============================================================
     LOAD
     ============================================================ */

  function load() {
    var action = currentReport;
    var params = {};

    if (currentReport === 'flow') {
      params.month = monthInput ? monthInput.value : '';
    }

    if (monthInput) {
      monthInput.hidden = (currentReport !== 'flow');
    }

    showLoading();

    api(action, params).then(function (data) {
      if (currentReport === 'population')      renderPopulation(data);
      else if (currentReport === 'flow')       renderFlow(data);
      else if (currentReport === 'drug-cases') renderDrugCases(data);
      lastLoaded = true;
    }).catch(function (err) {
      showError(err.message);
    });
  }

  /* ============================================================
     TABS
     ============================================================ */

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      tabs.forEach(function (t) { t.classList.remove('is-active'); });
      tab.classList.add('is-active');

      currentReport = tab.dataset.report || 'population';
      load();
    });
  });

  /* ============================================================
     MONTH PICKER
     ============================================================ */

  if (monthInput) {
    monthInput.addEventListener('change', function () {
      if (currentReport === 'flow') load();
    });
  }

  /* ============================================================
     EXPORT
     ============================================================ */

  if (exportBtn) {
    exportBtn.addEventListener('click', function () {
      var params = new URLSearchParams();
      params.set('report', currentReport);
      if (currentReport === 'flow' && monthInput) {
        params.set('month', monthInput.value);
      }
      window.location.href = 'index.php?route=report&action=export&' + params.toString();
    });
  }

  /* ============================================================
     SECTION ENTRY / EVENT LISTENERS
     ============================================================ */

  document.addEventListener('section:changed', function (e) {
    if (e.detail && e.detail.section === 'reports') {
      if (!lastLoaded) load();
    }
  });

  if (section.classList.contains('is-visible')) {
    load();
  }

  if (monthInput && !monthInput.value) {
    var now = new Date();
    var mm  = String(now.getMonth() + 1).padStart(2, '0');
    monthInput.value = now.getFullYear() + '-' + mm;
  }
})();