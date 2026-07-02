(function () {
  var orderTable = document.getElementById('adminOrderTable');
  if (!orderTable) return;

  function encodeFormBody(params) {
    return Object.keys(params).map(function (key) {
      return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
    }).join('&');
  }

  function updateTrackingCell(row, trackingNumber) {
    if (!row) return;
    var cell = row.querySelector('[data-tracking-cell]');
    if (!cell) return;
    cell.textContent = trackingNumber || '—';
  }

  orderTable.addEventListener('change', function (e) {
    var target = e.target;
    if (target.tagName !== 'SELECT' || !target.dataset.orderId) return;

    var orderId = target.dataset.orderId;
    var previousStatus = target.dataset.currentStatus || '';
    var newStatus = target.value;
    var trackingNumber = '';

    if (newStatus === 'in_progress' && newStatus !== previousStatus) {
      var entered = window.prompt('Optional tracking number (leave blank to skip):');
      if (entered === null) {
        target.value = previousStatus;
        return;
      }
      trackingNumber = entered.trim();
    }

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (!csrfMeta) {
      target.value = previousStatus;
      alert('Missing security token. Please refresh the page.');
      return;
    }

    fetch('ajax-update-order-status.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: encodeFormBody({
        order_id: orderId,
        status: newStatus,
        tracking_number: trackingNumber,
        csrf_token: csrfMeta.content,
      }),
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (data.ok) {
          target.dataset.currentStatus = data.status || newStatus;
          updateTrackingCell(target.closest('tr'), data.tracking_number || '');
        } else {
          target.value = previousStatus;
          alert(data.message || 'Could not update status.');
        }
      })
      .catch(function () {
        target.value = previousStatus;
        alert('Network error. Could not update status.');
      });
  });
})();
