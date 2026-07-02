(function () {
  var tbody = document.getElementById('adminProductSortable');
  if (!tbody) return;

  var dragRow = null;
  var activeCell = null;
  var sorting = false;
  var statusEl = document.getElementById('adminProductSortStatus');
  var lastOrderJson = '';

  function setStatus(message, isError) {
    if (!statusEl) return;
    statusEl.textContent = message;
    statusEl.hidden = message === '';
    statusEl.classList.toggle('is-error', !!isError);
  }

  function collectOrder() {
    return Array.prototype.map.call(
      tbody.querySelectorAll('tr[data-product-id]'),
      function (row) {
        return row.dataset.productId;
      }
    );
  }

  function encodeFormBody(params) {
    return Object.keys(params)
      .map(function (key) {
        return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
      })
      .join('&');
  }

  function saveOrder() {
    var ids = collectOrder();
    var payload = JSON.stringify(ids);
    if (payload === lastOrderJson) return;
    lastOrderJson = payload;

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (!csrfMeta) {
      setStatus('Missing security token. Refresh the page.', true);
      return;
    }

    setStatus('Saving order…', false);

    fetch('ajax-reorder-products.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: encodeFormBody({
        product_ids: payload,
        csrf_token: csrfMeta.content,
      }),
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (data.ok) {
          setStatus('Storefront order saved.', false);
          window.setTimeout(function () {
            if (statusEl && statusEl.textContent === 'Storefront order saved.') {
              setStatus('', false);
            }
          }, 2500);
        } else {
          setStatus(data.message || 'Could not save order.', true);
          lastOrderJson = '';
        }
      })
      .catch(function () {
        setStatus('Network error. Could not save order.', true);
        lastOrderJson = '';
      });
  }

  function clearDragOver() {
    tbody.querySelectorAll('.is-drag-over').forEach(function (row) {
      row.classList.remove('is-drag-over');
    });
  }

  function moveRowToY(clientY) {
    if (!dragRow) return;

    var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-product-id]'));
    var target = null;

    rows.some(function (row) {
      if (row === dragRow) return false;
      var rect = row.getBoundingClientRect();
      if (clientY >= rect.top && clientY <= rect.bottom) {
        target = row;
        return true;
      }
      return false;
    });

    clearDragOver();

    if (!target) {
      if (rows.length > 0 && clientY < rows[0].getBoundingClientRect().top) {
        tbody.insertBefore(dragRow, rows[0]);
      } else if (rows.length > 0) {
        var last = rows[rows.length - 1];
        if (last !== dragRow && clientY > last.getBoundingClientRect().bottom) {
          tbody.appendChild(dragRow);
        }
      }
      return;
    }

    target.classList.add('is-drag-over');
    var rect = target.getBoundingClientRect();
    var after = clientY > rect.top + rect.height / 2;
    if (after) {
      tbody.insertBefore(dragRow, target.nextSibling);
    } else {
      tbody.insertBefore(dragRow, target);
    }
  }

  function eventClientY(ev) {
    if (ev.touches && ev.touches.length) {
      return ev.touches[0].clientY;
    }
    if (ev.changedTouches && ev.changedTouches.length) {
      return ev.changedTouches[0].clientY;
    }
    return ev.clientY;
  }

  function stopDrag() {
    if (!sorting) return;

    document.body.classList.remove('ss-admin-is-sorting');
    if (dragRow) {
      dragRow.classList.remove('is-dragging');
    }
    if (activeCell) {
      activeCell.classList.remove('is-grabbing');
      var handle = activeCell.querySelector('.ss-admin-drag-handle');
      if (handle) {
        handle.setAttribute('aria-grabbed', 'false');
      }
    }
    clearDragOver();
    dragRow = null;
    activeCell = null;
    sorting = false;
    saveOrder();
  }

  function onMove(ev) {
    if (!sorting) return;
    ev.preventDefault();
    moveRowToY(eventClientY(ev));
  }

  function onEnd() {
    document.removeEventListener('mousemove', onMove);
    document.removeEventListener('mouseup', onEnd);
    document.removeEventListener('touchmove', onMove);
    document.removeEventListener('touchend', onEnd);
    document.removeEventListener('touchcancel', onEnd);
    stopDrag();
  }

  function startDrag(ev, cell) {
    if (sorting) return;
    if (ev.type === 'mousedown' && ev.button !== 0) return;

    var row = cell.closest('tr[data-product-id]');
    if (!row) return;

    ev.preventDefault();
    sorting = true;
    dragRow = row;
    activeCell = cell;
    cell.classList.add('is-grabbing');
    var handle = cell.querySelector('.ss-admin-drag-handle');
    if (handle) {
      handle.setAttribute('aria-grabbed', 'true');
    }
    row.classList.add('is-dragging');
    document.body.classList.add('ss-admin-is-sorting');

    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onEnd);
    document.addEventListener('touchmove', onMove, { passive: false });
    document.addEventListener('touchend', onEnd);
    document.addEventListener('touchcancel', onEnd);
  }

  tbody.querySelectorAll('.ss-admin-drag-cell').forEach(function (cell) {
    cell.addEventListener('mousedown', function (ev) {
      startDrag(ev, cell);
    });
    cell.addEventListener('touchstart', function (ev) {
      startDrag(ev, cell);
    }, { passive: false });
  });

  lastOrderJson = JSON.stringify(collectOrder());
})();
