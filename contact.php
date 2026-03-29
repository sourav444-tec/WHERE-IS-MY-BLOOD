<?php

declare(strict_types=1);

$title = 'Contact';
$active = 'contact';
require_once __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <h1>Company Customer Support</h1>
    <p>Use these customer support numbers for help with blood requests and services.</p>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Support Team</th>
                <th>Customer Support Number</th>
                <th>Availability</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>Main Customer Support</td>
                <td>+91 90000 10001</td>
                <td>24/7</td>
            </tr>
            <tr>
                <td>Emergency Blood Support</td>
                <td>+91 90000 10002</td>
                <td>24/7</td>
            </tr>
            <tr>
                <td>Donor Assistance</td>
                <td>+91 90000 10003</td>
                <td>8 AM - 10 PM</td>
            </tr>
            <tr>
                <td>Complaint and Feedback</td>
                <td>+91 90000 10004</td>
                <td>9 AM - 6 PM</td>
            </tr>
            </tbody>
        </table>
    </div>

    <p class="hint">Email support: support@whereismyblood.org</p>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
