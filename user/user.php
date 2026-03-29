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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $group = (string)($_POST['blood_group'] ?? '');
    $units = (int)($_POST['units'] ?? 0);
    $phone = trim((string)($_POST['phone'] ?? ''));
    $location = trim((string)($_POST['location'] ?? ''));
    $pincode = trim((string)($_POST['pincode'] ?? ''));
    $purpose = trim((string)($_POST['purpose'] ?? ''));

    if (
        $name === '' ||
        $phone === '' ||
        $location === '' ||
        $pincode === '' ||
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
    ];

    header('Location: /WHERE-IS-MY-BLOOD/user/request_type.php');
    exit;
}

$title = 'User Portal';
$active = 'user-portal';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <h1>User Portal</h1>
    <p>Welcome <?php echo esc($loggedInName); ?>. Check blood availability and submit a blood request.</p>
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
    </form>
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
