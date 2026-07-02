/* Sun and Space — homepage freebie promo modal */
(function () {
  var modal = document.getElementById('ssFreebieModal');
  if (!modal) return;

  var closeBtn = document.getElementById('ssFreebieClose');
  var backdrop = document.getElementById('ssFreebieBackdrop');
  var confettiRoot = document.getElementById('ssFreebieConfetti');
  var shopBtn = document.getElementById('ssFreebieShop');
  var trigger = modal.getAttribute('data-freebie-trigger') || 'guest';
  var storageKey = 'ss_freebie_seen';
  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function shouldShow() {
    if (trigger === 'login') {
      return true;
    }
    try {
      return !sessionStorage.getItem(storageKey);
    } catch (e) {
      return true;
    }
  }

  function markSeen() {
    if (trigger !== 'guest') {
      return;
    }
    try {
      sessionStorage.setItem(storageKey, '1');
    } catch (e) {
      /* ignore */
    }
  }

  function buildConfetti() {
    if (!confettiRoot || reducedMotion) return;

    var colors = ['#ee7e3a', '#f6b54a', '#e44a2b', '#fcf5e9', '#fbe7d2'];
    var count = 24;
    confettiRoot.textContent = '';

    for (var i = 0; i < count; i++) {
      var angle = ((360 / count) * i + (Math.random() * 20 - 10)) * (Math.PI / 180);
      var distance = 80 + Math.random() * 90;
      var piece = document.createElement('span');
      piece.className = 'ss-freebie-confetti__piece';
      piece.style.setProperty('--ss-confetti-x', String(Math.cos(angle) * distance) + 'px');
      piece.style.setProperty('--ss-confetti-y', String(Math.sin(angle) * distance) + 'px');
      piece.style.setProperty('--ss-confetti-delay', String(Math.random() * 0.15) + 's');
      piece.style.setProperty('--ss-confetti-color', colors[i % colors.length]);
      piece.style.setProperty('--ss-confetti-size', String(6 + Math.random() * 6) + 'px');
      piece.style.setProperty('--ss-confetti-rotate', String(Math.random() * 360) + 'deg');
      confettiRoot.appendChild(piece);
    }
  }

  function openModal() {
    modal.removeAttribute('hidden');
    document.body.classList.add('ss-modal-open');
    buildConfetti();
    markSeen();
    if (closeBtn) {
      closeBtn.focus();
    }
  }

  function closeModal() {
    modal.setAttribute('hidden', '');
    document.body.classList.remove('ss-modal-open');
    if (confettiRoot) {
      confettiRoot.textContent = '';
    }
  }

  if (closeBtn) {
    closeBtn.addEventListener('click', closeModal);
  }
  if (backdrop) {
    backdrop.addEventListener('click', closeModal);
  }
  if (shopBtn) {
    shopBtn.addEventListener('click', closeModal);
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hasAttribute('hidden')) {
      closeModal();
    }
  });

  if (shouldShow()) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', openModal);
    } else {
      openModal();
    }
  }
})();
