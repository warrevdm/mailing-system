<?php
session_start();

require __DIR__ . '/src/config.php';

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

$lines = [
    'Beste ' . $name . ',',
    '',
    'Goed nieuws: je fiets staat klaar voor afhaling bij Aerts Action Bike.',
    '',
    'Fiets: ' . $bikeType,
];

if ($pickupNote !== '') {
    $lines[] = '';
    $lines[] = $pickupNote;
}

$lines = array_merge($lines, [
    '',
    'Plan hier eenvoudig je afhaalmoment:',
    BOOKING_URL,
    '',
    'Zo kunnen we voldoende tijd voorzien om je fiets samen te overlopen en correct af te stellen.',
    '',
    'Sportieve groeten,',
    '',
    'Team Aerts Action Bike',
    'Kapellensteenweg 394',
    '2920 Kalmthout',
    '03 666 97 01',
    'www.aertsactionbike.be',
]);

$body = implode("\r\n", $lines);

$outlookUrl = 'https://outlook.office.com/mail/deeplink/compose?' . http_build_query([
    'to' => $email,
    'subject' => $subject,
    'body' => $body,
], '', '&', PHP_QUERY_RFC3986);

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

header('Location: ' . $outlookUrl);
exit;
