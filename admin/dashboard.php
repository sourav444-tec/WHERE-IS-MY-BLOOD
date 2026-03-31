<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$storage = read_storage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update_inventory') {
        $group = (string)($_POST['group'] ?? '');
        $units = (int)($_POST['units'] ?? 0);

        if (in_array($group, get_blood_groups(), true) && $units >= 0) {
            $storage['inventory'][$group] = $units;
            save_storage($storage);
            flash_set('success', 'Inventory updated for ' . $group . '.');
        } else {
            flash_set('error', 'Invalid blood group or units.');
        }

        header('Location: /WHERE-IS-MY-BLOOD/admin/dashboard.php');
        exit;
    }

    if ($action === 'add_donor') {
        $name = trim((string)($_POST['name'] ?? ''));
        $group = (string)($_POST['blood_group'] ?? '');
        $phone = preg_replace('/\D+/', '', trim((string)($_POST['phone'] ?? '')));

        if ($name !== '' && is_string($phone) && is_valid_phone($phone) && in_array($group, get_blood_groups(), true)) {
            $storage['donors'][] = [
                'name' => $name,
                'blood_group' => $group,
                'phone' => $phone,
                'created_at' => now(),
            ];
            save_storage($storage);
            flash_set('success', 'Donor added successfully.');
        } else {
            flash_set('error', 'Please provide valid donor details.');
        }

        header('Location: /WHERE-IS-MY-BLOOD/admin/dashboard.php');
        exit;
    }

    if ($action === 'update_request_status') {
        $requestId = trim((string)($_POST['request_id'] ?? ''));
        $newStatus = trim((string)($_POST['new_status'] ?? ''));
        $allowedStatuses = ['Approved', 'Rejected', 'Fulfilled'];

        if ($requestId === '' || !in_array($newStatus, $allowedStatuses, true)) {
            flash_set('error', 'Invalid request update payload.');
            header('Location: /WHERE-IS-MY-BLOOD/admin/dashboard.php');
            exit;
        }

        $index = find_request_index_by_id($storage['requests'], $requestId);
        if ($index < 0) {
            flash_set('error', 'Request not found.');
            header('Location: /WHERE-IS-MY-BLOOD/admin/dashboard.php');
            exit;
        }

        $request = $storage['requests'][$index];
        $group = (string)($request['blood_group'] ?? '');
        $units = (int)($request['units'] ?? 0);
        $requestType = (string)($request['request_type'] ?? 'Blood Collection');
        $oldStatus = (string)($request['status'] ?? 'Pending');

        if (!in_array($group, get_blood_groups(), true) || $units < 1) {
            flash_set('error', 'Request has invalid blood group or units.');
            header('Location: /WHERE-IS-MY-BLOOD/admin/dashboard.php');
            exit;
        }

        $inventory = (int)($storage['inventory'][$group] ?? 0);
        $wasReservedCollection = $requestType === 'Blood Collection' && ($oldStatus === 'Approved' || $oldStatus === 'Approved (Auto)');

        if ($newStatus === 'Rejected' && $wasReservedCollection) {
            $storage['inventory'][$group] = $inventory + $units;
        }

        if ($newStatus === 'Approved' && $requestType === 'Blood Collection' && !$wasReservedCollection) {
            if ($inventory < $units) {
                flash_set('error', 'Not enough inventory to approve request ' . $requestId . '.');
                header('Location: /WHERE-IS-MY-BLOOD/admin/dashboard.php');
                exit;
            }

            $storage['inventory'][$group] = $inventory - $units;
        }

        if ($newStatus === 'Fulfilled' && $requestType === 'Blood Donation' && $oldStatus !== 'Fulfilled') {
            $storage['inventory'][$group] = $inventory + $units;
        }

        $request['status'] = $newStatus;
        $request['updated_at'] = now();
        $storage['requests'][$index] = $request;
        save_storage($storage);

        flash_set('success', 'Request ' . $requestId . ' updated to ' . $newStatus . '.');
        header('Location: /WHERE-IS-MY-BLOOD/admin/dashboard.php');
        exit;
    }

    if ($action === 'update_order_status') {
        $orderId = trim((string)($_POST['order_id'] ?? ''));
        $newStatus = trim((string)($_POST['new_status'] ?? ''));
        $allowedStatuses = ['Processing', 'Dispatched', 'Delivered', 'Cancelled'];

        if ($orderId === '' || !in_array($newStatus, $allowedStatuses, true)) {
            flash_set('error', 'Invalid order update payload.');
            header('Location: /WHERE-IS-MY-BLOOD/admin/dashboard.php');
            exit;
        }

        $orderIndex = find_order_index_by_id($storage['orders'], $orderId);
        if ($orderIndex < 0) {
            flash_set('error', 'Order not found.');
            header('Location: /WHERE-IS-MY-BLOOD/admin/dashboard.php');
            exit;
        }

        $order = $storage['orders'][$orderIndex];
        $oldStatus = (string)($order['status'] ?? 'Order Placed');
        $group = (string)($order['blood_group'] ?? '');
        $units = (int)($order['units'] ?? 0);

        if ($newStatus === 'Cancelled' && $oldStatus !== 'Cancelled' && in_array($group, get_blood_groups(), true) && $units > 0) {
            $storage['inventory'][$group] = (int)($storage['inventory'][$group] ?? 0) + $units;
        }

        $order['status'] = $newStatus;
        $order['updated_at'] = now();
        $storage['orders'][$orderIndex] = $order;
        save_storage($storage);

        flash_set('success', 'Order ' . $orderId . ' updated to ' . $newStatus . '.');
        header('Location: /WHERE-IS-MY-BLOOD/admin/dashboard.php');
        exit;
    }
}

