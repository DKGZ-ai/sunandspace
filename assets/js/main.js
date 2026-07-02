/* Sun and Space — vanilla JS for mobile menu + carousel arrows */
(function () {
  // Storefront mobile menu toggle
  var burger = document.getElementById('ssBurger');
  var menu = document.getElementById('ssMobileMenu');
  if (burger && menu) {
    burger.addEventListener('click', function () {
      menu.classList.toggle('hidden');
    });
  }

  // Admin mobile nav toggle
  var adminBurger = document.getElementById('ssAdminBurger');
  var adminWrap = document.getElementById('ssAdminBarWrap');
  var adminNav = document.getElementById('ssAdminMobileMenu');
  if (adminBurger && adminWrap && adminNav) {
    function setAdminNavOpen(open) {
      adminWrap.classList.toggle('is-nav-open', open);
      adminBurger.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    adminBurger.addEventListener('click', function () {
      setAdminNavOpen(!adminWrap.classList.contains('is-nav-open'));
    });
    adminNav.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        setAdminNavOpen(false);
      });
    });
  }

  // Product carousel arrows (horizontal scroll nudge)
  var track = document.getElementById('ssCarousel');
  var prev = document.getElementById('ssPrev');
  var next = document.getElementById('ssNext');
  function nudge(dir) {
    if (!track) return;
    track.scrollBy({ left: dir * 320, behavior: 'smooth' });
  }
  if (prev) prev.addEventListener('click', function () { nudge(-1); });
  if (next) next.addEventListener('click', function () { nudge(1); });

  var paymentMethod = document.getElementById('ssPaymentMethod');
  var bankPanel = document.getElementById('ss-bank-panel');
  var paymentReceipt = document.getElementById('ssPaymentReceipt');
  function syncBankPanel() {
    if (!paymentMethod || !bankPanel) return;
    if (paymentMethod.disabled) {
      bankPanel.classList.add('hidden');
      if (paymentReceipt) {
        paymentReceipt.required = false;
      }
      return;
    }
    var isBank = paymentMethod.value === 'bank';
    bankPanel.classList.toggle('hidden', !isBank);
    if (paymentReceipt) {
      paymentReceipt.required = isBank;
    }
  }
  if (paymentMethod && bankPanel) {
    paymentMethod.addEventListener('change', syncBankPanel);
    syncBankPanel();
  }

  var scrollTopBtn = document.getElementById('ssScrollTop');
  if (scrollTopBtn) {
    function toggleScrollTop() {
      if (window.scrollY > 400) {
        scrollTopBtn.removeAttribute('hidden');
      } else {
        scrollTopBtn.setAttribute('hidden', '');
      }
    }
    window.addEventListener('scroll', toggleScrollTop, { passive: true });
    toggleScrollTop();
    scrollTopBtn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }
})();
