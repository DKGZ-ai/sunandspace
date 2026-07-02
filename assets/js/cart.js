(function () {
  function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function updateBadge(count) {
    var badge = document.getElementById('ssCartBadge');
    if (!badge) return;
    badge.textContent = String(count);
    if (count > 0) {
      badge.classList.remove('ss-cart-badge--hidden');
    } else {
      badge.classList.add('ss-cart-badge--hidden');
    }
  }

  function postCart(action, productId, qty) {
    var formData = new FormData();
    formData.append('action', action);
    formData.append('product_id', String(productId));
    formData.append('qty', String(qty || 1));
    var token = getCsrfToken();
    if (token) {
      formData.append('csrf_token', token);
    }
    return fetch('api/cart.php', { method: 'POST', body: formData }).then(function (r) {
      return r.json();
    });
  }

  function isCartIconButton(btn) {
    return btn && (btn.classList.contains('ss-cart-icon-btn') || btn.querySelector('svg'));
  }

  var cartToastTimer;

  function showCartToast(message) {
    var toast = document.getElementById('ssCartToast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'ssCartToast';
      toast.className = 'ss-cart-toast';
      toast.setAttribute('role', 'status');
      toast.setAttribute('aria-live', 'polite');
      document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.classList.add('ss-cart-toast--visible');
    if (cartToastTimer) {
      window.clearTimeout(cartToastTimer);
    }
    cartToastTimer = window.setTimeout(function () {
      toast.classList.remove('ss-cart-toast--visible');
    }, 2200);
  }

  function showAddToCartFeedback(btn) {
    if (!btn) {
      return;
    }
    if (isCartIconButton(btn)) {
      btn.classList.add('ss-cart-icon-btn--success');
      window.setTimeout(function () {
        btn.classList.remove('ss-cart-icon-btn--success');
        btn.disabled = false;
      }, 800);
      return;
    }
    var origText = btn.textContent;
    btn.textContent = '✓';
    window.setTimeout(function () {
      btn.textContent = origText;
      btn.disabled = false;
    }, 800);
  }

  function showAlreadyInCartFeedback(btn) {
    showCartToast('Already added to cart');
    if (!btn) {
      return;
    }
    if (isCartIconButton(btn)) {
      btn.classList.add('ss-cart-icon-btn--already');
      window.setTimeout(function () {
        btn.classList.remove('ss-cart-icon-btn--already');
        btn.disabled = false;
      }, 1200);
      return;
    }
    var origText = btn.textContent;
    btn.textContent = 'Already added';
    window.setTimeout(function () {
      btn.textContent = origText;
      btn.disabled = false;
    }, 1500);
  }

  function addToCart(productId, btn) {
    if (btn) {
      btn.disabled = true;
    }

    postCart('add', productId, 1)
      .then(function (data) {
        if (data.ok) {
          updateBadge(data.cartCount);
          if (btn) {
            if (data.alreadyInCart) {
              showAlreadyInCartFeedback(btn);
            } else {
              showAddToCartFeedback(btn);
            }
          }
        } else if (btn) {
          btn.disabled = false;
        }
      })
      .catch(function () {
        if (btn) btn.disabled = false;
      });
  }

  function buyNow(productId, btn) {
    if (btn) {
      btn.disabled = true;
    }

    postCart('buy_now', productId, 1)
      .then(function (data) {
        if (data.ok) {
          updateBadge(data.cartCount);
          if (data.redirect) {
            window.location.href = data.redirect;
          }
        } else if (btn) {
          btn.disabled = false;
        }
      })
      .catch(function () {
        if (btn) btn.disabled = false;
      });
  }

  document.querySelectorAll('[data-add-cart]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var id = btn.getAttribute('data-product-id');
      if (id) addToCart(id, btn);
    });
  });

  document.querySelectorAll('[data-buy-now]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var id = btn.getAttribute('data-product-id');
      if (id) buyNow(id, btn);
    });
  });

  document.querySelectorAll('.ss-plus').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
    });
  });

  function initQtyAutoSubmit() {
    var forms = document.querySelectorAll('.ss-qty-form');
    if (!forms.length) {
      return;
    }
    Array.prototype.forEach.call(forms, function (form) {
      var input = form.querySelector('.ss-qty-input');
      if (!input) {
        return;
      }
      var submitTimeout;
      function scheduleSubmit(delay) {
        if (submitTimeout) {
          clearTimeout(submitTimeout);
        }
        submitTimeout = setTimeout(function () {
          submitTimeout = null;
          form.submit();
        }, delay);
      }
      input.addEventListener('input', function () {
        scheduleSubmit(400);
      });
      input.addEventListener('blur', function () {
        scheduleSubmit(0);
      });
    });
  }

  function formatPeso(cents) {
    var amount = Math.round(cents / 100);
    return '₱' + amount.toLocaleString('en-US');
  }

  function initCartSelection() {
    var selectAll = document.getElementById('ssSelectAll');
    var lineChecks = document.querySelectorAll('.ss-cart-line-select');
    var totalEl = document.getElementById('ssSelectedTotal');
    if (!lineChecks.length || !totalEl) {
      return;
    }

    function updateTotal() {
      var cents = 0;
      var allChecked = true;
      lineChecks.forEach(function (cb) {
        var row = cb.closest('tr');
        if (cb.checked && row) {
          cents += parseInt(row.getAttribute('data-line-cents') || '0', 10);
        } else {
          allChecked = false;
        }
      });
      totalEl.textContent = formatPeso(cents);
      if (selectAll) {
        selectAll.checked = allChecked;
      }
    }

    if (selectAll) {
      selectAll.addEventListener('change', function () {
        lineChecks.forEach(function (cb) {
          cb.checked = selectAll.checked;
        });
        updateTotal();
      });
    }

    lineChecks.forEach(function (cb) {
      cb.addEventListener('change', updateTotal);
    });

    var checkoutForm = document.getElementById('ssCartCheckoutForm');
    if (checkoutForm) {
      checkoutForm.addEventListener('submit', function (e) {
        var anyChecked = false;
        lineChecks.forEach(function (cb) {
          if (cb.checked) {
            anyChecked = true;
          }
        });
        if (!anyChecked) {
          e.preventDefault();
          window.alert('Select at least one item to checkout.');
        }
      });
    }

    updateTotal();
  }

  function onReady() {
    initQtyAutoSubmit();
    initCartSelection();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', onReady);
  } else {
    onReady();
  }
})();
