<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if (!is_user_logged_in()) {
    flash_set('error', 'Please login first to view request history.');
    header('Location: /WHERE-IS-MY-BLOOD/user/login.php');
    exit;
}

$statusFilter = trim((string)($_GET['status'] ?? 'all'));
$groupFilter = trim((string)($_GET['group'] ?? 'all'));

$storage = read_storage();
$requests = is_array($storage['requests'] ?? null) ? $storage['requests'] : [];
$currentUser = get_current_user_username();
$currentPhone = get_current_user_phone();

$filteredRequests = [];
foreach (array_reverse($requests) as $request) {
    $owner = (string)($request['user_username'] ?? '');
    $phone = (string)($request['phone'] ?? '');

    if ($owner !== $currentUser && !($owner === '' && $phone === $currentPhone)) {
        continue;
    }

    $status = strtolower((string)($request['status'] ?? ''));
    $group = (string)($request['blood_group'] ?? '');

    if ($statusFilter !== 'all' && $status !== strtolower($statusFilter)) {
        continue;
    }

    if ($groupFilter !== 'all' && $group !== $groupFilter) {
        continue;
    }

    $filteredRequests[] = $request;
}

$title = 'My Request History';
$active = 'user-portal';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <h1>My Request History</h1>
    <p>Track all your blood requests and their latest status in one place.</p>
</section>

<section class="panel">
    <h2>Filters</h2>
    <form method="GET" class="grid two-columns compact-grid">
        <div class="form">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="emergency pending" <?php echo $statusFilter === 'emergency pending' ? 'selected' : ''; ?>>Emergency Pending</option>
                <option value="donation pending verification" <?php echo $statusFilter === 'donation pending verification' ? 'selected' : ''; ?>>Donation Pending Verification</option>
                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="approved (auto)" <?php echo $statusFilter === 'approved (auto)' ? 'selected' : ''; ?>>Approved (Auto)</option>
                <option value="fulfilled" <?php echo $statusFilter === 'fulfilled' ? 'selected' : ''; ?>>Fulfilled</option>
                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>

        <div class="form">
            <label for="group">Blood Group</label>
            <select id="group" name="group">
                <option value="all" <?php echo $groupFilter === 'all' ? 'selected' : ''; ?>>All</option>
                <?php foreach (get_blood_groups() as $group): ?>
                    <option value="<?php echo esc($group); ?>" <?php echo $groupFilter === $group ? 'selected' : ''; ?>><?php echo esc($group); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit">Apply Filters</button>
    </form>
</section>

<section class="panel">
    <h2>Requests (<?php echo count($filteredRequests); ?>)</h2>
    <?php if (empty($filteredRequests)): ?>
        <p>No request records match your selected filters.</p>
    <?php else: ?>
        <div class="table-tools">
            <input class="table-search" type="text" placeholder="Search visible request rows..." data-table-search="history-requests-table" data-search-counter="history-visible-count">
            <span class="table-counter">Visible rows: <strong id="history-visible-count"><?php echo count($filteredRequests); ?></strong></span>
        </div>
        <div class="table-wrap">
            <table id="history-requests-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Blood Group</th>
                    <th>Units</th>
                    <th>Type</th>
                    <th>Collection Mode</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Updated</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($filteredRequests as $request): ?>
                    <?php $status = (string)($request['status'] ?? 'Pending'); ?>
                    <tr>
                        <td><?php echo esc((string)($request['request_id'] ?? '-')); ?></td>
                        <td><?php echo esc((string)($request['name'] ?? '-')); ?></td>
                        <td><?php echo esc((string)($request['blood_group'] ?? '-')); ?></td>
                        <td><?php echo (int)($request['units'] ?? 0); ?></td>
                        <td><?php echo esc((string)($request['request_type'] ?? '-')); ?></td>
                        <td><?php echo esc((string)($request['collection_mode'] ?? '-')); ?></td>
                        <td>
                            <span class="status-badge <?php echo status_badge_class($status); ?>">
                                <?php echo esc($status); ?>
                            </span>
                        </td>
                        <td><?php echo esc((string)($request['created_at'] ?? '')); ?></td>
                        <td><?php echo esc((string)($request['updated_at'] ?? '-')); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