$inventory = is_array($storage['inventory'] ?? null) ? $storage['inventory'] : [];
$requests = is_array($storage['requests'] ?? null) ? $storage['requests'] : [];
$donors = is_array($storage['donors'] ?? null) ? $storage['donors'] : [];
$orders = is_array($storage['orders'] ?? null) ? $storage['orders'] : [];

$totalUnits = array_sum(array_map('intval', $inventory));
$lowStockGroups = 0;
foreach ($inventory as $units) {
    if ((int)$units <= 3) {
        $lowStockGroups++;
    }
}

$pendingCount = 0;
$approvedCount = 0;
$fulfilledCount = 0;
foreach ($requests as $request) {
    $status = strtolower((string)($request['status'] ?? ''));
    if ($status === 'pending' || $status === 'emergency pending' || $status === 'donation pending verification') {
        $pendingCount++;
    }
    if ($status === 'approved' || $status === 'approved (auto)') {
        $approvedCount++;
    }
    if ($status === 'fulfilled') {
        $fulfilledCount++;
    }
}

$title = 'Admin Dashboard';
$active = 'admin';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="grid four-cards">
    <div class="panel stat-card">
        <h2>Total Requests</h2>
        <p class="stat-value"><?php echo count($requests); ?></p>
    </div>
    <div class="panel stat-card">
        <h2>Pending Requests</h2>
        <p class="stat-value"><?php echo $pendingCount; ?></p>
    </div>
    <div class="panel stat-card">
        <h2>Total Units</h2>
        <p class="stat-value"><?php echo $totalUnits; ?></p>
    </div>
    <div class="panel stat-card">
        <h2>Low Stock Groups</h2>
        <p class="stat-value"><?php echo $lowStockGroups; ?></p>
    </div>
</section>

<section class="grid two-columns">
    <div class="panel">
        <h2>Update Blood Inventory</h2>
        <form method="POST" class="form">
            <input type="hidden" name="action" value="update_inventory">
            <label for="group">Blood Group</label>
            <select id="group" name="group" required>
                <?php foreach (get_blood_groups() as $group): ?>
                    <option value="<?php echo esc($group); ?>"><?php echo esc($group); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="units">Units</label>
            <input type="number" id="units" name="units" min="0" required>

            <button type="submit">Save Inventory</button>
        </form>
    </div>

    <div class="panel">
        <h2>Add Donor</h2>
        <form method="POST" class="form">
            <input type="hidden" name="action" value="add_donor">
            <label for="name">Donor Name</label>
            <input type="text" id="name" name="name" required>

            <label for="blood_group">Blood Group</label>
            <select id="blood_group" name="blood_group" required>
                <?php foreach (get_blood_groups() as $group): ?>
                    <option value="<?php echo esc($group); ?>"><?php echo esc($group); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" required>

            <button type="submit">Add Donor</button>
        </form>
    </div>
</section>

<section class="panel">
    <h2>Current Inventory</h2>
    <div class="chip-list">
        <?php foreach ($inventory as $group => $units): ?>
            <span class="chip"><?php echo esc($group); ?>: <?php echo (int)$units; ?> units</span>
        <?php endforeach; ?>
    </div>
</section>

