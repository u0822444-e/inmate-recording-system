(function () {
  'use strict';

  var section = document.getElementById('dashboard-reports');
  if (!section) return;

  var tabs       = section.querySelectorAll('.report-tab');
  var output     = document.getElementById('reportOutput');
  var monthInput = document.getElementById('reportMonth');
  var exportBtn  = document.getElementById('reportExportBtn');

  /* ---------- Drill-down modal refs ---------- */
  var drilldownModal = document.getElementById('reportDrilldownModal');
  var drilldownBody  = document.getElementById('reportDrilldownBody');
  var drilldownTitle = document.getElementById('reportDrilldownTitle');

  var currentReport = 'actual-population';
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
     SHARED LAYOUT
     ============================================================ */

  function renderReportHeader(meta) {
    if (!meta) return '';
    return '<header class="report-header">' +
      '<h2 class="report-header__title">' + esc(meta.title) + '</h2>' +
      '<p class="report-header__subtitle">' + esc(meta.facility) + ' · ' + esc(meta.subtitle) + '</p>' +
      '<p class="report-header__meta">Generated: ' + esc(meta.generated) + '</p>' +
    '</header>';
  }

  function renderFooterText(text) {
    return '<footer class="report-footer">' +
      '<span class="report-footer__label">Percentage:</span> ' +
      '<span class="report-footer__value">' + esc(text) + '</span>' +
    '</footer>';
  }

  /* ============================================================
     MAKE MUNICIPALITY ROWS CLICKABLE
     Call after setting output.innerHTML, only for reports with municipality.
     ============================================================ */

  function makeMunicipalityRowsClickable() {
    if (!output) return;
    var rows = output.querySelectorAll('.report-table tbody tr');
    rows.forEach(function (tr) {
      var firstCell = tr.querySelector('td:first-child');
      if (!firstCell) return;

      // Add a class for styling
      tr.classList.add('report-row--clickable');

      // Add a small caret icon inside the first cell
      var originalText = firstCell.textContent.trim();
      firstCell.innerHTML =
        '<span class="report-row__label">' +
          esc(originalText) +
          '<i class="bi bi-chevron-right report-row__caret" aria-hidden="true"></i>' +
        '</span>';

      tr.addEventListener('click', function () {
        openDrilldown(originalText);
      });
    });
  }

  /* ============================================================
     DRILLDOWN MODAL
     ============================================================ */

  function openDrilldown(municipalityName) {
    if (!drilldownModal || !drilldownBody) return;

    var action = currentReport === 'escorts'
      ? 'escorts-by-barangay'
      : 'actual-population-by-barangay';

    // Set a sensible title while loading
    if (drilldownTitle) {
      drilldownTitle.textContent = 'Barangays of ' + municipalityName;
    }

    drilldownBody.innerHTML = '<div class="data-table__empty">Loading…</div>';
    drilldownModal.classList.remove('is-hidden');
    document.body.classList.add('modal-open');

    var params = { municipality: municipalityName };
    if (action === 'escorts-by-barangay' && monthInput) {
      params.month = monthInput.value;
    }

    api(action, params).then(function (data) {
      if (data.meta && data.meta.title && drilldownTitle) {
        drilldownTitle.textContent = data.meta.title;
      }
      renderBarangayReport(data);
    }).catch(function (err) {
      drilldownBody.innerHTML = '<div class="data-table__empty">' + esc(err.message) + '</div>';
    });
  }

  function closeDrilldown() {
    if (!drilldownModal) return;
    drilldownModal.classList.add('is-hidden');
    document.body.classList.remove('modal-open');
  }

  // Modal only closes via explicit [data-report-drilldown-close] buttons
  if (drilldownModal) {
    drilldownModal.addEventListener('click', function (e) {
      if (e.target.closest('[data-report-drilldown-close]')) closeDrilldown();
    });
  }

  /* ============================================================
     RENDER BARANGAY REPORT (drilldown)
     Reuses the same column structure as the municipality report.
     ============================================================ */

  function renderBarangayReport(data) {
    var rows   = data.rows   || [];
    var totals = data.totals || {};

    var html = renderReportHeader(data.meta);

    if (!rows.length) {
      drilldownBody.innerHTML = html +
        '<div class="data-table__empty">No barangay records for this municipality.</div>';
      return;
    }

    if (currentReport === 'escorts') {
      // Escorts drilldown — same 17-column layout, first col is "Barangay"
      var purposes = [
        { key: 'court',      label: 'Court' },
        { key: 'hospital',   label: 'Hospital' },
        { key: 'drug_exam',  label: 'Drug Exam' },
        { key: 'rehab',      label: 'Rehab' },
        { key: 'inquest',    label: 'Inquest' },
        { key: 'transfer',   label: 'Transfer' },
        { key: 'visitation', label: 'Visitation' },
        { key: 'other',      label: 'Other' },
      ];

      html += '<div class="table-wrap report-table__wrap">' +
        '<table class="report-table report-table--actual"><thead>' +
        '<tr><th rowspan="2">Barangay</th>';
      purposes.forEach(function (p) {
        html += '<th colspan="2">' + esc(p.label) + '</th>';
      });
      html += '<th rowspan="2" class="ta-right">Total</th></tr><tr>';
      purposes.forEach(function () {
        html += '<th>M</th><th>F</th>';
      });
      html += '</tr></thead><tbody>';

      rows.forEach(function (r) {
        html += '<tr><td>' + esc(r.barangay) + '</td>';
        purposes.forEach(function (p) {
          html += '<td class="ta-right">' + num(r[p.key + '_male']) + '</td>' +
                  '<td class="ta-right">' + num(r[p.key + '_female']) + '</td>';
        });
        html += '<td class="ta-right"><strong>' + num(r.total) + '</strong></td></tr>';
      });

      html += '</tbody><tfoot><tr><td><strong>TOTAL</strong></td>';
      purposes.forEach(function (p) {
        html += '<td class="ta-right">' + num(totals[p.key + '_male']) + '</td>' +
                '<td class="ta-right">' + num(totals[p.key + '_female']) + '</td>';
      });
      html += '<td class="ta-right">' + num(totals.total) + '</td></tr></tfoot></table></div>';

      drilldownBody.innerHTML = html;
      return;
    }

    // Default: Actual Population drilldown (barangay version)
    html +=
      '<div class="table-wrap report-table__wrap">' +
      '<table class="report-table report-table--actual">' +
        '<thead>' +
          '<tr>' +
            '<th rowspan="3">Barangay</th>' +
            '<th colspan="2" rowspan="2">Detainee</th>' +
            '<th rowspan="2">Total<br>Detainees</th>' +
            '<th colspan="8">Sentenced</th>' +
            '<th rowspan="2">Total<br>Sentenced</th>' +
            '<th rowspan="2">Grand<br>Total</th>' +
          '</tr>' +
          '<tr>' +
            '<th colspan="2">Municipal</th><th colspan="2">City</th>' +
            '<th colspan="2">Provincial</th><th colspan="2">Insular</th>' +
          '</tr>' +
          '<tr>' +
            '<th>M</th><th>F</th><th>M</th><th>F</th>' +
            '<th>M</th><th>F</th><th>M</th><th>F</th>' +
          '</tr>' +
        '</thead><tbody>';

    rows.forEach(function (r) {
      html += '<tr>' +
        '<td>' + esc(r.barangay) + '</td>' +
        '<td class="ta-right">' + num(r.detainee_male) + '</td>' +
        '<td class="ta-right">' + num(r.detainee_female) + '</td>' +
        '<td class="ta-right"><strong>' + num(r.detainee_total) + '</strong></td>' +
        '<td class="ta-right">' + num(r.municipal_male) + '</td>' +
        '<td class="ta-right">' + num(r.municipal_female) + '</td>' +
        '<td class="ta-right">' + num(r.city_male) + '</td>' +
        '<td class="ta-right">' + num(r.city_female) + '</td>' +
        '<td class="ta-right">' + num(r.provincial_male) + '</td>' +
        '<td class="ta-right">' + num(r.provincial_female) + '</td>' +
        '<td class="ta-right">' + num(r.insular_male) + '</td>' +
        '<td class="ta-right">' + num(r.insular_female) + '</td>' +
        '<td class="ta-right"><strong>' + num(r.sentenced_total) + '</strong></td>' +
        '<td class="ta-right"><strong>' + num(r.grand_total) + '</strong></td>' +
        '</tr>';
    });

    html += '</tbody><tfoot><tr><td><strong>TOTAL</strong></td>' +
      '<td class="ta-right">' + num(totals.detainee_male) + '</td>' +
      '<td class="ta-right">' + num(totals.detainee_female) + '</td>' +
      '<td class="ta-right">' + num(totals.detainee_total) + '</td>' +
      '<td class="ta-right">' + num(totals.municipal_male) + '</td>' +
      '<td class="ta-right">' + num(totals.municipal_female) + '</td>' +
      '<td class="ta-right">' + num(totals.city_male) + '</td>' +
      '<td class="ta-right">' + num(totals.city_female) + '</td>' +
      '<td class="ta-right">' + num(totals.provincial_male) + '</td>' +
      '<td class="ta-right">' + num(totals.provincial_female) + '</td>' +
      '<td class="ta-right">' + num(totals.insular_male) + '</td>' +
      '<td class="ta-right">' + num(totals.insular_female) + '</td>' +
      '<td class="ta-right">' + num(totals.sentenced_total) + '</td>' +
      '<td class="ta-right">' + num(totals.grand_total) + '</td>' +
      '</tr></tfoot></table></div>';

    drilldownBody.innerHTML = html;
  }

  /* ============================================================
     POPULATION — simple
     ============================================================ */

  function renderPopulation(data) {
    var rows   = data.rows   || [];
    var totals = data.totals || { male: 0, female: 0, total: 0 };
    var pct    = data.percents;

    var html = renderReportHeader(data.meta);

    if (!rows.length) {
      output.innerHTML = html + '<div class="data-table__empty">No records.</div>';
      return;
    }

    html +=
      '<div class="table-wrap report-table__wrap"><table class="report-table">' +
        '<thead><tr>' +
          '<th rowspan="2">Classification</th>' +
          '<th colspan="2" class="report-table__group">Sex</th>' +
          '<th rowspan="2" class="ta-right">Total</th>' +
        '</tr><tr>' +
          '<th class="ta-right">Male</th><th class="ta-right">Female</th>' +
        '</tr></thead><tbody>';

    rows.forEach(function (r) {
      html += '<tr><td>' + esc(r.classification) + '</td>' +
        '<td class="ta-right">' + num(r.male) + '</td>' +
        '<td class="ta-right">' + num(r.female) + '</td>' +
        '<td class="ta-right"><strong>' + num(r.total) + '</strong></td></tr>';
    });

    html += '</tbody><tfoot><tr><td><strong>TOTAL</strong></td>' +
      '<td class="ta-right">' + num(totals.male) + '</td>' +
      '<td class="ta-right">' + num(totals.female) + '</td>' +
      '<td class="ta-right">' + num(totals.total) + '</td>' +
      '</tr></tfoot></table></div>';

    if (pct && pct.items && pct.items.length) {
      var parts = pct.items.map(function (p) {
        return p.percentage.toFixed(2) + '% ' + p.classification;
      });
      html += renderFooterText(parts.join(' · '));
    }

    output.innerHTML = html;
  }

  /* ============================================================
     POPULATION — detailed
     ============================================================ */

  function renderPopulationDetailed(data) {
    var rows   = data.rows   || [];
    var totals = data.totals || {};

    var html = renderReportHeader(data.meta);

    if (!rows.length) {
      output.innerHTML = html + '<div class="data-table__empty">No records.</div>';
      return;
    }

    html +=
      '<div class="table-wrap report-table__wrap"><table class="report-table report-table--actual">' +
        '<thead>' +
          '<tr>' +
            '<th rowspan="3">Classification</th>' +
            '<th colspan="2" rowspan="2">Detainee</th>' +
            '<th colspan="8">Sentenced</th>' +
            '<th rowspan="3">Grand Total</th>' +
          '</tr>' +
          '<tr>' +
            '<th colspan="2">Municipal</th><th colspan="2">City</th>' +
            '<th colspan="2">Provincial</th><th colspan="2">Insular</th>' +
          '</tr>' +
          '<tr>' +
            '<th>M</th><th>F</th><th>M</th><th>F</th><th>M</th><th>F</th>' +
            '<th>M</th><th>F</th><th>M</th><th>F</th>' +
          '</tr>' +
        '</thead><tbody>';

    rows.forEach(function (r) {
      html += '<tr>' +
        '<td>' + esc(r.classification) + '</td>' +
        '<td class="ta-right">' + num(r.detainee_male) + '</td>' +
        '<td class="ta-right">' + num(r.detainee_female) + '</td>' +
        '<td class="ta-right">' + num(r.municipal_male) + '</td>' +
        '<td class="ta-right">' + num(r.municipal_female) + '</td>' +
        '<td class="ta-right">' + num(r.city_male) + '</td>' +
        '<td class="ta-right">' + num(r.city_female) + '</td>' +
        '<td class="ta-right">' + num(r.provincial_male) + '</td>' +
        '<td class="ta-right">' + num(r.provincial_female) + '</td>' +
        '<td class="ta-right">' + num(r.insular_male) + '</td>' +
        '<td class="ta-right">' + num(r.insular_female) + '</td>' +
        '<td class="ta-right"><strong>' + num(r.grand_total) + '</strong></td>' +
        '</tr>';
    });

    html += '</tbody><tfoot><tr><td><strong>TOTAL</strong></td>' +
      '<td class="ta-right">' + num(totals.detainee_male) + '</td>' +
      '<td class="ta-right">' + num(totals.detainee_female) + '</td>' +
      '<td class="ta-right">' + num(totals.municipal_male) + '</td>' +
      '<td class="ta-right">' + num(totals.municipal_female) + '</td>' +
      '<td class="ta-right">' + num(totals.city_male) + '</td>' +
      '<td class="ta-right">' + num(totals.city_female) + '</td>' +
      '<td class="ta-right">' + num(totals.provincial_male) + '</td>' +
      '<td class="ta-right">' + num(totals.provincial_female) + '</td>' +
      '<td class="ta-right">' + num(totals.insular_male) + '</td>' +
      '<td class="ta-right">' + num(totals.insular_female) + '</td>' +
      '<td class="ta-right">' + num(totals.grand_total) + '</td>' +
      '</tr></tfoot></table></div>';

    output.innerHTML = html;
  }

  /* ============================================================
     FLOW
     ============================================================ */

  function renderFlow(data) {
    var rows   = data.rows   || [];
    var totals = data.totals || {};

    var html = renderReportHeader(data.meta);

    if (!rows.length) {
      output.innerHTML = html + '<div class="data-table__empty">No commits or releases for this month.</div>';
      return;
    }

    html +=
      '<div class="table-wrap report-table__wrap"><table class="report-table">' +
        '<thead><tr>' +
          '<th rowspan="2">Date</th>' +
          '<th colspan="3" class="report-table__group">Committed</th>' +
          '<th colspan="3" class="report-table__group">Released</th>' +
        '</tr><tr>' +
          '<th class="ta-right">M</th><th class="ta-right">F</th><th class="ta-right">Total</th>' +
          '<th class="ta-right">M</th><th class="ta-right">F</th><th class="ta-right">Total</th>' +
        '</tr></thead><tbody>';

    rows.forEach(function (r) {
      html += '<tr><td>' + esc(formatDayLabel(r.day)) + '</td>' +
        '<td class="ta-right">' + num(r.committed_male) + '</td>' +
        '<td class="ta-right">' + num(r.committed_female) + '</td>' +
        '<td class="ta-right"><strong>' + num(r.committed_total) + '</strong></td>' +
        '<td class="ta-right">' + num(r.released_male) + '</td>' +
        '<td class="ta-right">' + num(r.released_female) + '</td>' +
        '<td class="ta-right"><strong>' + num(r.released_total) + '</strong></td></tr>';
    });

    html += '</tbody><tfoot><tr><td><strong>TOTAL</strong></td>' +
      '<td class="ta-right">' + num(totals.committed_male) + '</td>' +
      '<td class="ta-right">' + num(totals.committed_female) + '</td>' +
      '<td class="ta-right">' + num(totals.committed_total) + '</td>' +
      '<td class="ta-right">' + num(totals.released_male) + '</td>' +
      '<td class="ta-right">' + num(totals.released_female) + '</td>' +
      '<td class="ta-right">' + num(totals.released_total) + '</td>' +
      '</tr></tfoot></table></div>';

    output.innerHTML = html;
  }

  /* ============================================================
     COMMITTED / RELEASED — by jail unit
     ============================================================ */

  function renderCommittedReleased(data) {
    var rows   = data.rows   || [];
    var totals = data.totals || {};

    var html = renderReportHeader(data.meta);

    if (!rows.length) {
      output.innerHTML = html + '<div class="data-table__empty">No jail units found.</div>';
      return;
    }

    html +=
      '<div class="table-wrap report-table__wrap"><table class="report-table">' +
        '<thead><tr>' +
          '<th rowspan="2">Jail Unit</th>' +
          '<th colspan="3" class="report-table__group">Committed</th>' +
          '<th colspan="3" class="report-table__group">Released</th>' +
          '<th rowspan="2" class="ta-right">Grand Total</th>' +
        '</tr><tr>' +
          '<th class="ta-right">M</th><th class="ta-right">F</th><th class="ta-right">Total</th>' +
          '<th class="ta-right">M</th><th class="ta-right">F</th><th class="ta-right">Total</th>' +
        '</tr></thead><tbody>';

    rows.forEach(function (r) {
      html += '<tr>' +
        '<td>' + esc(r.jail_unit_name) + '<br><small class="meta-text">' + esc(r.dormitory) + '</small></td>' +
        '<td class="ta-right">' + num(r.committed_male) + '</td>' +
        '<td class="ta-right">' + num(r.committed_female) + '</td>' +
        '<td class="ta-right"><strong>' + num(r.committed_total) + '</strong></td>' +
        '<td class="ta-right">' + num(r.released_male) + '</td>' +
        '<td class="ta-right">' + num(r.released_female) + '</td>' +
        '<td class="ta-right"><strong>' + num(r.released_total) + '</strong></td>' +
        '<td class="ta-right"><strong>' + num(r.grand_total) + '</strong></td>' +
        '</tr>';
    });

    html += '</tbody><tfoot><tr><td><strong>TOTAL</strong></td>' +
      '<td class="ta-right">' + num(totals.committed_male) + '</td>' +
      '<td class="ta-right">' + num(totals.committed_female) + '</td>' +
      '<td class="ta-right">' + num(totals.committed_total) + '</td>' +
      '<td class="ta-right">' + num(totals.released_male) + '</td>' +
      '<td class="ta-right">' + num(totals.released_female) + '</td>' +
      '<td class="ta-right">' + num(totals.released_total) + '</td>' +
      '<td class="ta-right">' + num(totals.grand_total) + '</td>' +
      '</tr></tfoot></table></div>';

    output.innerHTML = html;
  }

  /* ============================================================
     DRUG CASES — simple
     ============================================================ */

  function renderDrugCases(data) {
    var rows   = data.rows   || [];
    var totals = data.totals || { male: 0, female: 0, total: 0 };

    var html = renderReportHeader(data.meta);
    if (!rows.length) {
      output.innerHTML = html + '<div class="data-table__empty">No drug-related PDL records.</div>';
      return;
    }

    html +=
      '<div class="table-wrap report-table__wrap"><table class="report-table">' +
        '<thead><tr>' +
          '<th rowspan="2">Classification</th>' +
          '<th colspan="2" class="report-table__group">Sex</th>' +
          '<th rowspan="2" class="ta-right">Total</th>' +
        '</tr><tr>' +
          '<th class="ta-right">Male</th><th class="ta-right">Female</th>' +
        '</tr></thead><tbody>';

    rows.forEach(function (r) {
      html += '<tr><td>' + esc(r.classification) + '</td>' +
        '<td class="ta-right">' + num(r.male) + '</td>' +
        '<td class="ta-right">' + num(r.female) + '</td>' +
        '<td class="ta-right"><strong>' + num(r.total) + '</strong></td></tr>';
    });

    html += '</tbody><tfoot><tr><td><strong>TOTAL</strong></td>' +
      '<td class="ta-right">' + num(totals.male) + '</td>' +
      '<td class="ta-right">' + num(totals.female) + '</td>' +
      '<td class="ta-right">' + num(totals.total) + '</td>' +
      '</tr></tfoot></table></div>';

    output.innerHTML = html;
  }

  /* ============================================================
     DRUG CASES — detailed
     ============================================================ */

  function renderDrugCasesDetailed(data) {
    var rows   = data.rows   || [];
    var totals = data.totals || {};

    var html = renderReportHeader(data.meta);

    if (!rows.length) {
      output.innerHTML = html + '<div class="data-table__empty">No drug-related PDL records.</div>';
      return;
    }

    html +=
      '<div class="table-wrap report-table__wrap">' +
      '<table class="report-table report-table--actual">' +
        '<thead>' +
          '<tr>' +
            '<th rowspan="3">Classification</th>' +
            '<th colspan="8">Sentenced</th>' +
            '<th colspan="8">Awaiting / On Trial</th>' +
            '<th rowspan="3">Total</th>' +
          '</tr>' +
          '<tr>' +
            '<th colspan="4">R.A. 6425</th>' +
            '<th colspan="4">R.A. 9165</th>' +
            '<th colspan="4">R.A. 6425</th>' +
            '<th colspan="4">R.A. 9165</th>' +
          '</tr>' +
          '<tr>' +
            '<th>Adult M</th><th>Adult F</th><th>CICL M</th><th>CICL F</th>' +
            '<th>Adult M</th><th>Adult F</th><th>CICL M</th><th>CICL F</th>' +
            '<th>Adult M</th><th>Adult F</th><th>CICL M</th><th>CICL F</th>' +
            '<th>Adult M</th><th>Adult F</th><th>CICL M</th><th>CICL F</th>' +
          '</tr>' +
        '</thead><tbody>';

    rows.forEach(function (r) {
      html += '<tr>' +
        '<td>' + esc(r.classification) + '</td>' +
        '<td class="ta-right">' + num(r.r6425_adult_male) + '</td>' +
        '<td class="ta-right">' + num(r.r6425_adult_female) + '</td>' +
        '<td class="ta-right">' + num(r.r6425_cicl_male) + '</td>' +
        '<td class="ta-right">' + num(r.r6425_cicl_female) + '</td>' +
        '<td class="ta-right">' + num(r.r9165_adult_male) + '</td>' +
        '<td class="ta-right">' + num(r.r9165_adult_female) + '</td>' +
        '<td class="ta-right">' + num(r.r9165_cicl_male) + '</td>' +
        '<td class="ta-right">' + num(r.r9165_cicl_female) + '</td>' +
        '<td class="ta-right">' + num(r.at_r6425_adult_male) + '</td>' +
        '<td class="ta-right">' + num(r.at_r6425_adult_female) + '</td>' +
        '<td class="ta-right">' + num(r.at_r6425_cicl_male) + '</td>' +
        '<td class="ta-right">' + num(r.at_r6425_cicl_female) + '</td>' +
        '<td class="ta-right">' + num(r.at_r9165_adult_male) + '</td>' +
        '<td class="ta-right">' + num(r.at_r9165_adult_female) + '</td>' +
        '<td class="ta-right">' + num(r.at_r9165_cicl_male) + '</td>' +
        '<td class="ta-right">' + num(r.at_r9165_cicl_female) + '</td>' +
        '<td class="ta-right"><strong>' + num(r.total) + '</strong></td>' +
        '</tr>';
    });

    html += '</tbody><tfoot><tr><td><strong>TOTAL</strong></td>';
    [
      'r6425_adult_male','r6425_adult_female','r6425_cicl_male','r6425_cicl_female',
      'r9165_adult_male','r9165_adult_female','r9165_cicl_male','r9165_cicl_female',
      'at_r6425_adult_male','at_r6425_adult_female','at_r6425_cicl_male','at_r6425_cicl_female',
      'at_r9165_adult_male','at_r9165_adult_female','at_r9165_cicl_male','at_r9165_cicl_female',
      'total',
    ].forEach(function (k) {
      html += '<td class="ta-right">' + num(totals[k]) + '</td>';
    });
    html += '</tr></tfoot></table></div>';

    output.innerHTML = html;
  }

  /* ============================================================
     ACTUAL POPULATION
     ============================================================ */

  function renderActualPopulation(data) {
    var rows   = data.rows   || [];
    var totals = data.totals || {};
    var pct    = data.percents;

    var html = renderReportHeader(data.meta);
    if (!rows.length) {
      output.innerHTML = html + '<div class="data-table__empty">No records.</div>';
      return;
    }

    html +=
      '<div class="table-wrap report-table__wrap">' +
      '<table class="report-table report-table--actual">' +
        '<thead>' +
          '<tr>' +
            '<th rowspan="3">Municipality</th>' +
            '<th colspan="2" rowspan="2">Detainee</th>' +
            '<th rowspan="2">Total<br>Detainees</th>' +
            '<th colspan="8">Sentenced</th>' +
            '<th rowspan="2">Total<br>Sentenced</th>' +
            '<th rowspan="2">Grand<br>Total</th>' +
          '</tr>' +
          '<tr>' +
            '<th colspan="2">Municipal</th><th colspan="2">City</th>' +
            '<th colspan="2">Provincial</th><th colspan="2">Insular</th>' +
          '</tr>' +
          '<tr>' +
            '<th>M</th><th>F</th><th>M</th><th>F</th>' +
            '<th>M</th><th>F</th><th>M</th><th>F</th>' +
          '</tr>' +
        '</thead><tbody>';

    rows.forEach(function (r) {
      html += '<tr>' +
        '<td>' + esc(r.municipality) + '</td>' +
        '<td class="ta-right">' + num(r.detainee_male) + '</td>' +
        '<td class="ta-right">' + num(r.detainee_female) + '</td>' +
        '<td class="ta-right"><strong>' + num(r.detainee_total) + '</strong></td>' +
        '<td class="ta-right">' + num(r.municipal_male) + '</td>' +
        '<td class="ta-right">' + num(r.municipal_female) + '</td>' +
        '<td class="ta-right">' + num(r.city_male) + '</td>' +
        '<td class="ta-right">' + num(r.city_female) + '</td>' +
        '<td class="ta-right">' + num(r.provincial_male) + '</td>' +
        '<td class="ta-right">' + num(r.provincial_female) + '</td>' +
        '<td class="ta-right">' + num(r.insular_male) + '</td>' +
        '<td class="ta-right">' + num(r.insular_female) + '</td>' +
        '<td class="ta-right"><strong>' + num(r.sentenced_total) + '</strong></td>' +
        '<td class="ta-right"><strong>' + num(r.grand_total) + '</strong></td>' +
        '</tr>';
    });

    html += '</tbody><tfoot><tr><td><strong>TOTAL</strong></td>' +
      '<td class="ta-right">' + num(totals.detainee_male) + '</td>' +
      '<td class="ta-right">' + num(totals.detainee_female) + '</td>' +
      '<td class="ta-right">' + num(totals.detainee_total) + '</td>' +
      '<td class="ta-right">' + num(totals.municipal_male) + '</td>' +
      '<td class="ta-right">' + num(totals.municipal_female) + '</td>' +
      '<td class="ta-right">' + num(totals.city_male) + '</td>' +
      '<td class="ta-right">' + num(totals.city_female) + '</td>' +
      '<td class="ta-right">' + num(totals.provincial_male) + '</td>' +
      '<td class="ta-right">' + num(totals.provincial_female) + '</td>' +
      '<td class="ta-right">' + num(totals.insular_male) + '</td>' +
      '<td class="ta-right">' + num(totals.insular_female) + '</td>' +
      '<td class="ta-right">' + num(totals.sentenced_total) + '</td>' +
      '<td class="ta-right">' + num(totals.grand_total) + '</td>' +
      '</tr></tfoot></table></div>';

    if (pct && pct.detainee !== undefined) {
      html += renderFooterText(
        num(pct.detainee).toFixed(2) + '% Detainee · ' +
        num(pct.sentenced).toFixed(2) + '% Sentenced'
      );
    }

    output.innerHTML = html;

    // ⬇️ NEW: make municipality rows clickable
    makeMunicipalityRowsClickable();
  }

  /* ============================================================
     ESCORTS
     ============================================================ */

  function renderEscorts(data) {
    var rows   = data.rows   || [];
    var totals = data.totals || {};

    var html = renderReportHeader(data.meta);
    if (!rows.length) {
      output.innerHTML = html + '<div class="data-table__empty">No escort records for this month.</div>';
      return;
    }

    var purposes = [
      { key: 'court',      label: 'Court' },
      { key: 'hospital',   label: 'Hospital' },
      { key: 'drug_exam',  label: 'Drug Exam' },
      { key: 'rehab',      label: 'Rehab' },
      { key: 'inquest',    label: 'Inquest' },
      { key: 'transfer',   label: 'Transfer' },
      { key: 'visitation', label: 'Visitation' },
      { key: 'other',      label: 'Other' },
    ];

    html += '<div class="table-wrap report-table__wrap">' +
      '<table class="report-table report-table--actual"><thead>' +
      '<tr><th rowspan="2">Municipality</th>';
    purposes.forEach(function (p) {
      html += '<th colspan="2">' + esc(p.label) + '</th>';
    });
    html += '<th rowspan="2" class="ta-right">Total</th></tr><tr>';
    purposes.forEach(function () {
      html += '<th>M</th><th>F</th>';
    });
    html += '</tr></thead><tbody>';

    rows.forEach(function (r) {
      html += '<tr><td>' + esc(r.municipality) + '</td>';
      purposes.forEach(function (p) {
        html += '<td class="ta-right">' + num(r[p.key + '_male']) + '</td>' +
                '<td class="ta-right">' + num(r[p.key + '_female']) + '</td>';
      });
      html += '<td class="ta-right"><strong>' + num(r.total) + '</strong></td></tr>';
    });

    html += '</tbody><tfoot><tr><td><strong>TOTAL</strong></td>';
    purposes.forEach(function (p) {
      html += '<td class="ta-right">' + num(totals[p.key + '_male']) + '</td>' +
              '<td class="ta-right">' + num(totals[p.key + '_female']) + '</td>';
    });
    html += '<td class="ta-right">' + num(totals.total) + '</td></tr></tfoot></table></div>';

    output.innerHTML = html;

    // ⬇️ NEW: make municipality rows clickable
    makeMunicipalityRowsClickable();
  }

  /* ============================================================
     LOAD
     ============================================================ */

  function load() {
    var params = {};
    var needsMonth = (
      currentReport === 'flow' ||
      currentReport === 'committed-released' ||
      currentReport === 'escorts'
    );
    if (needsMonth) {
      params.month = monthInput ? monthInput.value : '';
    }
    if (monthInput) {
      monthInput.hidden = !needsMonth;
    }

    showLoading();

    api(currentReport, params).then(function (data) {
      switch (currentReport) {
        case 'population':          renderPopulation(data);       break;
        case 'population-detailed': renderPopulationDetailed(data); break;
        case 'flow':                renderFlow(data);             break;
        case 'committed-released':  renderCommittedReleased(data); break;
        case 'drug-cases':          renderDrugCases(data);        break;
        case 'drug-cases-detailed': renderDrugCasesDetailed(data); break;
        case 'actual-population':   renderActualPopulation(data); break;
        case 'escorts':             renderEscorts(data);          break;
      }
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

      currentReport = tab.dataset.report || 'actual-population';
      load();
    });
  });

  if (monthInput) {
    monthInput.addEventListener('change', function () { load(); });
  }

  if (exportBtn) {
    exportBtn.addEventListener('click', function () {
      var params = new URLSearchParams();
      params.set('report', currentReport);
      if ((currentReport === 'flow' || currentReport === 'committed-released' || currentReport === 'escorts') && monthInput) {
        params.set('month', monthInput.value);
      }
      window.location.href = 'index.php?route=report&action=export&' + params.toString();
    });
  }

  document.addEventListener('section:changed', function (e) {
    if (e.detail && e.detail.section === 'reports') {
      if (!lastLoaded) load();
    }
  });

  if (section.classList.contains('is-visible')) load();

  if (monthInput && !monthInput.value) {
    var now = new Date();
    var mm  = String(now.getMonth() + 1).padStart(2, '0');
    monthInput.value = now.getFullYear() + '-' + mm;
  }
})();