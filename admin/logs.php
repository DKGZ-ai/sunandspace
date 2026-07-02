<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
require_once dirname(__DIR__) . '/includes/admin-layout.php';

admin_page_start('Logs', 'logs');
admin_page_header('Logs', 'Activity and audit trail.', 'System logs will be listed here when logging is enabled.', false);
?>
<section class="ss-admin-panel">
  <h2>No logs yet</h2>
  <p>Order and account events are not written to an audit log in this version.</p>
</section>
<?php admin_page_end(); ?>
