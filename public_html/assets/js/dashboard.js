/* ============================================================
   dashboard.js — ROUTER + DATA LOADER (combined)
   Replaces both the old router file and the old dashboard.js
   ============================================================ */
(function () {
  "use strict";

  console.log("[dashboard] script starting");

  var shell = document.getElementById("dashboardScreen");
  if (!shell) {
    console.warn(
      "[dashboard] #dashboardScreen not found — script will not run"
    );
    return;
  }

  var isAdmin = shell.dataset.role === "admin";
  console.log("[dashboard] shell found, isAdmin =", isAdmin);

  /* ============================================================
     PART 1 — SECTION ROUTER
     ============================================================ */

  var sidebar = shell.querySelector(".dashboard-sidebar");
  var menuBtn = document.getElementById("dashboardMenuButton");
  var navItems = shell.querySelectorAll(".sidebar-nav__item[data-section]");
  var triggers = shell.querySelectorAll("[data-section]");
  var sections = shell.querySelectorAll(".dashboard-section");
  var groups = shell.querySelectorAll(".sidebar-nav__group[data-group]");

  console.log(
    "[dashboard] navItems:",
    navItems.length,
    "sections:",
    sections.length,
    "groups:",
    groups.length
  );

  function groupOwning(section) {
    var owner = null;
    groups.forEach(function (btn) {
      var group = btn.dataset.group;
      var children = shell.querySelector(
        '.sidebar-nav__children[data-group-children="' + group + '"]'
      );
      if (!children) return;
      if (
        children.querySelector(
          '.sidebar-nav__item[data-section="' + section + '"]'
        )
      ) {
        owner = group;
      }
    });
    return owner;
  }

  function closeAllGroups(exceptGroup) {
    groups.forEach(function (btn) {
      if (btn.dataset.group === exceptGroup) return;
      btn.classList.remove("is-open");
      btn.setAttribute("aria-expanded", "false");
      var children = shell.querySelector(
        '.sidebar-nav__children[data-group-children="' +
          btn.dataset.group +
          '"]'
      );
      if (children) children.classList.remove("is-open");
    });
  }

  function openGroup(group) {
    var btn = shell.querySelector(
      '.sidebar-nav__group[data-group="' + group + '"]'
    );
    var children = shell.querySelector(
      '.sidebar-nav__children[data-group-children="' + group + '"]'
    );
    if (!btn || !children) return;
    btn.classList.add("is-open");
    btn.setAttribute("aria-expanded", "true");
    children.classList.add("is-open");
  }

  groups.forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      var group = btn.dataset.group;
      var children = shell.querySelector(
        '.sidebar-nav__children[data-group-children="' + group + '"]'
      );
      if (!children) return;
      var isOpen = btn.classList.toggle("is-open");
      children.classList.toggle("is-open", isOpen);
      btn.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });
  });

  function getSectionFromUrl() {
    var url = new URL(window.location.href);
    return (
      url.searchParams.get("section") ||
      shell.dataset.initialSection ||
      "overview"
    );
  }

  function validSection(name) {
    var target = document.getElementById("dashboard-" + name);
    var ok = !!(target && shell.contains(target));
    if (!ok) console.warn("[dashboard] invalid section:", name);
    return ok;
  }

  function closeSidebar() {
    if (sidebar) sidebar.classList.remove("is-open");
    if (menuBtn) menuBtn.setAttribute("aria-expanded", "false");
  }

  function showSection(name, updateUrl) {
    if (!validSection(name)) return;

    var target = document.getElementById("dashboard-" + name);

    sections.forEach(function (s) {
      var on = s === target;
      s.hidden = !on;
      s.classList.toggle("is-visible", on);
    });

    navItems.forEach(function (item) {
      var on = item.dataset.section === name;
      item.classList.toggle("is-active", on);
      if (on) item.setAttribute("aria-current", "page");
      else item.removeAttribute("aria-current");
    });

    var owner = groupOwning(name);
    if (owner) {
      closeAllGroups(owner);
      openGroup(owner);
    } else {
      closeAllGroups(null);
    }

    closeSidebar();

    if (updateUrl !== false) {
      var url = new URL(window.location.href);
      if (url.searchParams.get("section") !== name) {
        url.searchParams.set("section", name);
        history.replaceState(null, "", url.toString());
      }
    }

    document.dispatchEvent(
      new CustomEvent("section:changed", {
        detail: { section: name },
      })
    );

    console.log("[dashboard] section shown:", name);
  }

  // Attach click handlers to every [data-section] trigger
  triggers.forEach(function (t) {
    t.addEventListener("click", function (e) {
      var name = e.currentTarget.dataset.section;
      if (!name) return;
      e.preventDefault();
      showSection(name);
    });
  });

  // Mobile sidebar toggle
  if (menuBtn && sidebar) {
    menuBtn.addEventListener("click", function (e) {
      e.preventDefault();
      sidebar.classList.toggle("is-open");
      menuBtn.setAttribute(
        "aria-expanded",
        sidebar.classList.contains("is-open") ? "true" : "false"
      );
    });
  }

  document.addEventListener("click", function (e) {
    if (window.innerWidth > 991) return;
    if (!sidebar || !sidebar.classList.contains("is-open")) return;
    if (sidebar.contains(e.target) || (menuBtn && menuBtn.contains(e.target)))
      return;
    closeSidebar();
  });

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") closeSidebar();
  });

  // Logout modal
  var logoutBtn = document.getElementById("sidebarLogoutBtn");
  var logoutModal = document.getElementById("logoutModal");
  function openLogoutModal() {
    if (!logoutModal) return;
    logoutModal.classList.remove("is-hidden");
    document.body.classList.add("modal-open");
    setTimeout(function () {
      var okBtn = logoutModal.querySelector("#logoutConfirmBtn");
      if (okBtn) okBtn.focus();
    }, 50);
  }
  function closeLogoutModal() {
    if (!logoutModal) return;
    logoutModal.classList.add("is-hidden");
    document.body.classList.remove("modal-open");
  }
  if (logoutBtn && logoutModal) {
    logoutBtn.addEventListener("click", openLogoutModal);
    logoutModal.addEventListener("click", function (e) {
      if (e.target === logoutModal) closeLogoutModal();
      if (e.target.closest("[data-logout-close]")) closeLogoutModal();
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && !logoutModal.classList.contains("is-hidden")) {
        closeLogoutModal();
      }
    });
  }

  // Alert banner auto-dismiss
  var alertBanner = document.querySelector(".alert-banner");
  if (alertBanner) {
    var dismissAlert = function () {
      if (alertBanner.classList.contains("is-dismissing")) return;
      alertBanner.classList.add("is-dismissing");
      setTimeout(function () {
        alertBanner.remove();
      }, 400);
    };
    var closeBtn = alertBanner.querySelector(".alert-banner__close");
    if (closeBtn) closeBtn.addEventListener("click", dismissAlert);
    setTimeout(dismissAlert, 4000);
  }

  // Non-chart tab groups (chart ones are handled below)
  document
    .querySelectorAll(".tab-group:not([data-chart-range])")
    .forEach(function (group) {
      group.addEventListener("click", function (e) {
        var btn = e.target.closest(".tab-btn");
        if (!btn) return;
        group.querySelectorAll(".tab-btn").forEach(function (b) {
          b.classList.remove("is-active");
        });
        btn.classList.add("is-active");
      });
    });

  // Back/forward
  window.addEventListener("popstate", function () {
    showSection(getSectionFromUrl(), false);
  });

  /* ============================================================
     PART 2 — DATA LOADER
     ============================================================ */

  function el(id) {
    return document.getElementById(id);
  }

  function esc(v) {
    var d = document.createElement("div");
    d.textContent = String(v == null ? "" : v);
    return d.innerHTML;
  }

  function getJSON(url) {
    return fetch(url, {
      credentials: "same-origin",
      headers: { Accept: "application/json" },
    }).then(function (r) {
      return r.json().then(function (data) {
        if (!r.ok || !data.success) throw new Error(data.message || "Error");
        return data;
      });
    });
  }

  function shortDate(iso) {
    if (!iso) return "";
    var d = new Date(iso + "T00:00:00");
    if (isNaN(d.getTime())) return iso;
    return d.toLocaleDateString("en-US", { month: "short", day: "numeric" });
  }

  function setText(id, value) {
    var node = el(id);
    if (!node) return;
    node.textContent = value == null || value === "" ? "—" : value;
  }

  function loadStats() {
    getJSON("index.php?route=dashboard&action=stats")
      .then(function (data) {
        var s = data.stats || {};
        if (s.scope === "admin") {
          setText("statTotalInmates", s.pdl);
          setText("statVisitors", s.visitors);
          setText("statIncidents", s.incidents);
          setText("statUsers", s.users);
        } else {
          setText("statMyEntries", s.my_entries);
          setText("statMyHeadcount", s.my_headcount);
          setText("statTotalInmates", s.pdl);
          setText("statVisitors", s.visitors);
        }
      })
      .catch(function (err) {
        console.error("[dashboard] stats failed:", err);
      });
  }

  var KIND_ICON = {
    inmate: "bi-person-lines-fill",
    user: "bi-person-badge",
    visitor: "bi-person-check",
    incident: "bi-exclamation-triangle",
    headcount: "bi-clipboard2-check",
  };

  function relTime(ts) {
    if (!ts) return "";
    var d = new Date(ts.replace(" ", "T"));
    if (isNaN(d.getTime())) return ts;
    var diff = Math.floor((Date.now() - d.getTime()) / 1000);
    if (diff < 60) return "just now";
    if (diff < 3600) return Math.floor(diff / 60) + "m ago";
    if (diff < 86400) return Math.floor(diff / 3600) + "h ago";
    if (diff < 604800) return Math.floor(diff / 86400) + "d ago";
    return d.toLocaleDateString();
  }

  function loadActivity() {
    var feed = el("activityFeed");
    if (!feed) return;
    getJSON("index.php?route=dashboard&action=activity&limit=6")
      .then(function (data) {
        var items = data.activity || [];
        if (!items.length) {
          feed.innerHTML =
            '<li class="activity"><div class="activity__body"><strong>No recent activity</strong></div></li>';
          return;
        }
        feed.innerHTML = items
          .map(function (a) {
            var icon = KIND_ICON[a.kind] || "bi-clock-history";
            return (
              '<li class="activity">' +
              '<span class="activity__icon"><i class="bi ' +
              icon +
              '"></i></span>' +
              '<div class="activity__body">' +
              "<strong>" +
              esc(a.title) +
              "</strong>" +
              "<span>" +
              esc(a.subtitle || "") +
              " · " +
              esc(relTime(a.happened_at)) +
              "</span>" +
              "</div>" +
              "</li>"
            );
          })
          .join("");
      })
      .catch(function (err) {
        console.error("[dashboard] activity failed:", err);
      });
  }

  /* ============================================================
     CHARTS — safe rendering with canvas reuse protection
     ============================================================ */

  var charts = {};
  var rendering = false;

  function safeDestroy(key, canvas) {
    if (charts[key]) {
      try {
        charts[key].destroy();
      } catch (e) {}
      delete charts[key];
    }
    if (canvas && window.Chart && typeof Chart.getChart === "function") {
      var orphan = Chart.getChart(canvas);
      if (orphan) {
        try {
          orphan.destroy();
        } catch (e) {}
      }
    }
  }

  function safeCreate(key, canvas, config) {
    if (!canvas) return null;
    safeDestroy(key, canvas);
    try {
      charts[key] = new Chart(canvas, config);
      return charts[key];
    } catch (err) {
      console.error('[dashboard] failed to render chart "' + key + '":', err);
      return null;
    }
  }

  var THEME = {
    primary: "#2563eb",
    success: "#16a34a",
    warning: "#f59e0b",
    danger: "#dc2626",
    purple: "#7c3aed",
    teal: "#0891b2",
    slate: "#64748b",
    pink: "#db2777",
    palette: [
      "#2563eb",
      "#16a34a",
      "#f59e0b",
      "#dc2626",
      "#7c3aed",
      "#0891b2",
      "#db2777",
      "#64748b",
    ],
  };

  if (window.Chart) {
    Chart.defaults.font.family =
      "'Inter', system-ui, -apple-system, sans-serif";
    Chart.defaults.color = "#475569";
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 8;
    Chart.defaults.plugins.legend.labels.padding = 14;
    Chart.defaults.maintainAspectRatio = false;
  } else {
    console.warn("[dashboard] Chart.js not loaded — charts will not render");
  }

  function loadAnalytics(days) {
    if (!isAdmin) return;
    if (rendering) return;
    rendering = true;
    days = days || 30;

    getJSON("index.php?route=dashboard&action=analytics&days=" + days)
      .then(function (data) {
        renderCharts(data.analytics || {});
      })
      .catch(function (err) {
        console.error("[dashboard] analytics failed:", err);
      })
      .finally(function () {
        rendering = false;
      });
  }

  function renderCharts(a) {
    if (!window.Chart) return;

    if (a.population_trend && a.population_trend.length) {
      safeCreate("population", el("chartPopulationTrend"), {
        type: "line",
        data: {
          labels: a.population_trend.map(function (r) {
            return shortDate(r.date);
          }),
          datasets: [
            {
              label: "PDL count",
              data: a.population_trend.map(function (r) {
                return r.count;
              }),
              borderColor: THEME.primary,
              backgroundColor: "rgba(37, 99, 235, 0.10)",
              borderWidth: 2,
              fill: true,
              tension: 0.35,
              pointRadius: 3,
              pointHoverRadius: 5,
            },
          ],
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { grid: { display: false } },
          },
        },
      });
    }

    if (a.classification && a.classification.length) {
      safeCreate("classification", el("chartClassification"), {
        type: "doughnut",
        data: {
          labels: a.classification.map(function (r) {
            return r.label;
          }),
          datasets: [
            {
              data: a.classification.map(function (r) {
                return r.count;
              }),
              backgroundColor: THEME.palette,
              borderWidth: 0,
            },
          ],
        },
        options: { cutout: "62%", plugins: { legend: { position: "bottom" } } },
      });
    }

    if (a.top_offenses && a.top_offenses.length) {
      safeCreate("offenses", el("chartTopOffenses"), {
        type: "bar",
        data: {
          labels: a.top_offenses.map(function (r) {
            return r.label;
          }),
          datasets: [
            {
              label: "Inmates",
              data: a.top_offenses.map(function (r) {
                return r.count;
              }),
              backgroundColor: THEME.primary,
              borderRadius: 4,
            },
          ],
        },
        options: {
          indexAxis: "y",
          plugins: { legend: { display: false } },
          scales: {
            x: { beginAtZero: true, ticks: { precision: 0 } },
            y: { grid: { display: false } },
          },
        },
      });
    }

    if (a.visitors_per_day && a.visitors_per_day.length) {
      safeCreate("visitors", el("chartVisitors"), {
        type: "bar",
        data: {
          labels: a.visitors_per_day.map(function (r) {
            return shortDate(r.date);
          }),
          datasets: [
            {
              label: "Visitors",
              data: a.visitors_per_day.map(function (r) {
                return r.count;
              }),
              backgroundColor: THEME.success,
              borderRadius: 4,
            },
          ],
        },
        options: {
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { grid: { display: false } },
          },
        },
      });
    }

    if (a.incidents_by_type && a.incidents_by_type.length) {
      safeCreate("incType", el("chartIncidentsType"), {
        type: "doughnut",
        data: {
          labels: a.incidents_by_type.map(function (r) {
            return r.label;
          }),
          datasets: [
            {
              data: a.incidents_by_type.map(function (r) {
                return r.count;
              }),
              backgroundColor: [
                THEME.danger,
                THEME.warning,
                THEME.slate,
                THEME.purple,
                THEME.teal,
              ],
              borderWidth: 0,
            },
          ],
        },
        options: { cutout: "62%", plugins: { legend: { position: "bottom" } } },
      });
    }

    if (a.incidents_by_severity && a.incidents_by_severity.length) {
      safeCreate("incSeverity", el("chartIncidentsSeverity"), {
        type: "bar",
        data: {
          labels: a.incidents_by_severity.map(function (r) {
            return r.label;
          }),
          datasets: [
            {
              label: "Open",
              data: a.incidents_by_severity.map(function (r) {
                return r.open;
              }),
              backgroundColor: THEME.danger,
              borderRadius: 4,
              stack: "s",
            },
            {
              label: "Resolved",
              data: a.incidents_by_severity.map(function (r) {
                return r.resolved;
              }),
              backgroundColor: THEME.success,
              borderRadius: 4,
              stack: "s",
            },
          ],
        },
        options: {
          plugins: { legend: { position: "bottom" } },
          scales: {
            y: { beginAtZero: true, stacked: true, ticks: { precision: 0 } },
            x: { stacked: true, grid: { display: false } },
          },
        },
      });
    }

    if (a.age_distribution && a.age_distribution.length) {
      safeCreate("age", el("chartAge"), {
        type: "bar",
        data: {
          labels: a.age_distribution.map(function (r) {
            return r.label;
          }),
          datasets: [
            {
              label: "Inmates",
              data: a.age_distribution.map(function (r) {
                return r.count;
              }),
              backgroundColor: THEME.purple,
              borderRadius: 4,
            },
          ],
        },
        options: {
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { grid: { display: false } },
          },
        },
      });
    }

    if (a.inmates_by_unit && a.inmates_by_unit.length) {
      safeCreate("units", el("chartUnits"), {
        type: "bar",
        data: {
          labels: a.inmates_by_unit.map(function (r) {
            return r.label;
          }),
          datasets: [
            {
              label: "Male",
              data: a.inmates_by_unit.map(function (r) {
                return r.male;
              }),
              backgroundColor: THEME.primary,
              borderRadius: 4,
            },
            {
              label: "Female",
              data: a.inmates_by_unit.map(function (r) {
                return r.female;
              }),
              backgroundColor: THEME.pink,
              borderRadius: 4,
            },
          ],
        },
        options: {
          plugins: { legend: { position: "bottom" } },
          scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { grid: { display: false } },
          },
        },
      });
    }

    if (a.drug_cases && a.drug_cases.length) {
      safeCreate("drug", el("chartDrugCases"), {
        type: "doughnut",
        data: {
          labels: a.drug_cases.map(function (r) {
            return r.label;
          }),
          datasets: [
            {
              data: a.drug_cases.map(function (r) {
                return r.count;
              }),
              backgroundColor: [THEME.warning, THEME.danger, THEME.slate],
              borderWidth: 0,
            },
          ],
        },
        options: { cutout: "62%", plugins: { legend: { position: "bottom" } } },
      });
    }

    if (a.admissions_by_month && a.admissions_by_month.length) {
      safeCreate("admissions", el("chartAdmissions"), {
        type: "line",
        data: {
          labels: a.admissions_by_month.map(function (r) {
            return r.month;
          }),
          datasets: [
            {
              label: "Admissions",
              data: a.admissions_by_month.map(function (r) {
                return r.count;
              }),
              borderColor: THEME.teal,
              backgroundColor: "rgba(8, 145, 178, 0.10)",
              borderWidth: 2,
              fill: true,
              tension: 0.35,
              pointRadius: 3,
            },
          ],
        },
        options: {
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { grid: { display: false } },
          },
        },
      });
    }

    renderCompliance(a.headcount_compliance || []);
  }

  function renderCompliance(rows) {
    var wrap = el("complianceList");
    if (!wrap) return;

    if (!rows.length) {
      wrap.innerHTML = '<p class="meta-text">No active jail units.</p>';
      return;
    }

    wrap.innerHTML = rows
      .map(function (r) {
        var pct = Math.round((r.submitted / r.required) * 100);
        pct = Math.max(0, Math.min(100, pct));
        var cls =
          pct >= 100 ? "is-complete" : pct >= 50 ? "is-partial" : "is-low";
        return (
          '<div class="compliance-row">' +
          '<div class="compliance-row__head">' +
          "<strong>" +
          esc(r.unit) +
          "</strong>" +
          '<span class="meta-text">' +
          r.submitted +
          " / " +
          r.required +
          "</span>" +
          "</div>" +
          '<div class="compliance-bar"><span class="compliance-bar__fill ' +
          cls +
          '" style="width:' +
          pct +
          '%"></span></div>' +
          "</div>"
        );
      })
      .join("");
  }

  document
    .querySelectorAll(".tab-group[data-chart-range]")
    .forEach(function (group) {
      group.addEventListener("click", function (e) {
        var btn = e.target.closest(".tab-btn");
        if (!btn) return;

        group.querySelectorAll(".tab-btn").forEach(function (b) {
          b.classList.remove("is-active");
        });
        btn.classList.add("is-active");

        var range = btn.textContent.trim().toUpperCase();
        var days;
        if (range === "7D") days = 7;
        else if (range === "30D") days = 30;
        else if (range === "12M") days = 365;
        else days = 30;

        // 💾 Remember the choice across page reloads
        try {
          localStorage.setItem("dashboardTrendRange", range);
        } catch (err) {}

        console.log("[dashboard] analytics range:", range, "→", days, "days");
        loadAnalytics(days);
      });
    });

  /* ============================================================
     PART 3 — INIT
     ============================================================ */

  var statsLoaded = false;

  function ensureOverviewData() {
    if (!statsLoaded) {
      statsLoaded = true;
      loadStats();
      loadActivity();
      // loadUpcoming();
    }

    if (!isAdmin) return;

    // ---- Read the saved trend range ----
    var saved = "30D";
    try {
      var stored = localStorage.getItem("dashboardTrendRange");
      if (stored) saved = stored.trim().toUpperCase();
    } catch (err) {}

    // ---- Validate it against the actual buttons ----
    var buttons = document.querySelectorAll(
      ".tab-group[data-chart-range] .tab-btn"
    );
    var matchedBtn = null;
    buttons.forEach(function (b) {
      var label = b.textContent.trim().toUpperCase();
      if (label === saved) matchedBtn = b;
    });

    // If the saved range isn't a real button, fall back to 30D
    if (!matchedBtn) {
      saved = "30D";
      buttons.forEach(function (b) {
        if (b.textContent.trim().toUpperCase() === "30D") matchedBtn = b;
      });
    }

    // ---- Update the active tab visual to match ----
    buttons.forEach(function (b) {
      b.classList.toggle("is-active", b === matchedBtn);
    });

    // ---- Map range → days ----
    var days;
    if (saved === "7D") days = 7;
    else if (saved === "12M") days = 365;
    else days = 30;

    console.log("[dashboard] restoring range:", saved, "→", days, "days");

    // ---- Load analytics with the saved range ----
    loadAnalytics(days);
  }

  document.addEventListener("section:changed", function (e) {
    if (e.detail && e.detail.section === "overview") {
      ensureOverviewData();
    }
  });

  // Initial render — show the section from URL
  showSection(getSectionFromUrl(), false);

  // If overview is active, load its data now
  var overviewSection = el("dashboard-overview");
  if (overviewSection && overviewSection.classList.contains("is-visible")) {
    ensureOverviewData();
  }

  console.log("[dashboard] initialized");
})();
