<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if (!is_user_logged_in()) {
    flash_set('error', 'Please login first to continue.');
    header('Location: /WHERE-IS-MY-BLOOD/user/login.php');
    exit;
}

$pending = $_SESSION['pending_request'] ?? null;
if (!is_array($pending)) {
    flash_set('error', 'Please submit your details first.');
    header('Location: /WHERE-IS-MY-BLOOD/user/user.php');
    exit;
}

$step = (string)($_GET['step'] ?? 'type');
$kolkataInventory = read_storage()['inventory'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'select_request_type') {
        $requestType = (string)($_POST['request_type'] ?? '');

        if ($requestType === 'Blood Donation') {
            $storage = read_storage();
            $group = (string)($pending['blood_group'] ?? '');
            $units = (int)($pending['units'] ?? 0);
            $status = 'Donation Pending Verification';

            $storage['requests'][] = [
                'name' => (string)($pending['name'] ?? ''),
                'blood_group' => $group,
                'units' => $units,
                'phone' => (string)($pending['phone'] ?? ''),
                'location' => (string)($pending['location'] ?? ''),
                'pincode' => (string)($pending['pincode'] ?? ''),
                'purpose' => (string)($pending['purpose'] ?? ''),
                'request_type' => $requestType,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            save_storage($storage);
            unset($_SESSION['pending_request']);
            flash_set('success', 'Request submitted successfully as ' . $requestType . '. Status: ' . $status);
            header('Location: /WHERE-IS-MY-BLOOD/user/user.php');
            exit;
        }

        if ($requestType === 'Blood Collection') {
            header('Location: /WHERE-IS-MY-BLOOD/user/request_type.php?step=collection_mode');
            exit;
        }

        flash_set('error', 'Please select Blood Donation or Blood Collection.');
        header('Location: /WHERE-IS-MY-BLOOD/user/request_type.php');
        exit;
    }

    if ($action === 'submit_collection_mode') {
        $collectionMode = (string)($_POST['collection_mode'] ?? '');
        $selectedGroup = (string)($_POST['blood_group'] ?? '');
        $advancedHospital = trim((string)($_POST['advanced_hospital'] ?? ''));
        $advancedTime = trim((string)($_POST['advanced_time'] ?? ''));
        $advancedNotes = trim((string)($_POST['advanced_notes'] ?? ''));
        if (!in_array($collectionMode, ['Emergency', 'Regular'], true)) {
            flash_set('error', 'Please select Emergency or Regular.');
            header('Location: /WHERE-IS-MY-BLOOD/user/request_type.php?step=collection_mode');
            exit;
        }

        if (!in_array($selectedGroup, get_blood_groups(), true)) {
            flash_set('error', 'Please select a valid blood group.');
            header('Location: /WHERE-IS-MY-BLOOD/user/request_type.php?step=collection_mode');
            exit;
        }

        $storage = read_storage();
        $group = $selectedGroup;
        $units = (int)($pending['units'] ?? 0);
        $availableUnits = (int)($storage['inventory'][$group] ?? 0);

        $status = $availableUnits >= $units ? 'Approved (Auto)' : 'Pending';
        if ($collectionMode === 'Emergency' && $status === 'Pending') {
            $status = 'Emergency Pending';
        }

        if ($status === 'Approved (Auto)') {
            $storage['inventory'][$group] = $availableUnits - $units;
        }

        $storage['requests'][] = [
            'name' => (string)($pending['name'] ?? ''),
            'blood_group' => $group,
            'units' => $units,
            'phone' => (string)($pending['phone'] ?? ''),
            'location' => (string)($pending['location'] ?? ''),
            'pincode' => (string)($pending['pincode'] ?? ''),
            'purpose' => (string)($pending['purpose'] ?? ''),
            'request_type' => 'Blood Collection',
            'collection_mode' => $collectionMode,
            'advanced_hospital' => $advancedHospital,
            'advanced_time' => $advancedTime,
            'advanced_notes' => $advancedNotes,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        save_storage($storage);
        unset($_SESSION['pending_request']);

        flash_set('success', 'Request submitted as Blood Collection (' . $collectionMode . '). Status: ' . $status);
        header('Location: /WHERE-IS-MY-BLOOD/user/user.php');
        exit;
    }
}

$title = 'Select Request Type';
$active = 'user';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <h1>Select Request Type</h1>
    <p>Choose one option to continue your submission.</p>
</section>

<?php if ($step === 'collection_mode'): ?>
    <section class="panel">
        <h2>Blood Collection Type</h2>
        <p>Please choose one: Emergency or Regular.</p>
    </section>

    <form method="POST" class="panel option-panel">
        <input type="hidden" name="action" value="submit_collection_mode">

        <label for="collection_blood_group">Blood Group</label>
        <div class="rh-toggle" data-select-id="collection_blood_group">
            <button type="button" class="rh-btn active" data-rh="+">RH Positive (+)</button>
            <button type="button" class="rh-btn" data-rh="-">RH Negative (-)</button>
        </div>
        <select id="collection_blood_group" name="blood_group" required>
            <?php foreach (get_blood_groups() as $group): ?>
                <option value="<?php echo esc($group); ?>"><?php echo esc($group); ?></option>
            <?php endforeach; ?>
        </select>

        <section class="grid two-columns compact-grid">
            <div class="panel option-panel">
                <h2>Emergency</h2>
                <p>Choose this for urgent blood collection needs.</p>
                <button type="submit" name="collection_mode" value="Emergency">Submit as Emergency</button>
            </div>

            <div class="panel option-panel">
                <h2>Regular</h2>
                <p>Choose this for normal planned collection.</p>
                <button type="submit" name="collection_mode" value="Regular">Submit as Regular</button>
            </div>
        </section>

        <details class="advanced-section">
            <summary>Advanced Section</summary>
            <label for="advanced_hospital">Hospital Name</label>
            <input type="text" id="advanced_hospital" name="advanced_hospital" placeholder="Optional">

            <label for="advanced_time">Required Time</label>
            <input type="text" id="advanced_time" name="advanced_time" placeholder="Optional">

            <label for="advanced_notes">Additional Notes</label>
            <textarea id="advanced_notes" name="advanced_notes" rows="3" placeholder="Optional"></textarea>

            <button type="button" class="secondary-button bank-toggle" data-target="kolkata-groups" aria-expanded="false">
                Kolkata Blood Bank
            </button>
            <div id="kolkata-groups" class="bank-groups hidden">
                <p>Kolkata Blood Bank blood groups:</p>
                <div class="chip-list">
                    <?php foreach (get_blood_groups() as $bankGroup): ?>
                        <span class="chip"><?php echo esc($bankGroup); ?>: <?php echo (int)($kolkataInventory[$bankGroup] ?? 0); ?> units</span>
                    <?php endforeach; ?>
                </div>
            </div>
        </details>
    </form>
<?php else: ?>
    <section class="grid two-columns">
        <form method="POST" class="panel option-panel">
            <h2>Blood Donation</h2>
            <p>Use this if you want to donate blood.</p>
            <input type="hidden" name="action" value="select_request_type">
            <input type="hidden" name="request_type" value="Blood Donation">
            <button type="submit">Continue as Blood Donation</button>
        </form>

        <form method="POST" class="panel option-panel">
            <h2>Blood Collection</h2>
            <p>Use this if you need to collect blood.</p>
            <input type="hidden" name="action" value="select_request_type">
            <input type="hidden" name="request_type" value="Blood Collection">
            <button type="submit">Continue as Blood Collection</button>
        </form>
    </section>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var rhGroups = document.querySelectorAll('.rh-toggle');
    rhGroups.forEach(function (toggleGroup) {
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

    var toggleButtons = document.querySelectorAll('.bank-toggle');
    toggleButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-target');
            if (!targetId) {
                return;
            }

            var panel = document.getElementById(targetId);
            if (!panel) {
                return;
            }

            panel.classList.toggle('hidden');
            var expanded = !panel.classList.contains('hidden');
            button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
