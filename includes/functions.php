<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const STORAGE_FILE = __DIR__ . '/../data/storage.json';

function get_blood_groups(): array
{
    return ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
}

function default_storage(): array
{
    $inventory = [];

    foreach (get_blood_groups() as $group) {
        $inventory[$group] = 0;
    }

    return [
        'inventory' => $inventory,
        'donors' => [],
        'requests' => [],
        'orders' => [],
        'users' => [
            [
                'username' => 'user',
                'password' => 'user123',
                'name' => 'Website User',
                'phone' => '9000012345',
            ],
        ],
    ];
}

function read_storage(): array
{
    if (!file_exists(STORAGE_FILE)) {
        save_storage(default_storage());
    }

    $raw = file_get_contents(STORAGE_FILE);
    if ($raw === false || trim($raw) === '') {
        return default_storage();
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return default_storage();
    }

    $storage = default_storage();

    if (isset($decoded['inventory']) && is_array($decoded['inventory'])) {
        foreach (get_blood_groups() as $group) {
            $value = $decoded['inventory'][$group] ?? 0;
            $storage['inventory'][$group] = max(0, (int)$value);
        }
    }

    if (isset($decoded['donors']) && is_array($decoded['donors'])) {
        $storage['donors'] = $decoded['donors'];
    }

    if (isset($decoded['requests']) && is_array($decoded['requests'])) {
        $storage['requests'] = $decoded['requests'];
    }

    if (isset($decoded['orders']) && is_array($decoded['orders'])) {
        $storage['orders'] = $decoded['orders'];
    }

    if (isset($decoded['users']) && is_array($decoded['users'])) {
        $storage['users'] = $decoded['users'];
    }

    return $storage;
}

function save_storage(array $storage): bool
{
    $json = json_encode($storage, JSON_PRETTY_PRINT);
    if ($json === false) {
        return false;
    }

    return file_put_contents(STORAGE_FILE, $json, LOCK_EX) !== false;
}

function esc(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function flash_get(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return is_array($flash) ? $flash : null;
}

function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_logged_in']);
}

function set_admin_session(): void
{
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
}

function clear_admin_session(): void
{
    unset($_SESSION['admin_logged_in']);
}

function is_user_logged_in(): bool
{
    return !empty($_SESSION['user_logged_in']);
}

function get_current_user_name(): string
{
    return (string)($_SESSION['user_name'] ?? 'User');
}

function get_current_user_phone(): string
{
    return (string)($_SESSION['user_phone'] ?? '');
}

function set_user_session(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_name'] = (string)($user['name'] ?? 'User');
    $_SESSION['user_username'] = (string)($user['username'] ?? '');
    $_SESSION['user_phone'] = (string)($user['phone'] ?? '');
}

function clear_user_session(): void
{
    unset($_SESSION['user_logged_in'], $_SESSION['user_name'], $_SESSION['user_username'], $_SESSION['user_phone'], $_SESSION['pending_request']);
}
