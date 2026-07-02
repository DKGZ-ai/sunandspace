<?php
/** @var string $wholesaleNoteContext */
$wholesaleNoteContext = $wholesaleNoteContext ?? 'pdp';
$isFooter = $wholesaleNoteContext === 'footer';
$wrapperTag = $isFooter ? 'p' : 'aside';
?>
<<?= $wrapperTag ?> class="ss-wholesale-note ss-wholesale-note--<?= ss_escape($wholesaleNoteContext) ?>"<?= $isFooter ? '' : ' aria-label="Wholesale and reseller pricing"' ?>>
  <span class="ss-wholesale-note__text">Wholesale and reseller prices are available.</span>
  <span class="ss-wholesale-note__actions">
    <a href="<?= ss_escape(ss_support_phone_tel()) ?>"><?= ss_escape(ss_support_phone()) ?></a>
    <span class="ss-wholesale-note__sep" aria-hidden="true">&middot;</span>
    <a href="<?= ss_escape(ss_facebook_url()) ?>" target="_blank" rel="noopener noreferrer">Message on Facebook</a>
  </span>
</<?= $wrapperTag ?>>
