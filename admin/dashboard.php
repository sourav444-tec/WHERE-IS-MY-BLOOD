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
        $phone = trim((string)($_POST['phone'] ?? ''));

        if ($name !== '' && $phone !== '' && in_array($group, get_blood_groups(), true)) {
            $storage['donors'][] = [
                'name' => $name,
                'blood_group' => $group,
                'phone' => $phone,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            save_storage($storage);
            flash_set('success', 'Donor added successfully.');
        } else {
            flash_set('error', 'Please provide valid donor details.');
        }

        header('Location: /WHERE-IS-MY-BLOOD/admin/dashboard.php');
        exit;
    }
}

$title = 'Admin Dashboard';
$active = 'admin';
require_once __DIR__ . '/../includes/header.php';
?>
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
        <?php foreach ($storage['inventory'] as $group => $units): ?>
            <span class="chip"><?php echo esc($group); ?>: <?php echo (int)$units; ?> units</span>
        <?php endforeach; ?>
    </div>
</section>

<section class="grid two-columns">
    <div class="panel">
        <h2>Registered Donors</h2>
        <?php if (empty($storage['donors'])): ?>
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
                    <?php foreach (array_reverse($storage['donors']) as $donor): ?>
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
        <h2>Blood Requests</h2>
        <?php if (empty($storage['requests'])): ?>
            <p>No requests submitted yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Blood Group</th>
                        <th>Units</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Pincode</th>
                        <th>Purpose</th>
                        <th>Request Type</th>
                        <th>Collection Mode</th>
                        <th>Advanced Hospital</th>
                        <th>Required Time</th>
                        <th>Advanced Notes</th>
                        <th>Status</th>
                        <th>Time</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (array_reverse($storage['requests']) as $request): ?>
                        <tr>
                            <td><?php echo esc($request['name'] ?? ''); ?></td>
                            <td><?php echo esc($request['blood_group'] ?? ''); ?></td>
                            <td><?php echo (int)($request['units'] ?? 0); ?></td>
                            <td><?php echo esc($request['phone'] ?? ''); ?></td>
                            <td><?php echo esc($request['location'] ?? ''); ?></td>
                            <td><?php echo esc($request['pincode'] ?? ''); ?></td>
                            <td><?php echo esc($request['purpose'] ?? ''); ?></td>
                            <td><?php echo esc($request['request_type'] ?? 'Blood Collection'); ?></td>
                            <td><?php echo esc($request['collection_mode'] ?? '-'); ?></td>
                            <td><?php echo esc($request['advanced_hospital'] ?? '-'); ?></td>
                            <td><?php echo esc($request['advanced_time'] ?? '-'); ?></td>
                            <td><?php echo esc($request['advanced_notes'] ?? '-'); ?></td>
                            <td><?php echo esc($request['status'] ?? ''); ?></td>
                            <td><?php echo esc($request['created_at'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
