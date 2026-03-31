<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if (!is_user_logged_in()) {
    flash_set('error', 'Please login first to buy blood.');
    header('Location: /WHERE-IS-MY-BLOOD/user/login.php');
    exit;
}

$pricePerUnit = 1500;
$storage = read_storage();
$currentUsername = get_current_user_username();
$currentPhone = get_current_user_phone();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? 'place_order');

    if ($action === 'place_order') {
        $group = (string)($_POST['blood_group'] ?? '');
        $units = (int)($_POST['units'] ?? 0);
        $address = trim((string)($_POST['delivery_address'] ?? ''));
        $paymentMode = trim((string)($_POST['payment_mode'] ?? 'Cash On Delivery'));

        if (!in_array($group, get_blood_groups(), true) || $units < 1 || $address === '') {
            flash_set('error', 'Please provide valid order details.');
            header('Location: /WHERE-IS-MY-BLOOD/user/buy_blood.php');
            exit;
        }

        if ($paymentMode !== 'Cash On Delivery' && $paymentMode !== 'UPI') {
            flash_set('error', 'Please select a valid payment mode.');
            header('Location: /WHERE-IS-MY-BLOOD/user/buy_blood.php');
            exit;
        }

        $available = (int)($storage['inventory'][$group] ?? 0);
        if ($available < $units) {
            flash_set('error', 'Insufficient stock for ' . $group . '. Available units: ' . $available . '.');
            header('Location: /WHERE-IS-MY-BLOOD/user/buy_blood.php');
            exit;
        }

        $storage['inventory'][$group] = $available - $units;
        $storage['orders'][] = create_order_record([
            'name' => get_current_user_name(),
            'phone' => get_current_user_phone(),
            'blood_group' => $group,
            'units' => $units,
            'delivery_address' => $address,
            'payment_mode' => $paymentMode,
            'total_amount' => $units * $pricePerUnit,
            'status' => 'Order Placed',
            'created_at' => now(),
            'user_username' => $currentUsername,
        ]);

        save_storage($storage);
        flash_set('success', 'Order placed successfully. Please wait for admin confirmation.');
        header('Location: /WHERE-IS-MY-BLOOD/user/buy_blood.php');
        exit;
    }

    if ($action === 'cancel_order') {
        $orderId = trim((string)($_POST['order_id'] ?? ''));
        if ($orderId === '') {
            flash_set('error', 'Invalid order id.');
            header('Location: /WHERE-IS-MY-BLOOD/user/buy_blood.php');
            exit;
        }

        $index = find_order_index_by_id($storage['orders'], $orderId);
        if ($index < 0) {
            flash_set('error', 'Order not found.');
            header('Location: /WHERE-IS-MY-BLOOD/user/buy_blood.php');
            exit;
        }

        $order = $storage['orders'][$index];
        $owner = (string)($order['user_username'] ?? '');
        if ($owner !== '' && $owner !== $currentUsername) {
            flash_set('error', 'You can only cancel your own order.');
            header('Location: /WHERE-IS-MY-BLOOD/user/buy_blood.php');
            exit;
        }

        $status = (string)($order['status'] ?? 'Order Placed');
        if ($status !== 'Order Placed' && $status !== 'Processing') {
            flash_set('error', 'Only placed or processing orders can be cancelled.');
            header('Location: /WHERE-IS-MY-BLOOD/user/buy_blood.php');
            exit;
        }

        $group = (string)($order['blood_group'] ?? '');
        $units = (int)($order['units'] ?? 0);
        if (in_array($group, get_blood_groups(), true) && $units > 0) {
            $storage['inventory'][$group] = (int)($storage['inventory'][$group] ?? 0) + $units;
        }

        $order['status'] = 'Cancelled';
        $order['updated_at'] = now();
        $storage['orders'][$index] = $order;
        save_storage($storage);

        flash_set('success', 'Order cancelled successfully.');
        header('Location: /WHERE-IS-MY-BLOOD/user/buy_blood.php');
        exit;
    }
}

