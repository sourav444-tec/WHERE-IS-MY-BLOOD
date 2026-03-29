<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

clear_admin_session();
flash_set('success', 'Logged out successfully.');
header('Location: /WHERE-IS-MY-BLOOD/index.php');
exit;
