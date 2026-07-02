<?php
require_once __DIR__ . '/includes/bootstrap.php';
logout_user();
ss_redirect('index.php');
// Debug: 
// var_dump($_SESSION);

