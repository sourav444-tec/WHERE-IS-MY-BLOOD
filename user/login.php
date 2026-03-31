<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if (is_user_logged_in()) {
    header('Location: /WHERE-IS-MY-BLOOD/user/user.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $storage = read_storage();
    $users = is_array($storage['users'] ?? null) ? $storage['users'] : [];

    foreach ($users as $user) {
        $savedUsername = (string)($user['username'] ?? '');
        $savedPassword = (string)($user['password'] ?? '');

        if ($username === $savedUsername && $password === $savedPassword) {
            set_user_session($user);
            flash_set('success', 'Welcome ' . (string)($user['name'] ?? 'User') . '. You are logged in.');
            header('Location: /WHERE-IS-MY-BLOOD/user/user.php');
            exit;
        }
    }

    flash_set('error', 'Invalid user login. Try user / user123.');
    header('Location: /WHERE-IS-MY-BLOOD/user/login.php');
    exit;
}

$title = 'User Login';
$active = 'user';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <h1>User Login</h1>
    <p>Login to continue and submit blood requests with a personalized welcome.</p>

    <form method="POST" class="form">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <p class="hint">Demo user credentials: user / user123</p>
    <p class="hint">New here? <a href="/WHERE-IS-MY-BLOOD/user/register.php">Create a user account</a>.</p>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
