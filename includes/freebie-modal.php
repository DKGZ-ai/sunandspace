<?php
/** @var string $freebieTrigger 'guest'|'login' */
// Bundled in repo: images/freebie-hero.png (deployed via git, not sunandspace_data)
$heroImg = ss_media_url('images/freebie-hero.png');
?>
<div
  class="ss-modal ss-freebie-modal"
  id="ssFreebieModal"
  hidden
  role="dialog"
  aria-modal="true"
  aria-labelledby="ssFreebieTitle"
  data-freebie-trigger="<?= ss_escape($freebieTrigger) ?>"
>
  <div class="ss-freebie-confetti" id="ssFreebieConfetti" aria-hidden="true"></div>
  <button type="button" class="ss-modal__backdrop" id="ssFreebieBackdrop" aria-label="Close free gift offer"></button>
  <div class="ss-modal__dialog ss-freebie-modal__dialog">
    <button type="button" class="ss-modal__close" id="ssFreebieClose" aria-label="Close">&times;</button>
    <div class="ss-freebie-modal__hero">
      <span class="ss-freebie-modal__badge">Bonus offer</span>
      <div class="ss-freebie-modal__img-wrap">
        <img
          class="ss-freebie-modal__img"
          src="<?= ss_escape($heroImg) ?>"
          alt="Power station with complimentary solar panel"
          width="440"
          height="248"
        >
        <span class="ss-freebie-modal__free-ribbon">FREE</span>
      </div>
      <p class="ss-freebie-modal__caption">Included with most products</p>
    </div>
    <div class="ss-modal__body ss-freebie-modal__body">
      <h2 class="ss-freebie-modal__title" id="ssFreebieTitle">Free solar panel with most purchases</h2>
      <p class="ss-freebie-modal__desc">
        Most of our products include a complimentary solar panel — our thank-you for choosing <?= ss_escape(ss_brand_name()) ?>.
      </p>
      <p class="ss-freebie-modal__note">
        <strong>Note:</strong> Some items are sold without the free solar bonus. Check the product name for details.
      </p>
      <div class="ss-modal__actions ss-freebie-modal__actions">
        <a href="#products" class="ss-btn-primary ss-freebie-modal__cta" id="ssFreebieShop">Shop now</a>
      </div>
    </div>
  </div>
</div>
