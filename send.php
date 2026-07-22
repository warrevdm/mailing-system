<?php
session_start();

require __DIR__ . '/src/config.php';
require __DIR__ . '/src/mail-template.php';

function redirectWithError(string $message): never
{
    header('Location: index.php?status=error&message=' . urlencode($message));
    exit;
}

function cleanHeaderValue(string $value): string
{
    return trim(str_replace(["\r", "\n"], '', $value));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithError('Ongeldige aanvraag.');
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    redirectWithError('De sessie is verlopen. Herlaad de pagina en probeer opnieuw.');
}

$name = trim((string) ($_POST['customer_name'] ?? ''));
$email = trim((string) ($_POST['customer_email'] ?? ''));
$bikeType = trim((string) ($_POST['bike_type'] ?? ''));
$pickupNote = trim((string) ($_POST['pickup_note'] ?? ''));

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

$subject = 'Je fiets staat klaar voor afhaling';
$htmlBody = buildMailHtml($name, $bikeType, $pickupNote);
$textBody = buildMailText($name, $bikeType, $pickupNote);
$boundary = 'aab_' . bin2hex(random_bytes(16));

$fromName = cleanHeaderValue(MAIL_FROM_NAME);
$fromAddress = cleanHeaderValue(MAIL_FROM_ADDRESS);
$toAddress = cleanHeaderValue($email);
$encodedSubject = mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n");
$encodedFromName = mb_encode_mimeheader($fromName, 'UTF-8', 'B', "\r\n");

$headers = [
    'X-Unsent: 1',
    'From: ' . $encodedFromName . ' <' . $fromAddress . '>',
    'To: ' . $toAddress,
    'Subject: ' . $encodedSubject,
    'Date: ' . date(DATE_RFC2822),
    'MIME-Version: 1.0',
    'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
];

$eml = implode("\r\n", $headers) . "\r\n\r\n";
$eml .= '--' . $boundary . "\r\n";
$eml .= "Content-Type: text/plain; charset=UTF-8\r\n";
$eml .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
$eml .= quoted_printable_encode($textBody) . "\r\n\r\n";
$eml .= '--' . $boundary . "\r\n";
$eml .= "Content-Type: text/html; charset=UTF-8\r\n";
$eml .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
$eml .= quoted_printable_encode($htmlBody) . "\r\n\r\n";
$eml .= '--' . $boundary . "--\r\n";

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$safeFilename = preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($name));
$filename = 'fiets-klaar-' . trim((string) $safeFilename, '-') . '.eml';

header('Content-Type: message/rfc822');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($eml));
header('Cache-Control: no-store, no-cache, must-revalidate');

echo $eml;
exit;
