(function () {
  "use strict";

  var section = document.getElementById("dashboard-activity-logs");
  if (!section) return;

  var tbody = document.getElementById("logsTableBody");
  var searchInput = document.getElementById("logSearch");
  var moduleFilter = document.getElementById("logModuleFilter");
  var loaded = false;

  function esc(v) {
    var d = document.createElement("div");
    d.textContent = String(v == null ? "" : v);
    return d.innerHTML;
  }

  function formatDate(iso) {
    if (!iso) return "—";
    var d = new Date(iso.replace(" ", "T"));
    if (isNaN(d.getTime())) return iso;
    return d.toLocaleString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
      hour: "numeric",
      minute: "2-digit",
      hour12: true,
    });
  }

  function getActionBadge(action) {
    if (action === "CREATE")
      return '<span class="chip chip--success">Created</span>';
    if (action === "UPDATE")
      return '<span class="chip chip--pending">Updated</span>';
    if (action === "DELETE")
      return '<span class="chip chip--danger">Deleted</span>';
    if (action === "REVERT")
      return '<span class="chip chip--neutral">Reverted</span>';
    return '<span class="chip chip--neutral">' + esc(action) + "</span>";
  }

  function load(force) {
    if (loaded && !force) return;
    loaded = true;
    tbody.innerHTML =
      '<tr><td colspan="7" class="data-table__empty">Loading...</td></tr>';

    var params = new URLSearchParams();
    if (searchInput && searchInput.value.trim())
      params.set("q", searchInput.value.trim());
    if (moduleFilter && moduleFilter.value)
      params.set("module", moduleFilter.value);

    fetch("index.php?route=activity-logs&action=list&" + params.toString(), {
      credentials: "same-origin",
      headers: { Accept: "application/json" },
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (!data.success) throw new Error(data.message || "Error");
        var logs = data.logs || [];

        if (!logs.length) {
          tbody.innerHTML =
            '<tr><td colspan="7" class="data-table__empty">No activity logs found.</td></tr>';
          return;
        }

        tbody.innerHTML = logs
          .map(function (log) {
            // Only allow reverting UPDATEs
            var revertBtn = "";
            if (
              log.action === "UPDATE" &&
              log.old_data &&
              log.old_data.trim() !== ""
            ) {
              revertBtn =
                '<button type="button" class="icon-btn" data-action="revert" data-id="' +
                log.id +
                '" title="Revert this change">' +
                '<i class="bi bi-arrow-counterclockwise"></i>' +
                "</button>";
            }

            return (
              "<tr>" +
              '<td class="meta-text">' +
              esc(formatDate(log.created_at)) +
              "</td>" +
              "<td><strong>" +
              esc(log.full_name || log.username || "Unknown") +
              "</strong></td>" +
              "<td>" +
              getActionBadge(log.action) +
              "</td>" +
              '<td><span class="role-badge role-badge--staff">' +
              esc(log.module) +
              "</span></td>" +
              "<td>" +
              esc(log.description) +
              "</td>" +
              '<td class="meta-text">' +
              esc(log.ip_address || "—") +
              "</td>" +
              '<td class="ta-right"><div class="row-actions">' +
              revertBtn +
              "</div></td>" +
              "</tr>"
            );
          })
          .join("");
      })
      .catch(function (err) {
        tbody.innerHTML =
          '<tr><td colspan="7" class="data-table__empty">' +
          esc(err.message) +
          "</td></tr>";
      });
  }

  // Handle Revert click
  if (tbody) {
    tbody.addEventListener("click", async function (e) {
      var btn = e.target.closest('button[data-action="revert"]');
      if (!btn) return;

      var id = btn.dataset.id;

      var confirmed = await window.confirmDialog({
        title: "Revert Change",
        message:
          "Are you sure you want to revert this record to its previous state?",
        confirmLabel: "Revert",
        variant: "warning",
      });

      if (!confirmed) return;

      btn.disabled = true;
      btn.innerHTML =
        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

      fetch("index.php?route=activity-logs&action=revert", {
        method: "POST",
        credentials: "same-origin",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ id: Number(id) }),
      })
        .then(function (r) {
          return r.json().then(function (d) {
            if (!r.ok || !d.success) throw new Error(d.message || "Error");
            return d;
          });
        })
        .then(function () {
          window.showSuccess({
            title: "Reverted",
            message:
              "The record has been successfully reverted to its previous state.",
          });
          load(true); // Reload the table
        })
        .catch(function (err) {
          alert(err.message);
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i>';
        });
    });
  }

  // Wire up filters
  var timer;
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      clearTimeout(timer);
      timer = setTimeout(function () {
        load(true);
      }, 300);
    });
  }
  if (moduleFilter) {
    moduleFilter.addEventListener("change", function () {
      load(true);
    });
  }

  // Listen for section changes from the router
  document.addEventListener("section:changed", function (e) {
    if (e.detail && e.detail.section === "activity-logs") {
      load(true);
    }
  });

  // ✅ Force load immediately when script runs
  load(true);

  // ✅ Fallback: Watch for the router making the section visible
  var observer = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      if (
        mutation.attributeName === "class" &&
        section.classList.contains("is-visible")
      ) {
        load();
      }
    });
  });
  observer.observe(section, { attributes: true });
})();
