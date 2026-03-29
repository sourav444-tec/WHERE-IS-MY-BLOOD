<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if (is_admin_logged_in()) {
    header('Location: /WHERE-IS-MY-BLOOD/admin/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === 'admin' && $password === 'admin123') {
        set_admin_session();
        flash_set('success', 'Welcome Admin.');
        header('Location: /WHERE-IS-MY-BLOOD/admin/dashboard.php');
        exit;
    }

    flash_set('error', 'Invalid credentials. Try admin / admin123.');
    header('Location: /WHERE-IS-MY-BLOOD/admin/login.php');
    exit;
}

$title = 'Admin Login';
$active = 'admin';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <h1>Admin Login</h1>
    <form method="POST" class="form">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Login</button>
    </form>
    <p class="hint">Default admin credentials: <strong>admin</strong> / <strong>admin123</strong></p>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
