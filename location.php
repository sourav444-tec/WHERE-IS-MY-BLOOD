<?php

declare(strict_types=1);

$title = 'Location';
$active = 'location';
require_once __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <h1>Kolkata Blood Bank Locations</h1>
    <p>Nearest Kolkata blood bank centers and support points.</p>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Center</th>
                <th>Area</th>
                <th>Contact</th>
                <th>Open</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>Kolkata Central Blood Bank</td>
                <td>Esplanade, Kolkata</td>
                <td>+91 90000 11111</td>
                <td>24/7</td>
            </tr>
            <tr>
                <td>North Kolkata Blood Unit</td>
                <td>Shyambazar, Kolkata</td>
                <td>+91 90000 22222</td>
                <td>8 AM - 10 PM</td>
            </tr>
            <tr>
                <td>South Kolkata Blood Unit</td>
                <td>Gariahat, Kolkata</td>
                <td>+91 90000 33333</td>
                <td>8 AM - 10 PM</td>
            </tr>
            </tbody>
        </table>
    </div>

    <p class="hint">Tip: Call before visiting for real-time stock confirmation.</p>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
