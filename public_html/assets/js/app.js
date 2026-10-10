(function () {
  'use strict';

  /* ============================================================
     CONFIRM MODAL
     ============================================================ */

  var confirmModal   = document.getElementById('confirmModal');
  var confirmTitle   = document.getElementById('confirmModalTitle');
  var confirmMessage = document.getElementById('confirmModalMessage');
  var confirmOkBtn   = document.getElementById('confirmModalOk');

  var confirmResolve = null;
  var confirmBound = false;

  window.confirmDialog = function (opts) {
    opts = opts || {};

    return new Promise(function (resolve) {
      if (!confirmModal || !confirmOkBtn) {
        resolve(window.confirm(opts.message || 'Are you sure?'));
        return;
      }

      /* If a previous confirm is still pending, resolve it as cancelled */
      if (confirmResolve) {
        var prev = confirmResolve;
        confirmResolve = null;
        prev(false);
      }

      confirmResolve = resolve;

      if (confirmTitle)   confirmTitle.textContent = opts.title || 'Confirm';
      if (confirmMessage) confirmMessage.textContent = opts.message || 'Are you sure?';

      confirmOkBtn.textContent = opts.confirmLabel || 'Confirm';
      confirmOkBtn.className = 'btn-primary';

      if (opts.variant === 'danger') {
        confirmOkBtn.className = 'btn-danger';
      } else if (opts.variant === 'warning') {
        confirmOkBtn.className = 'btn-warning';
      }

      confirmModal.classList.remove('is-hidden');
      document.body.classList.add('modal-open');

      setTimeout(function () {
        try { confirmOkBtn.focus(); } catch (e) {}
      }, 50);
    });
  };

  function closeConfirm(result) {
    if (confirmModal) {
      confirmModal.classList.add('is-hidden');
      document.body.classList.remove('modal-open');
    }

    if (confirmResolve) {
      var r = confirmResolve;
      confirmResolve = null;
      setTimeout(function () { r(result); }, 0);
    }
  }

  function bindConfirmEvents() {
    if (confirmBound || !confirmModal) return;
    confirmBound = true;

    confirmModal.addEventListener('click', function (e) {
      if (e.target === confirmModal) {
        closeConfirm(false);
        return;
      }
      if (e.target.closest('[data-confirm-close]')) {
        closeConfirm(false);
      }
    });

    if (confirmOkBtn) {
      confirmOkBtn.addEventListener('click', function (e) {
        e.preventDefault();
        closeConfirm(true);
      });
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && confirmModal && !confirmModal.classList.contains('is-hidden')) {
        closeConfirm(false);
      }
    });
  }

  /* ============================================================
     SUCCESS MODAL
     ============================================================ */

  window.showSuccess = function (opts) {
    opts = opts || {};

    var modal   = document.getElementById('successModal');
    var titleEl = document.getElementById('successModalTitle');
    var msgEl   = document.getElementById('successModalMessage');

    if (!modal) {
      window.alert(opts.message || 'Success');
      return;
    }

    if (titleEl) titleEl.textContent = opts.title || 'Success';
    if (msgEl)   msgEl.textContent = opts.message || 'Saved successfully.';

    /* Aggressive show: remove ALL hidden indicators */
    modal.classList.remove('is-hidden');
    modal.removeAttribute('hidden');
    modal.style.display = 'flex';
    modal.style.opacity = '1';
    modal.style.visibility = 'visible';
    modal.style.zIndex = '2147483000';

    document.body.classList.add('modal-open');

    if (window.__successTimer) clearTimeout(window.__successTimer);

    var duration = typeof opts.duration === 'number' ? opts.duration : 2500;

    if (duration > 0) {
      window.__successTimer = setTimeout(function () {
        modal.classList.add('is-hidden');
        modal.style.display = '';
        modal.style.opacity = '';
        modal.style.visibility = '';
        document.body.classList.remove('modal-open');
        window.__successTimer = null;

        if (typeof opts.onClose === 'function') opts.onClose();
      }, duration);
    }
  };

  /* Click on backdrop dismisses the success modal early */
  document.addEventListener('click', function (e) {
    var modal = document.getElementById('successModal');
    if (modal && e.target === modal) {
      modal.classList.add('is-hidden');
      modal.style.display = '';
      modal.style.opacity = '';
      modal.style.visibility = '';
      document.body.classList.remove('modal-open');
      if (window.__successTimer) {
        clearTimeout(window.__successTimer);
        window.__successTimer = null;
      }
    }
  });

  /* ============================================================
     BOOT
     ============================================================ */

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindConfirmEvents, { once: true });
  } else {
    bindConfirmEvents();
  }
})();