<?php
session_start();

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/config.php';
require __DIR__ . '/src/mail-template.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

function redirectWithError(string $message): never
{
    header('Location: index.php?status=error&message=' . urlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithError('Ongeldige aanvraag.');
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    redirectWithError('De sessie is verlopen. Herlaad de pagina en probeer opnieuw.');
}

$name = trim((string)($_POST['customer_name'] ?? ''));
$email = trim((string)($_POST['customer_email'] ?? ''));
$bikeType = trim((string)($_POST['bike_type'] ?? ''));
$pickupNote = trim((string)($_POST['pickup_note'] ?? ''));

if ($name === '' || mb_strlen($name) > 100) {
    redirectWithError('Vul een geldige klantnaam in.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
    redirectWithError('Vul een geldig e-mailadres in.');
}

if ($bikeType === '' || mb_strlen($bikeType) > 150) {
    redirectWithError('Vul een geldige fietsomschrijving in.');
}

if (mb_strlen($pickupNote) > 500) {
    redirectWithError('De extra boodschap is te lang.');
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_ENCRYPTION;
    $mail->Port = SMTP_PORT;
    $mail->CharSet = 'UTF-8';

    $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
    $mail->addReplyTo(MAIL_REPLY_TO, MAIL_FROM_NAME);
    $mail->addAddress($email, $name);

    $mail->isHTML(true);
    $mail->Subject = 'Je fiets staat klaar voor afhaling';
    $mail->Body = buildMailHtml($name, $bikeType, $pickupNote);
    $mail->AltBody = buildMailText($name, $bikeType, $pickupNote);

    $mail->send();

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header('Location: index.php?status=success');
    exit;
} catch (Exception $exception) {
    $error = $mail->ErrorInfo ?: $exception->getMessage();
    error_log('AAB mail error: ' . $error);

    redirectWithError('SMTP-fout: ' . $error);
}
