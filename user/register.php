<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if (is_user_logged_in()) {
    header('Location: /WHERE-IS-MY-BLOOD/user/user.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $username = strtolower(trim((string)($_POST['username'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $phone = preg_replace('/\D+/', '', trim((string)($_POST['phone'] ?? '')));

    if ($name === '' || $username === '' || strlen($password) < 4 || !is_string($phone) || !is_valid_phone($phone)) {
        flash_set('error', 'Provide valid details. Password must be at least 4 characters.');
        header('Location: /WHERE-IS-MY-BLOOD/user/register.php');
        exit;
    }

    $storage = read_storage();
    $users = is_array($storage['users'] ?? null) ? $storage['users'] : [];

    foreach ($users as $user) {
        if ((string)($user['username'] ?? '') === $username) {
            flash_set('error', 'Username already exists. Please choose another username.');
            header('Location: /WHERE-IS-MY-BLOOD/user/register.php');
            exit;
        }
    }

    $newUser = [
        'username' => $username,
        'password' => $password,
        'name' => $name,
        'phone' => $phone,
    ];

    $storage['users'][] = $newUser;
    save_storage($storage);

    set_user_session($newUser);
    flash_set('success', 'Account created successfully. Welcome ' . $name . '.');
    header('Location: /WHERE-IS-MY-BLOOD/user/user.php');
    exit;
}

$title = 'User Register';
$active = 'user-register';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <h1>Create User Account</h1>
    <p>Create a quick account to track your own blood requests and statuses.</p>

    <form method="POST" class="form">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" required>

        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" minlength="4" required>

        <label for="phone">Phone Number</label>
        <input type="text" id="phone" name="phone" required>

        <button type="submit">Create Account</button>
    </form>

    <p class="hint">Already have an account? <a href="/WHERE-IS-MY-BLOOD/user/login.php">Login here</a>.</p>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