<section class="grid two-columns">
    <div class="panel">
        <h2>Registered Donors (<?php echo count($donors); ?>)</h2>
        <?php if (empty($donors)): ?>
            <p>No donors added yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Blood Group</th>
                        <th>Phone</th>
                        <th>Added</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (array_reverse($donors) as $donor): ?>
                        <tr>
                            <td><?php echo esc($donor['name'] ?? ''); ?></td>
                            <td><?php echo esc($donor['blood_group'] ?? ''); ?></td>
                            <td><?php echo esc($donor['phone'] ?? ''); ?></td>
                            <td><?php echo esc($donor['created_at'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h2>Request Summary</h2>
        <div class="chip-list">
            <span class="chip">Approved: <?php echo $approvedCount; ?></span>
            <span class="chip">Fulfilled: <?php echo $fulfilledCount; ?></span>
            <span class="chip">Pending: <?php echo $pendingCount; ?></span>
        </div>
        <p class="hint">Use the table below to approve, reject, or mark requests fulfilled.</p>
    </div>
</section>

<section class="panel">
    <h2>Blood Requests</h2>
    <?php if (empty($requests)): ?>
            <p>No requests submitted yet.</p>
    <?php else: ?>
        <div class="table-tools">
            <input class="table-search" type="text" placeholder="Search request table..." data-table-search="requests-table" data-search-counter="requests-visible-count">
            <span class="table-counter">Visible rows: <strong id="requests-visible-count"><?php echo count($requests); ?></strong></span>
        </div>
        <div class="table-wrap">
            <table id="requests-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Blood Group</th>
                    <th>Units</th>
                    <th>Phone</th>
                    <th>Location</th>
                    <th>Pincode</th>
                    <th>Purpose</th>
                    <th>Request Type</th>
                    <th>Collection Mode</th>
                    <th>Status</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (array_reverse($requests) as $request): ?>
                    <tr>
                        <td><?php echo esc($request['request_id'] ?? '-'); ?></td>
                        <td><?php echo esc($request['name'] ?? ''); ?></td>
                        <td><?php echo esc($request['blood_group'] ?? ''); ?></td>
                        <td><?php echo (int)($request['units'] ?? 0); ?></td>
                        <td><?php echo esc($request['phone'] ?? ''); ?></td>
                        <td><?php echo esc($request['location'] ?? ''); ?></td>
                        <td><?php echo esc($request['pincode'] ?? ''); ?></td>
                        <td><?php echo esc($request['purpose'] ?? ''); ?></td>
                        <td><?php echo esc($request['request_type'] ?? 'Blood Collection'); ?></td>
                        <td><?php echo esc($request['collection_mode'] ?? '-'); ?></td>
                        <td>
                            <span class="status-badge <?php echo status_badge_class((string)($request['status'] ?? '')); ?>">
                                <?php echo esc($request['status'] ?? 'Pending'); ?>
                            </span>
                        </td>
                        <td><?php echo esc($request['created_at'] ?? ''); ?></td>
                        <td>
                            <form method="POST" class="form inline-form compact-form">
                                <input type="hidden" name="action" value="update_request_status">
                                <input type="hidden" name="request_id" value="<?php echo esc((string)($request['request_id'] ?? '')); ?>">
                                <select name="new_status" required>
                                    <option value="Approved">Approve</option>
                                    <option value="Rejected">Reject</option>
                                    <option value="Fulfilled">Fulfilled</option>
                                </select>
                                <button type="submit">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Blood Orders (<?php echo count($orders); ?>)</h2>
    <?php if (empty($orders)): ?>
        <p>No blood orders found yet.</p>
    <?php else: ?>
        <div class="table-tools">
            <input class="table-search" type="text" placeholder="Search order table..." data-table-search="orders-table" data-search-counter="orders-visible-count">
            <span class="table-counter">Visible rows: <strong id="orders-visible-count"><?php echo count($orders); ?></strong></span>
        </div>
        <div class="table-wrap">
            <table id="orders-table">
                <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Blood Group</th>
                    <th>Units</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (array_reverse($orders) as $order): ?>
                    <tr>
                        <td><?php echo esc((string)($order['order_id'] ?? '-')); ?></td>
                        <td><?php echo esc((string)($order['name'] ?? '-')); ?></td>
                        <td><?php echo esc((string)($order['phone'] ?? '-')); ?></td>
                        <td><?php echo esc((string)($order['blood_group'] ?? '-')); ?></td>
                        <td><?php echo (int)($order['units'] ?? 0); ?></td>
                        <td>Rs <?php echo (int)($order['total_amount'] ?? 0); ?></td>
                        <td><?php echo esc((string)($order['payment_mode'] ?? '-')); ?></td>
                        <td>
                            <span class="status-badge <?php echo status_badge_class((string)($order['status'] ?? 'Order Placed')); ?>">
                                <?php echo esc((string)($order['status'] ?? 'Order Placed')); ?>
                            </span>
                        </td>
                        <td><?php echo esc((string)($order['created_at'] ?? '')); ?></td>
                        <td>
                            <form method="POST" class="form inline-form compact-form">
                                <input type="hidden" name="action" value="update_order_status">
                                <input type="hidden" name="order_id" value="<?php echo esc((string)($order['order_id'] ?? '')); ?>">
                                <select name="new_status" required>
                                    <option value="Processing">Processing</option>
                                    <option value="Dispatched">Dispatched</option>
                                    <option value="Delivered">Delivered</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                                <button type="submit">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
