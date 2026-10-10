(function () {
  'use strict';

  function SearchableSelect(opts) {
    this.container   = opts.container;
    this.placeholder = opts.placeholder || 'Select...';
    this.onChange    = opts.onChange || function () {};
    this.items       = [];
    this.value       = '';
    this.open        = false;
    this.highlight   = -1;
    this.disabled    = false;

    this._buildDOM();
    this._bindEvents();
  }

  SearchableSelect.prototype._buildDOM = function () {
    var wrap = document.createElement('div');
    wrap.className = 'searchable-select';

    var input = document.createElement('input');
    input.type = 'text';
    input.className = 'searchable-select__input';
    input.placeholder = this.placeholder;
    input.autocomplete = 'off';

    var arrow = document.createElement('i');
    arrow.className = 'bi bi-chevron-down searchable-select__arrow';

    wrap.appendChild(input);
    wrap.appendChild(arrow);

    this.container.innerHTML = '';
    this.container.appendChild(wrap);

    /* Menu is appended to <body> so it escapes any parent stacking context */
    var menu = document.createElement('div');
    menu.className = 'searchable-select__menu';
    menu.style.display = 'none';
    document.body.appendChild(menu);

    this.wrap = wrap;
    this.input = input;
    this.menu = menu;
  };

  SearchableSelect.prototype._bindEvents = function () {
    var self = this;

    this.input.addEventListener('focus', function () {
      if (self.disabled) return;
      self.openMenu();
    });

    this.input.addEventListener('input', function () {
      self.openMenu();
    });

    this.input.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        self.moveHighlight(1);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        self.moveHighlight(-1);
      } else if (e.key === 'Enter') {
        if (self.highlight >= 0) {
          e.preventDefault();
          var filtered = self._filteredItems(self.input.value);
          var item = filtered[self.highlight];
          if (item) self.selectItem(item);
        }
      } else if (e.key === 'Escape') {
        self.closeMenu();
      }
    });

    this.menu.addEventListener('mousedown', function (e) {
      /* Prevent blur before click */
      e.preventDefault();
    });

    this.menu.addEventListener('click', function (e) {
      var btn = e.target.closest('.searchable-select__option');
      if (!btn) return;
      var idx = Number(btn.dataset.index);
      var filtered = self._filteredItems(self.input.value);
      var item = filtered[idx];
      if (item) self.selectItem(item);
    });

    document.addEventListener('click', function (e) {
      if (!self.wrap.contains(e.target) && !self.menu.contains(e.target)) {
        self.closeMenu();
      }
    });

    /* Reposition on scroll / resize while open */
    window.addEventListener('resize', function () {
      if (self.open) self._positionMenu();
    });
    window.addEventListener('scroll', function () {
      if (self.open) self._positionMenu();
    }, true);
  };

  SearchableSelect.prototype._positionMenu = function () {
    var rect = this.input.getBoundingClientRect();
    this.menu.style.position = 'fixed';
    this.menu.style.top = (rect.bottom + 4) + 'px';
    this.menu.style.left = rect.left + 'px';
    this.menu.style.width = rect.width + 'px';
    this.menu.style.zIndex = '2147483000';
  };

  SearchableSelect.prototype._filteredItems = function (query) {
    if (!query || !query.trim()) return this.items;
    var q = query.toLowerCase().trim();
    return this.items.filter(function (item) {
      return String(item.label).toLowerCase().indexOf(q) !== -1;
    });
  };

  SearchableSelect.prototype.openMenu = function () {
    if (this.disabled) return;
    this.open = true;
    this.wrap.classList.add('is-open');
    this._positionMenu();
    this.menu.style.display = 'block';
    this.renderMenu(this.input.value);
  };

  SearchableSelect.prototype.closeMenu = function () {
    this.open = false;
    this.wrap.classList.remove('is-open');
    this.menu.style.display = 'none';
    this.highlight = -1;
  };

  SearchableSelect.prototype.renderMenu = function (query) {
    var filtered = this._filteredItems(query);

    if (!filtered.length) {
      this.menu.innerHTML = '<div class="searchable-select__empty">No matches found</div>';
      return;
    }

    var selectedValue = String(this.value);
    var self = this;

    this.menu.innerHTML = filtered.map(function (item, i) {
      var isSelected = String(item.value) === selectedValue;
      var isHighlighted = i === self.highlight;
      return '<button type="button" ' +
             'class="searchable-select__option' +
             (isSelected ? ' is-selected' : '') +
             (isHighlighted ? ' is-highlighted' : '') +
             '" data-index="' + i + '">' +
             self._esc(item.label) +
             '</button>';
    }).join('');
  };

  SearchableSelect.prototype._esc = function (v) {
    var d = document.createElement('div');
    d.textContent = String(v == null ? '' : v);
    return d.innerHTML;
  };

  SearchableSelect.prototype.moveHighlight = function (delta) {
    var filtered = this._filteredItems(this.input.value);
    if (!filtered.length) return;

    this.highlight = (this.highlight + delta + filtered.length) % filtered.length;
    this.renderMenu(this.input.value);

    var el = this.menu.querySelector('.is-highlighted');
    if (el && el.scrollIntoView) {
      el.scrollIntoView({ block: 'nearest' });
    }
  };

  SearchableSelect.prototype.selectItem = function (item) {
    this.value = item.value;
    this.input.value = item.label;
    this.closeMenu();
    this.onChange(item.value, item.label, item);
  };

  SearchableSelect.prototype.setItems = function (items) {
    this.items = items.map(function (it) {
      return { value: it.value, label: it.label, raw: it.raw || it };
    });
    this.input.value = '';
    this.value = '';
    this.renderMenu('');
  };

  SearchableSelect.prototype.setValue = function (value) {
    if (value === '' || value == null) {
      this.value = '';
      this.input.value = '';
      return;
    }
    var match = this.items.find(function (it) {
      return String(it.value) === String(value);
    });
    if (match) {
      this.value = match.value;
      this.input.value = match.label;
    }
  };

  SearchableSelect.prototype.getValue = function () {
    return this.value;
  };

  SearchableSelect.prototype.clear = function () {
    this.value = '';
    this.input.value = '';
  };

  SearchableSelect.prototype.setDisabled = function (disabled) {
    this.disabled = !!disabled;
    this.input.disabled = !!disabled;
    if (disabled) this.closeMenu();
  };

  window.SearchableSelect = SearchableSelect;
})();