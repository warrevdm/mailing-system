<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$status = $_GET['status'] ?? '';
$message = $_GET['message'] ?? '';

// Oude SMTP-fouten kunnen als queryparameter in een opgeslagen of vernieuwde URL blijven staan.
// De huidige versie gebruikt geen SMTP meer, dus verwijder die verouderde melding automatisch.
if (
    $status === 'error'
    && (
        stripos($message, 'SMTP-fout') !== false
        || stripos($message, 'SMTP Error') !== false
        || stripos($message, 'Could not authenticate') !== false
    )
) {
    header('Location: index.php');
    exit;
}
?>
<!doctype html>
<html lang="nl-BE">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fiets klaar voor afhaling | Aerts Action Bike</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="app-shell">
    <section class="panel intro-panel">
        <div class="brand-mark">AAB</div>
        <p class="eyebrow">Aerts Action Bike</p>
        <h1>Fiets klaar voor afhaling</h1>
        <p class="lead">Vul de klantgegevens in en maak een professioneel Outlook-concept met dezelfde opmaak als het voorbeeld.</p>

        <div class="info-card">
            <strong>Zo werkt het</strong>
            <p>Het systeem downloadt een Outlook-concept met HTML-opmaak en een duidelijke bookingknop. Open het bestand, controleer de mail en klik zelf op verzenden.</p>
        </div>
    </section>

    <section class="panel form-panel">
        <?php if ($status === 'error'): ?>
            <div class="alert error"><?= htmlspecialchars($message ?: 'Het Outlook-concept kon niet worden gemaakt.', ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="alert success" id="downloadMessage" hidden>
            Het Outlook-concept is aangemaakt. Klik op het gedownloade bestand om het te openen.
        </div>

        <form id="mailForm" action="send.php" method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-grid">
                <div class="field">
                    <label for="customer_name">Naam klant</label>
                    <input id="customer_name" name="customer_name" type="text" autocomplete="name" required maxlength="100" placeholder="Bijvoorbeeld: Jan Peeters">
                    <small class="field-error"></small>
                </div>

                <div class="field">
                    <label for="customer_email">E-mailadres</label>
                    <input id="customer_email" name="customer_email" type="email" autocomplete="email" required maxlength="190" placeholder="jan@example.be">
                    <small class="field-error"></small>
                </div>

                <div class="field field-full">
                    <label for="bike_type">Soort fiets</label>
                    <input id="bike_type" name="bike_type" type="text" required maxlength="150" placeholder="Bijvoorbeeld: Trek Madone SL 7 Gen 8">
                    <small class="field-error"></small>
                </div>

                <div class="field field-full">
                    <label for="pickup_note">Extra boodschap <span>(optioneel)</span></label>
                    <textarea id="pickup_note" name="pickup_note" rows="4" maxlength="500" placeholder="Bijvoorbeeld: Gelieve je identiteitskaart mee te brengen."></textarea>
                    <small class="counter"><span id="noteCount">0</span>/500</small>
                </div>
            </div>

            <div class="actions">
                <button type="button" class="button button-secondary" id="previewButton">Voorbeeld bekijken</button>
                <button type="submit" class="button button-primary">Maak en open Outlook-concept</button>
            </div>
        </form>
    </section>
</main>

<dialog id="previewDialog">
    <div class="dialog-header">
        <div>
            <p class="eyebrow">Voorbeeld</p>
            <h2>Mail naar klant</h2>
        </div>
        <button type="button" class="icon-button" id="closePreview" aria-label="Voorbeeld sluiten">×</button>
    </div>
    <div id="previewContent" class="preview-content"></div>
</dialog>

<script src="assets/app.js" defer></script>
</body>
</html>
