<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if (!is_user_logged_in()) {
    flash_set('error', 'Please login first to access the user portal.');
    header('Location: /WHERE-IS-MY-BLOOD/user/login.php');
    exit;
}

$storage = read_storage();
$loggedInName = get_current_user_name();
$loggedInPhone = get_current_user_phone();
$loggedInUsername = get_current_user_username();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? 'submit_request');

    if ($action === 'cancel_request') {
        $requestId = trim((string)($_POST['request_id'] ?? ''));

        if ($requestId === '') {
            flash_set('error', 'Invalid request id.');
            header('Location: /WHERE-IS-MY-BLOOD/user/user.php');
            exit;
        }

        $index = find_request_index_by_id($storage['requests'], $requestId);
        if ($index < 0) {
            flash_set('error', 'Request not found.');
            header('Location: /WHERE-IS-MY-BLOOD/user/user.php');
            exit;
        }

        $request = $storage['requests'][$index];
        $owner = (string)($request['user_username'] ?? '');
        if ($owner !== '' && $owner !== $loggedInUsername) {
            flash_set('error', 'You can only cancel your own requests.');
            header('Location: /WHERE-IS-MY-BLOOD/user/user.php');
            exit;
        }

        $status = (string)($request['status'] ?? 'Pending');
        if ($status !== 'Pending' && $status !== 'Emergency Pending' && $status !== 'Donation Pending Verification') {
            flash_set('error', 'Only pending requests can be cancelled.');
            header('Location: /WHERE-IS-MY-BLOOD/user/user.php');
            exit;
        }

        $request['status'] = 'Cancelled';
        $request['updated_at'] = now();
        $storage['requests'][$index] = $request;
        save_storage($storage);

        flash_set('success', 'Request ' . $requestId . ' cancelled successfully.');
        header('Location: /WHERE-IS-MY-BLOOD/user/user.php');
        exit;
    }

    $name = trim((string)($_POST['name'] ?? ''));
    $group = (string)($_POST['blood_group'] ?? '');
    $units = (int)($_POST['units'] ?? 0);
    $phone = preg_replace('/\D+/', '', trim((string)($_POST['phone'] ?? '')));
    $location = trim((string)($_POST['location'] ?? ''));
    $pincode = preg_replace('/\D+/', '', trim((string)($_POST['pincode'] ?? '')));
    $purpose = trim((string)($_POST['purpose'] ?? ''));

    if (
        $name === '' ||
        !is_string($phone) ||
        !is_valid_phone($phone) ||
        $location === '' ||
        !is_string($pincode) ||
        !is_valid_pincode($pincode) ||
        $purpose === '' ||
        !in_array($group, get_blood_groups(), true) ||
        $units < 1
    ) {
        flash_set('error', 'Please provide valid request details.');
        header('Location: /WHERE-IS-MY-BLOOD/user/user.php');
        exit;
    }

    $_SESSION['pending_request'] = [
        'name' => $name,
        'blood_group' => $group,
        'units' => $units,
        'phone' => $phone,
        'location' => $location,
        'pincode' => $pincode,
        'purpose' => $purpose,
        'user_username' => $loggedInUsername,
    ];

    header('Location: /WHERE-IS-MY-BLOOD/user/request_type.php');
    exit;
}

$myRequests = [];
foreach (array_reverse($storage['requests']) as $request) {
    $owner = (string)($request['user_username'] ?? '');
    $phone = (string)($request['phone'] ?? '');
    if ($owner === $loggedInUsername || ($owner === '' && $phone === $loggedInPhone)) {
        $myRequests[] = $request;
    }
}

$title = 'User Portal';
$active = 'user-portal';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <h1>User Portal</h1>
    <p>Welcome <?php echo esc($loggedInName); ?>. Check blood availability and submit a blood request.</p>
    <p><a class="link-button" href="/WHERE-IS-MY-BLOOD/user/buy_blood.php">Open Buy Blood Section</a></p>
</section>

