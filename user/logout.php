<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

clear_user_session();
session_regenerate_id(true);
flash_set('success', 'User logged out successfully.');
header('Location: /WHERE-IS-MY-BLOOD/index.php');
exit;
