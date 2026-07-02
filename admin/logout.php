<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
logout_user();
ss_redirect('login.php');
