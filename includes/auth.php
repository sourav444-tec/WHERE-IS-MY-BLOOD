<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

if (!is_admin_logged_in()) {
    flash_set('error', 'Please login as admin first.');
    header('Location: /WHERE-IS-MY-BLOOD/admin/login.php');
    exit;
}