$orders = is_array($storage['orders'] ?? null) ? $storage['orders'] : [];
$myOrders = [];
foreach (array_reverse($orders) as $order) {
    $owner = (string)($order['user_username'] ?? '');
    $phone = (string)($order['phone'] ?? '');
    if ($owner === $currentUsername || ($owner === '' && $phone === $currentPhone)) {
        $myOrders[] = $order;
    }
}

$title = 'Buy Blood';
$active = 'buy-blood';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <h1>Buy Blood Section</h1>
    <p>Place an order for required blood units. Current sample rate: Rs <?php echo $pricePerUnit; ?> per unit.</p>
</section>

<section class="grid two-columns">
    <div class="panel">
        <h2>Available Stock</h2>
        <div class="chip-list">
            <?php foreach ($storage['inventory'] as $group => $units): ?>
                <span class="chip"><?php echo esc($group); ?>: <?php echo (int)$units; ?> units</span>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="panel">
        <h2>Place New Order</h2>
        <form method="POST" class="form">
            <input type="hidden" name="action" value="place_order">

            <label for="blood_group">Blood Group</label>
            <select id="blood_group" name="blood_group" required>
                <?php foreach (get_blood_groups() as $group): ?>
                    <option value="<?php echo esc($group); ?>"><?php echo esc($group); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="units">Units</label>
            <input type="number" id="units" name="units" min="1" required>

            <p class="hint">Estimated amount: <strong id="order-amount-preview" data-price-per-unit="<?php echo $pricePerUnit; ?>">Rs 0</strong></p>

            <label for="payment_mode">Payment Mode</label>
            <select id="payment_mode" name="payment_mode" required>
                <option value="Cash On Delivery">Cash On Delivery</option>
                <option value="UPI">UPI</option>
            </select>

            <label for="delivery_address">Delivery Address</label>
            <textarea id="delivery_address" name="delivery_address" rows="3" required></textarea>

            <button type="submit">Place Order</button>
        </form>
    </div>
</section>

<section class="panel">
    <h2>My Blood Orders</h2>
    <?php if (empty($myOrders)): ?>
        <p>No blood orders found for your account.</p>
    <?php else: ?>
        <div class="table-tools">
            <input class="table-search" type="text" placeholder="Search my orders..." data-table-search="my-orders-table" data-search-counter="my-orders-visible-count">
            <span class="table-counter">Visible rows: <strong id="my-orders-visible-count"><?php echo count($myOrders); ?></strong></span>
        </div>
        <div class="table-wrap">
            <table id="my-orders-table">
                <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Blood Group</th>
                    <th>Units</th>
                    <th>Total Amount</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($myOrders as $order): ?>
                    <?php $status = (string)($order['status'] ?? 'Order Placed'); ?>
                    <tr>
                        <td><?php echo esc((string)($order['order_id'] ?? '-')); ?></td>
                        <td><?php echo esc((string)($order['blood_group'] ?? '-')); ?></td>
                        <td><?php echo (int)($order['units'] ?? 0); ?></td>
                        <td>Rs <?php echo (int)($order['total_amount'] ?? 0); ?></td>
                        <td><?php echo esc((string)($order['payment_mode'] ?? '-')); ?></td>
                        <td>
                            <span class="status-badge <?php echo status_badge_class($status); ?>">
                                <?php echo esc($status); ?>
                            </span>
                        </td>
                        <td><?php echo esc((string)($order['created_at'] ?? '')); ?></td>
                        <td>
                            <?php if ($status === 'Order Placed' || $status === 'Processing'): ?>
                                <form method="POST" class="inline-form compact-form">
                                    <input type="hidden" name="action" value="cancel_order">
                                    <input type="hidden" name="order_id" value="<?php echo esc((string)($order['order_id'] ?? '')); ?>">
                                    <button type="submit">Cancel</button>
                                </form>
                            <?php else: ?>
                                <span class="hint">No action</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
