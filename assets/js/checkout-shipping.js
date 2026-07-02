(function () {
  'use strict';

  var root = document.getElementById('ssCheckoutShippingRoot');
  if (!root) {
    return;
  }

  var form = root.closest('form');
  if (!form) {
    return;
  }

  var deliveryMethodInputs = form.querySelectorAll('input[name="delivery_method"]');
  var deliveryMethodFieldset = form.querySelector('.ss-delivery-method');
  var sameDayPanel = document.getElementById('ssCheckoutSameDayPanel');
  var pickupPanel = document.getElementById('ssCheckoutPickupPanel');
  var jtShippingBlock = document.getElementById('ssCheckoutJtShipping');
  var provinceSelect = document.getElementById('ssShippingProvince');
  var citySelect = document.getElementById('ssShippingCity');
  var shippingCentsInput = document.getElementById('ssShippingCents');
  var shippingCityInput = document.getElementById('ssShippingCityValue');
  var shippingProvinceInput = document.getElementById('ssShippingProvinceValue');
  var shippingProvinceIdInput = document.getElementById('ssShippingProvinceId');
  var subtotalEl = document.getElementById('ssCheckoutSubtotal');
  var shippingLabelEl = document.getElementById('ssCheckoutShippingLabel');
  var shippingEl = document.getElementById('ssCheckoutShipping');
  var shippingRowEl = document.getElementById('ssCheckoutShippingRow');
  var carrierValueEl = document.getElementById('ssCheckoutCarrierValue');
  var summaryCarrierEl = document.getElementById('ssCheckoutSummaryCarrier');
  var carrierSameDay = root.getAttribute('data-carrier-same-day') || 'Lalamove';
  var carrierJt = root.getAttribute('data-carrier-jt') || 'J&T Express';
  var carrierPickup = root.getAttribute('data-carrier-pickup') || 'Store pickup';
  var totalEl = document.getElementById('ssCheckoutTotal');
  var bankTotalEl = document.getElementById('ssBankTotal');
  var paymentMethodSelect = document.getElementById('ssPaymentMethod');
  var paymentMethodWrap = document.getElementById('ssPaymentMethodWrap');
  var paymentMethodPickup = document.getElementById('ssPaymentMethodPickup');
  var paymentReceiptInput = document.getElementById('ssPaymentReceipt');
  var addressInput = document.getElementById('ssShippingAddress');
  var addressRequiredMark = document.getElementById('ssShippingAddressRequired');
  var addressHint = document.getElementById('ssShippingAddressHint');
  var validationAlert = document.getElementById('ssCheckoutValidationAlert');
  var placeOrderBtn = form.querySelector('button[type="submit"]');
  var quoteTimer = null;
  var isQuoting = false;

  var subtotalCents = parseInt(root.getAttribute('data-subtotal-cents') || '0', 10);
  var csrfToken = document.querySelector('meta[name="csrf-token"]');
  var token = csrfToken ? csrfToken.getAttribute('content') : '';

  function formatPrice(cents) {
    var amount = Math.round(cents) / 100;
    return '₱' + amount.toLocaleString('en-PH', { maximumFractionDigits: 0 });
  }

  function selectedDeliveryMethod() {
    var selected = form.querySelector('input[name="delivery_method"]:checked');
    return selected ? selected.value : 'jt_nationwide';
  }

  function isSameDayDelivery() {
    return selectedDeliveryMethod() === 'same_day_local';
  }

  function isPickupDelivery() {
    return selectedDeliveryMethod() === 'cash_on_pickup';
  }

  function isJtDelivery() {
    return selectedDeliveryMethod() === 'jt_nationwide';
  }

  function activePaymentMethod() {
    if (isPickupDelivery()) {
      return 'cop';
    }
    return paymentMethodSelect ? paymentMethodSelect.value : 'cod';
  }

  function syncAddressRequired() {
    var required = !isPickupDelivery();
    if (addressInput) {
      addressInput.required = required;
    }
    if (addressRequiredMark) {
      addressRequiredMark.classList.toggle('hidden', !required);
    }
    if (addressHint) {
      addressHint.classList.toggle('hidden', required);
    }
  }

  function syncPaymentMethodForDelivery() {
    var pickup = isPickupDelivery();
    if (paymentMethodWrap) {
      paymentMethodWrap.classList.toggle('hidden', pickup);
    }
    if (paymentMethodSelect) {
      paymentMethodSelect.disabled = pickup;
    }
    if (paymentMethodPickup) {
      paymentMethodPickup.disabled = !pickup;
    }
    if (pickup && paymentReceiptInput) {
      paymentReceiptInput.required = false;
    }
    var bankPanel = document.getElementById('ss-bank-panel');
    if (pickup && bankPanel) {
      bankPanel.classList.add('hidden');
    } else if (paymentMethodSelect && bankPanel) {
      bankPanel.classList.toggle('hidden', paymentMethodSelect.value !== 'bank');
    }
  }

  function fieldContainer(field) {
    if (!field) {
      return null;
    }
    if (field.closest) {
      return field.closest('label') || field.closest('fieldset') || field;
    }
    return field;
  }

  function clearFieldErrors() {
    form.querySelectorAll('.ss-field-error').forEach(function (node) {
      node.classList.remove('ss-field-error');
    });
    form.querySelectorAll('[aria-invalid="true"]').forEach(function (node) {
      node.removeAttribute('aria-invalid');
    });
    if (jtShippingBlock) {
      jtShippingBlock.classList.remove('ss-field-error');
    }
    if (shippingRowEl) {
      shippingRowEl.classList.remove('ss-field-error');
    }
  }

  function markFieldError(field) {
    var container = fieldContainer(field);
    if (container) {
      container.classList.add('ss-field-error');
    }
    if (field && field.setAttribute) {
      field.setAttribute('aria-invalid', 'true');
    }
  }

  function hideValidationAlert() {
    if (!validationAlert) {
      return;
    }
    validationAlert.textContent = '';
    validationAlert.classList.add('hidden');
  }

  function showValidationAlert(message) {
    if (!validationAlert) {
      return;
    }
    validationAlert.textContent = message;
    validationAlert.classList.remove('hidden');
    validationAlert.classList.add('ss-alert-warning');
    validationAlert.classList.remove('ss-alert-error');
  }

  function isValidEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
  }

  function validateCheckoutForm() {
    var issues = [];
    var fields = [];
    var firstField = null;

    function addIssue(field, code, message) {
      if (!firstField && field) {
        firstField = field;
      }
      if (field && fields.indexOf(field) === -1) {
        fields.push(field);
      }
      issues.push({ code: code, message: message });
    }

    var nameInput = form.querySelector('input[name="shipping_name"]');
    var emailInput = form.querySelector('input[name="shipping_email"]');

    if (!nameInput || nameInput.value.trim() === '') {
      addIssue(nameInput, 'name', 'Please enter your full name to continue.');
    }

    if (!emailInput || emailInput.value.trim() === '') {
      addIssue(emailInput, 'email', 'Please enter your email address to continue.');
    } else if (!isValidEmail(emailInput.value.trim())) {
      addIssue(emailInput, 'email_invalid', 'Please enter a valid email address to continue.');
    }

    if (!form.querySelector('input[name="delivery_method"]:checked')) {
      addIssue(deliveryMethodFieldset, 'delivery_method', 'Please select a delivery method to continue.');
    }

    if (!isPickupDelivery() && (!addressInput || addressInput.value.trim() === '')) {
      addIssue(addressInput, 'address', 'Please enter your delivery address to continue.');
    }

    if (isJtDelivery()) {
      if (!provinceSelect || provinceSelect.value === '') {
        addIssue(provinceSelect, 'province', 'Please select your province to continue.');
      }
      if (!citySelect || citySelect.value === '') {
        addIssue(citySelect, 'city', 'Please select your city to continue.');
      }
      if (!shippingCentsInput || shippingCentsInput.value === '') {
        if (jtShippingBlock && fields.indexOf(jtShippingBlock) === -1) {
          fields.push(jtShippingBlock);
        }
        if (shippingRowEl && fields.indexOf(shippingRowEl) === -1) {
          fields.push(shippingRowEl);
        }
        if (provinceSelect && provinceSelect.value !== '' && citySelect && citySelect.value !== '') {
          addIssue(citySelect, 'quote_pending', 'Please wait for the J&T shipping fee to finish calculating.');
        } else {
          addIssue(provinceSelect || citySelect, 'shipping_quote', 'Please select your province and city to calculate J&T shipping.');
        }
      }
    }

    if (activePaymentMethod() === 'bank') {
      var hasReceipt = paymentReceiptInput
        && paymentReceiptInput.files
        && paymentReceiptInput.files.length > 0;
      if (!hasReceipt) {
        addIssue(paymentReceiptInput, 'receipt', 'Please upload your payment receipt to continue with bank transfer.');
      }
    }

    var bannerMessage = 'Please fill in the highlighted fields to continue.';
    if (issues.length === 1) {
      bannerMessage = issues[0].message;
    } else if (issues.length > 1) {
      bannerMessage = 'Please complete the highlighted fields to continue. ' + issues[0].message;
    }

    return {
      valid: issues.length === 0,
      message: bannerMessage,
      fields: fields,
      firstField: firstField || fields[0] || null,
    };
  }

  function setShippingDisplay(cents, label) {
    if (!shippingEl) {
      return;
    }
    if (cents > 0) {
      shippingEl.textContent = formatPrice(cents);
    } else if (label) {
      shippingEl.textContent = label;
    } else {
      shippingEl.textContent = 'Select city to calculate';
    }
  }

  function setTotal(cents) {
    if (totalEl) {
      totalEl.textContent = formatPrice(cents);
    }
    if (bankTotalEl) {
      bankTotalEl.textContent = formatPrice(cents);
    }
  }

  function setJtFieldsRequired(required) {
    if (provinceSelect) {
      provinceSelect.required = required;
    }
    if (citySelect) {
      citySelect.required = required;
    }
  }

  function setPlaceOrderDisabled(disabled) {
    if (placeOrderBtn) {
      placeOrderBtn.disabled = disabled;
    }
  }

  function clearQuote() {
    if (shippingCentsInput) {
      shippingCentsInput.value = '';
    }
    setShippingDisplay(0, 'Select city to calculate');
    setTotal(subtotalCents);
    setPlaceOrderDisabled(isQuoting);
  }

  function setCarrierLabel(carrier) {
    if (carrierValueEl) {
      carrierValueEl.textContent = carrier;
    }
    if (summaryCarrierEl) {
      summaryCarrierEl.textContent = carrier;
    }
  }

  function applySameDayMode() {
    if (sameDayPanel) {
      sameDayPanel.classList.remove('hidden');
    }
    if (pickupPanel) {
      pickupPanel.classList.add('hidden');
    }
    if (jtShippingBlock) {
      jtShippingBlock.classList.add('hidden');
    }
    setCarrierLabel(carrierSameDay);
    if (shippingCentsInput) {
      shippingCentsInput.value = '0';
    }
    if (shippingCityInput) {
      shippingCityInput.value = '';
    }
    if (shippingProvinceInput) {
      shippingProvinceInput.value = '';
    }
    setJtFieldsRequired(false);
    setShippingDisplay(0, 'Arranged separately');
    setTotal(subtotalCents);
    setPlaceOrderDisabled(isQuoting);
    syncAddressRequired();
    syncPaymentMethodForDelivery();
    if (shippingRowEl) {
      shippingRowEl.classList.remove('ss-field-error');
    }
    if (jtShippingBlock) {
      jtShippingBlock.classList.remove('ss-field-error');
    }
  }

  function applyPickupMode() {
    if (sameDayPanel) {
      sameDayPanel.classList.add('hidden');
    }
    if (pickupPanel) {
      pickupPanel.classList.remove('hidden');
    }
    if (jtShippingBlock) {
      jtShippingBlock.classList.add('hidden');
    }
    setCarrierLabel(carrierPickup);
    if (shippingCentsInput) {
      shippingCentsInput.value = '0';
    }
    if (shippingCityInput) {
      shippingCityInput.value = '';
    }
    if (shippingProvinceInput) {
      shippingProvinceInput.value = '';
    }
    setJtFieldsRequired(false);
    setShippingDisplay(0, 'Free');
    setTotal(subtotalCents);
    setPlaceOrderDisabled(isQuoting);
    syncAddressRequired();
    syncPaymentMethodForDelivery();
    if (shippingRowEl) {
      shippingRowEl.classList.remove('ss-field-error');
    }
    if (jtShippingBlock) {
      jtShippingBlock.classList.remove('ss-field-error');
    }
  }

  function applyJtMode() {
    if (sameDayPanel) {
      sameDayPanel.classList.add('hidden');
    }
    if (pickupPanel) {
      pickupPanel.classList.add('hidden');
    }
    if (jtShippingBlock) {
      jtShippingBlock.classList.remove('hidden');
    }
    setCarrierLabel(carrierJt);
    setJtFieldsRequired(true);
    syncAddressRequired();
    syncPaymentMethodForDelivery();
    if (!provinceSelect.value || !citySelect.value) {
      clearQuote();
      return;
    }
    requestQuote();
  }

  function applyDeliveryMethod() {
    hideValidationAlert();
    if (isSameDayDelivery()) {
      applySameDayMode();
      return;
    }
    if (isPickupDelivery()) {
      applyPickupMode();
      return;
    }
    applyJtMode();
  }

  function fetchJson(url) {
    return fetch(url, { credentials: 'same-origin' }).then(function (res) {
      return res.json().then(function (data) {
        if (!res.ok || !data.ok) {
          throw new Error(data.error || 'Request failed.');
        }
        return data;
      });
    });
  }

  function loadProvinces() {
    return fetchJson('api/shipping.php?action=provinces').then(function (data) {
      var selectedId = provinceSelect.getAttribute('data-selected-id') || '';
      provinceSelect.innerHTML = '<option value="">Select province</option>';
      data.provinces.forEach(function (province) {
        var option = document.createElement('option');
        option.value = String(province.id);
        option.textContent = province.label;
        option.setAttribute('data-name', province.name);
        if (String(province.id) === selectedId) {
          option.selected = true;
        }
        provinceSelect.appendChild(option);
      });
    });
  }

  function loadCities(provinceId, selectedCity) {
    citySelect.innerHTML = '<option value="">Select city</option>';
    citySelect.disabled = true;
    if (!isJtDelivery()) {
      clearQuote();
    }

    if (!provinceId) {
      return Promise.resolve();
    }

    return fetchJson('api/shipping.php?action=cities&province_id=' + encodeURIComponent(provinceId))
      .then(function (data) {
        data.cities.forEach(function (city) {
          var option = document.createElement('option');
          option.value = city.name;
          option.textContent = city.label;
          if (selectedCity && city.name === selectedCity) {
            option.selected = true;
          }
          citySelect.appendChild(option);
        });
        citySelect.disabled = false;
      });
  }

  function syncProvinceFields() {
    var option = provinceSelect.options[provinceSelect.selectedIndex];
    if (shippingProvinceIdInput) {
      shippingProvinceIdInput.value = provinceSelect.value;
    }
    if (shippingProvinceInput && option) {
      shippingProvinceInput.value = option.getAttribute('data-name') || '';
    }
  }

  function requestQuote() {
    if (!isJtDelivery()) {
      return;
    }

    syncProvinceFields();

    var provinceId = provinceSelect.value;
    var city = citySelect.value;

    if (!provinceId || !city) {
      clearQuote();
      return;
    }

    if (shippingCityInput) {
      shippingCityInput.value = city;
    }

    isQuoting = true;
    setShippingDisplay(0, 'Calculating…');
    setPlaceOrderDisabled(true);

    var body = new FormData();
    body.append('action', 'quote');
    body.append('csrf_token', token);
    body.append('receiver_city', city);
    body.append('receiver_province_id', provinceId);

    fetch('api/shipping.php', {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
    })
      .then(function (res) {
        return res.json().then(function (data) {
          if (!res.ok || !data.ok) {
            throw new Error(data.error || 'Could not get shipping quote.');
          }
          return data;
        });
      })
      .then(function (data) {
        if (shippingCentsInput) {
          shippingCentsInput.value = String(data.shippingCents);
        }
        setShippingDisplay(data.shippingCents, '');
        setTotal(data.totalCents);
        if (shippingRowEl) {
          shippingRowEl.classList.remove('ss-field-error');
        }
      })
      .catch(function (err) {
        if (shippingCentsInput) {
          shippingCentsInput.value = '';
        }
        setShippingDisplay(0, err.message || 'Quote unavailable');
        setTotal(subtotalCents);
      })
      .finally(function () {
        isQuoting = false;
        setPlaceOrderDisabled(false);
      });
  }

  function scheduleQuote() {
    if (!isJtDelivery()) {
      return;
    }
    if (quoteTimer) {
      clearTimeout(quoteTimer);
    }
    quoteTimer = setTimeout(requestQuote, 250);
  }

  function clearErrorForField(field) {
    var container = fieldContainer(field);
    if (container) {
      container.classList.remove('ss-field-error');
    }
    if (field && field.removeAttribute) {
      field.removeAttribute('aria-invalid');
    }
    if (field === provinceSelect || field === citySelect) {
      if (jtShippingBlock) {
        jtShippingBlock.classList.remove('ss-field-error');
      }
      if (shippingRowEl) {
        shippingRowEl.classList.remove('ss-field-error');
      }
    }
    hideValidationAlert();
  }

  deliveryMethodInputs.forEach(function (input) {
    input.addEventListener('change', function () {
      clearFieldErrors();
      hideValidationAlert();
      applyDeliveryMethod();
    });
  });

  provinceSelect.addEventListener('change', function () {
    clearErrorForField(provinceSelect);
    syncProvinceFields();
    loadCities(provinceSelect.value, '').then(scheduleQuote);
  });

  citySelect.addEventListener('change', function () {
    clearErrorForField(citySelect);
    scheduleQuote();
  });

  form.querySelectorAll('input, select, textarea').forEach(function (field) {
    field.addEventListener('input', function () {
      clearErrorForField(field);
    });
    field.addEventListener('change', function () {
      clearErrorForField(field);
    });
  });

  form.addEventListener('submit', function (event) {
    clearFieldErrors();
    hideValidationAlert();

    var result = validateCheckoutForm();
    if (!result.valid) {
      event.preventDefault();
      result.fields.forEach(function (field) {
        if (field === shippingRowEl || field === jtShippingBlock) {
          field.classList.add('ss-field-error');
        } else {
          markFieldError(field);
        }
      });
      showValidationAlert(result.message);
      if (result.firstField) {
        if (result.firstField.focus) {
          result.firstField.focus();
        }
        var scrollTarget = fieldContainer(result.firstField) || result.firstField;
        if (scrollTarget && scrollTarget.scrollIntoView) {
          scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      } else if (validationAlert && validationAlert.scrollIntoView) {
        validationAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      return;
    }

    if ((isSameDayDelivery() || isPickupDelivery()) && shippingCentsInput) {
      shippingCentsInput.value = '0';
    }
  });

  if (subtotalEl) {
    subtotalEl.textContent = formatPrice(subtotalCents);
  }
  setTotal(subtotalCents);
  setPlaceOrderDisabled(false);

  var initialProvinceId = provinceSelect.getAttribute('data-selected-id') || '';
  var initialCity = citySelect.getAttribute('data-selected-city') || '';

  loadProvinces()
    .then(function () {
      if (initialProvinceId) {
        provinceSelect.value = initialProvinceId;
        syncProvinceFields();
        return loadCities(initialProvinceId, initialCity);
      }
      return null;
    })
    .then(function () {
      applyDeliveryMethod();
      if (isJtDelivery() && initialProvinceId && initialCity) {
        requestQuote();
      }
    })
    .catch(function () {
      if (isJtDelivery()) {
        setShippingDisplay(0, 'Could not load provinces.');
      }
    });
})();
