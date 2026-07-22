<?php
session_start();

require __DIR__ . '/src/config.php';
require __DIR__ . '/src/mail-template.php';

function redirectWithMessage(string $status, string $message): never
{
    header('Location: index.php?status=' . urlencode($status) . '&message=' . urlencode($message));
    exit;
}

function resendFriendlyError(int $httpStatus, string $detail): string
{
    $normalized = strtolower($detail);

    if ($httpStatus === 401 || str_contains($normalized, 'api key')) {
        return 'De Resend API-key is ongeldig of ingetrokken. Maak een nieuwe key en plaats die in src/config.php.';
    }

    if (str_contains($normalized, 'domain') || str_contains($normalized, 'verify')) {
        return 'Het verzenddomein of afzenderadres is nog niet geverifieerd in Resend.';
    }

    if (str_contains($normalized, 'from')) {
        return 'Het afzenderadres is niet toegestaan. Controleer MAIL_FROM_ADDRESS in src/config.php.';
    }

    if ($httpStatus === 429) {
        return 'De verzendlimiet van Resend is tijdelijk bereikt. Probeer later opnieuw.';
    }

    return 'Resend-fout: ' . mb_substr($detail, 0, 240);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('error', 'Ongeldige aanvraag.');
}

$csrfToken = (string) ($_POST['csrf_token'] ?? '');
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    redirectWithMessage('error', 'De sessie is verlopen. Herlaad de pagina en probeer opnieuw.');
}

$name = trim((string) ($_POST['customer_name'] ?? ''));
$email = trim((string) ($_POST['customer_email'] ?? ''));
$bikeType = trim((string) ($_POST['bike_type'] ?? ''));
$pickupNote = trim((string) ($_POST['pickup_note'] ?? ''));

if ($name === '' || mb_strlen($name) > 100) {
    redirectWithMessage('error', 'Vul een geldige klantnaam in.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
    redirectWithMessage('error', 'Vul een geldig e-mailadres in.');
}
if ($bikeType === '' || mb_strlen($bikeType) > 150) {
    redirectWithMessage('error', 'Vul een geldige fietsomschrijving in.');
}
if (mb_strlen($pickupNote) > 500) {
    redirectWithMessage('error', 'De extra boodschap is te lang.');
}
if (!defined('RESEND_API_KEY') || RESEND_API_KEY === '' || str_contains(RESEND_API_KEY, 'VUL_HIER')) {
    redirectWithMessage('error', 'De verzendkoppeling is nog niet ingesteld. Vul de Resend API-key in src/config.php in.');
}

$payload = [
    'from' => MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>',
    'to' => [$email],
    'reply_to' => MAIL_REPLY_TO,
    'subject' => 'Je fiets staat klaar voor afhaling',
    'html' => buildMailHtml($name, $bikeType, $pickupNote),
    'text' => buildMailText($name, $bikeType, $pickupNote),
];

$curl = curl_init('https://api.resend.com/emails');
curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . RESEND_API_KEY,
        'Content-Type: application/json',
        'Idempotency-Key: fiets-klaar-' . hash('sha256', strtolower($email) . '|' . $bikeType . '|' . date('Y-m-d-H-i')),
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
]);

$response = curl_exec($curl);
$curlError = curl_error($curl);
$httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($response === false) {
    error_log('Resend netwerkfout: ' . $curlError);
    redirectWithMessage('error', 'Netwerkfout bij Resend: ' . mb_substr($curlError, 0, 180));
}

$data = json_decode((string) $response, true);
if ($httpStatus < 200 || $httpStatus >= 300 || !is_array($data) || empty($data['id'])) {
    $detail = is_array($data) ? (string) ($data['message'] ?? 'Onbekende fout') : 'Ongeldig antwoord van Resend';
    error_log('Resend fout (' . $httpStatus . '): ' . $detail);
    redirectWithMessage('error', resendFriendlyError($httpStatus, $detail));
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
redirectWithMessage('success', 'De mail werd succesvol verstuurd naar ' . $email . '.');
