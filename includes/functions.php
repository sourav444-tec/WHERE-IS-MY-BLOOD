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
        $storage['requests'] = [];

        foreach ($decoded['requests'] as $index => $request) {
            if (!is_array($request)) {
                continue;
            }

            if (!isset($request['request_id']) || trim((string)$request['request_id']) === '') {
                $request['request_id'] = generate_request_id((int)$index);
            }

            if (!isset($request['created_at']) || trim((string)$request['created_at']) === '') {
                $request['created_at'] = now();
            }

            if (!isset($request['status']) || trim((string)$request['status']) === '') {
                $request['status'] = 'Pending';
            }

            $storage['requests'][] = $request;
        }
    }

    if (isset($decoded['orders']) && is_array($decoded['orders'])) {
        $storage['orders'] = [];

        foreach ($decoded['orders'] as $index => $order) {
            if (!is_array($order)) {
                continue;
            }

            if (!isset($order['order_id']) || trim((string)$order['order_id']) === '') {
                $order['order_id'] = generate_order_id((int)$index);
            }

            if (!isset($order['created_at']) || trim((string)$order['created_at']) === '') {
                $order['created_at'] = now();
            }

            if (!isset($order['status']) || trim((string)$order['status']) === '') {
                $order['status'] = 'Order Placed';
            }

            $storage['orders'][] = $order;
        }
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

function now(): string
{
    return date('Y-m-d H:i:s');
}

function generate_request_id(int $seed = 0): string
{
    return 'REQ-' . date('YmdHis') . '-' . strtoupper(substr(md5((string)($seed . microtime(true) . random_int(1000, 9999))), 0, 5));
}

function generate_order_id(int $seed = 0): string
{
    return 'ORD-' . date('YmdHis') . '-' . strtoupper(substr(md5((string)($seed . microtime(true) . random_int(1000, 9999))), 0, 5));
}

function get_current_user_username(): string
{
    return (string)($_SESSION['user_username'] ?? '');
}

function is_valid_phone(string $phone): bool
{
    return preg_match('/^[0-9]{10,15}$/', $phone) === 1;
}

function is_valid_pincode(string $pincode): bool
{
    return preg_match('/^[0-9]{6}$/', $pincode) === 1;
}

function find_request_index_by_id(array $requests, string $requestId): int
{
    foreach ($requests as $index => $request) {
        if ((string)($request['request_id'] ?? '') === $requestId) {
            return (int)$index;
        }
    }

    return -1;
}

function status_badge_class(string $status): string
{
    $normalized = strtolower(trim($status));

    if ($normalized === 'approved' || $normalized === 'approved (auto)' || $normalized === 'fulfilled') {
        return 'success';
    }

    if ($normalized === 'rejected' || $normalized === 'cancelled') {
        return 'error';
    }

    if ($normalized === 'emergency pending' || $normalized === 'pending' || $normalized === 'donation pending verification' || $normalized === 'order placed' || $normalized === 'processing' || $normalized === 'dispatched') {
        return 'warn';
    }

    return 'neutral';
}

function create_request_record(array $input): array
{
    return [
        'request_id' => (string)($input['request_id'] ?? generate_request_id()),
        'name' => trim((string)($input['name'] ?? '')),
        'blood_group' => (string)($input['blood_group'] ?? ''),
        'units' => max(1, (int)($input['units'] ?? 1)),
        'phone' => trim((string)($input['phone'] ?? '')),
        'location' => trim((string)($input['location'] ?? '')),
        'pincode' => trim((string)($input['pincode'] ?? '')),
        'purpose' => trim((string)($input['purpose'] ?? '')),
        'request_type' => (string)($input['request_type'] ?? 'Blood Collection'),
        'collection_mode' => (string)($input['collection_mode'] ?? ''),
        'advanced_hospital' => trim((string)($input['advanced_hospital'] ?? '')),
        'advanced_time' => trim((string)($input['advanced_time'] ?? '')),
        'advanced_notes' => trim((string)($input['advanced_notes'] ?? '')),
        'status' => (string)($input['status'] ?? 'Pending'),
        'created_at' => (string)($input['created_at'] ?? now()),
        'user_username' => (string)($input['user_username'] ?? get_current_user_username()),
    ];
}

function find_order_index_by_id(array $orders, string $orderId): int
{
    foreach ($orders as $index => $order) {
        if ((string)($order['order_id'] ?? '') === $orderId) {
            return (int)$index;
        }
    }

    return -1;
}

function create_order_record(array $input): array
{
    return [
        'order_id' => (string)($input['order_id'] ?? generate_order_id()),
        'name' => trim((string)($input['name'] ?? get_current_user_name())),
        'phone' => trim((string)($input['phone'] ?? get_current_user_phone())),
        'blood_group' => (string)($input['blood_group'] ?? ''),
        'units' => max(1, (int)($input['units'] ?? 1)),
        'delivery_address' => trim((string)($input['delivery_address'] ?? '')),
        'payment_mode' => trim((string)($input['payment_mode'] ?? 'Cash On Delivery')),
        'total_amount' => max(0, (int)($input['total_amount'] ?? 0)),
        'status' => (string)($input['status'] ?? 'Order Placed'),
        'created_at' => (string)($input['created_at'] ?? now()),
        'updated_at' => (string)($input['updated_at'] ?? ''),
        'user_username' => (string)($input['user_username'] ?? get_current_user_username()),
    ];
}