<section class="panel">
    <h2>Available Blood Units</h2>
    <div class="chip-list">
        <?php foreach ($storage['inventory'] as $group => $units): ?>
            <span class="chip"><?php echo esc($group); ?>: <?php echo (int)$units; ?> units</span>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel">
    <h2>Submit Blood Request</h2>
    <form method="POST" class="form">
        <label for="name">Patient / Requester Name</label>
        <input type="text" id="name" name="name" value="<?php echo esc($loggedInName); ?>" required>

        <label for="blood_group">Blood Group Needed</label>
        <div class="rh-toggle" data-select-id="blood_group">
            <button type="button" class="rh-btn active" data-rh="+">RH Positive (+)</button>
            <button type="button" class="rh-btn" data-rh="-">RH Negative (-)</button>
        </div>
        <select id="blood_group" name="blood_group" required>
            <?php foreach (get_blood_groups() as $group): ?>
                <option value="<?php echo esc($group); ?>"><?php echo esc($group); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="units">Units Needed</label>
        <input type="number" id="units" name="units" min="1" required>

        <label for="phone">Contact Number</label>
        <input type="text" id="phone" name="phone" value="<?php echo esc($loggedInPhone); ?>" required>

        <label for="location">Location</label>
        <input type="text" id="location" name="location" required>

        <label for="pincode">Pincode</label>
        <input type="text" id="pincode" name="pincode" required>

        <label for="purpose">Purpose</label>
        <textarea id="purpose" name="purpose" rows="3" required></textarea>

        <button type="submit">Submit Request</button>
        <p class="hint">Phone must be 10-15 digits. Pincode must be 6 digits.</p>
    </form>
</section>

<section class="panel">
    <h2>My Recent Requests</h2>
    <?php if (empty($myRequests)): ?>
        <p>No requests found for your account yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Blood Group</th>
                    <th>Units</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($myRequests as $request): ?>
                    <?php $status = (string)($request['status'] ?? 'Pending'); ?>
                    <tr>
                        <td><?php echo esc((string)($request['request_id'] ?? '-')); ?></td>
                        <td><?php echo esc((string)($request['blood_group'] ?? '-')); ?></td>
                        <td><?php echo (int)($request['units'] ?? 0); ?></td>
                        <td><?php echo esc((string)($request['request_type'] ?? '-')); ?></td>
                        <td>
                            <span class="status-badge <?php echo status_badge_class($status); ?>">
                                <?php echo esc($status); ?>
                            </span>
                        </td>
                        <td><?php echo esc((string)($request['created_at'] ?? '')); ?></td>
                        <td>
                            <?php if ($status === 'Pending' || $status === 'Emergency Pending' || $status === 'Donation Pending Verification'): ?>
                                <form method="POST" class="inline-form compact-form">
                                    <input type="hidden" name="action" value="cancel_request">
                                    <input type="hidden" name="request_id" value="<?php echo esc((string)($request['request_id'] ?? '')); ?>">
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
        <p><a class="link-button" href="/WHERE-IS-MY-BLOOD/user/history.php">View full request history</a></p>
    <?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var groups = document.querySelectorAll('.rh-toggle');

    groups.forEach(function (toggleGroup) {
        var selectId = toggleGroup.getAttribute('data-select-id');
        if (!selectId) {
            return;
        }

        var select = document.getElementById(selectId);
        if (!select) {
            return;
        }

        var allOptions = Array.from(select.options).map(function (opt) {
            return { value: opt.value, text: opt.text };
        });

        function setOptionsByRh(rh) {
            var filtered = allOptions.filter(function (item) {
                return item.value.endsWith(rh);
            });

            select.innerHTML = '';
            filtered.forEach(function (item) {
                var option = document.createElement('option');
                option.value = item.value;
                option.textContent = item.text;
                select.appendChild(option);
            });
        }

        var buttons = toggleGroup.querySelectorAll('.rh-btn');
        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                var rh = button.getAttribute('data-rh');
                if (rh !== '+' && rh !== '-') {
                    return;
                }

                buttons.forEach(function (btn) {
                    btn.classList.remove('active');
                });
                button.classList.add('active');

                setOptionsByRh(rh);
            });
        });

        setOptionsByRh('+');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
