<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

$title = $title ?? 'Blood Bank Management System';
$active = $active ?? '';
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($title); ?></title>
    <link rel="stylesheet" href="/WHERE-IS-MY-BLOOD/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="/WHERE-IS-MY-BLOOD/index.php">Where Is My Blood</a>
        <nav>
            <a href="/WHERE-IS-MY-BLOOD/index.php" class="<?php echo $active === 'home' ? 'active' : ''; ?>">Home</a>
            <a href="/WHERE-IS-MY-BLOOD/admin/login.php" class="<?php echo $active === 'admin' ? 'active' : ''; ?>">Admin</a>
            <a href="/WHERE-IS-MY-BLOOD/user/login.php" class="<?php echo $active === 'user' ? 'active' : ''; ?>">User Login</a>
            <?php if (is_user_logged_in()): ?>
                <a href="/WHERE-IS-MY-BLOOD/user/user.php" class="<?php echo $active === 'user-portal' ? 'active' : ''; ?>">User Portal</a>
                <a href="/WHERE-IS-MY-BLOOD/user/logout.php">User Logout</a>
            <?php endif; ?>
            <a href="/WHERE-IS-MY-BLOOD/contact.php" class="<?php echo $active === 'contact' ? 'active' : ''; ?>">Contact</a>
            <a href="/WHERE-IS-MY-BLOOD/location.php" class="<?php echo $active === 'location' ? 'active' : ''; ?>">Location</a>
            <?php if (is_admin_logged_in()): ?>
                <a href="/WHERE-IS-MY-BLOOD/admin/logout.php">Logout</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container main-content">
    <?php if ($flash): ?>
        <div class="flash <?php echo esc($flash['type']); ?>"><?php echo esc($flash['message']); ?></div>
    <?php endif; ?>
