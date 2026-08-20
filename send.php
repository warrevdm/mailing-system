<?php

declare(strict_types=1);

require __DIR__ . '/src/auth.php';
require __DIR__ . '/src/mail-template.php';

$currentUser = authRequireLogin();

function redirectWithMessage(string $status, string $message): never
{
    header('Location: index.php?status=' . urlencode($status) . '&message=' . urlencode($message));
    exit;
}

function cleanHeaderValue(string $value): string
{
    return trim(str_replace(["\r", "\n"], '', $value));
}

function buildEml(string $name, string $email, string $subject, string $htmlBody, string $textBody): string
{
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
    $eml .= '--' . $boundary . "\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n";
    $eml .= quoted_printable_encode($textBody) . "\r\n\r\n";
    $eml .= '--' . $boundary . "\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n";
    $eml .= quoted_printable_encode($htmlBody) . "\r\n\r\n--" . $boundary . "--\r\n";
    return $eml;
}

function downloadEml(string $name, string $email, string $subject, string $htmlBody, string $textBody, bool $fallback = false): never
{
    $eml = buildEml($name, $email, $subject, $htmlBody, $textBody);
    $safeFilename = preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($name));
    $filename = ($fallback ? 'fallback-' : '') . 'nieuwe-fiets-klaar-' . trim((string) $safeFilename, '-') . '.eml';
    header('Content-Type: message/rfc822');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($eml));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo $eml;
    exit;
}

function requestGraphToken(): array
{
    $tokenUrl = 'https://login.microsoftonline.com/' . rawurlencode(MS_TENANT_ID) . '/oauth2/v2.0/token';
    $curl = curl_init($tokenUrl);
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POSTFIELDS => http_build_query([
            'client_id' => MS_CLIENT_ID,
            'client_secret' => MS_CLIENT_SECRET,
            'scope' => MS_GRAPH_SCOPE,
            'grant_type' => 'client_credentials',
        ]),
    ]);
    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($response === false) return [false, 'Netwerkfout bij Microsoft-login: ' . $curlError];
    $data = json_decode((string) $response, true);
    if ($httpStatus < 200 || $httpStatus >= 300 || !is_array($data) || empty($data['access_token'])) {
        $detail = is_array($data) ? (string) ($data['error_description'] ?? $data['error'] ?? 'Onbekende authenticatiefout') : 'Ongeldig antwoord van Microsoft';
        return [false, 'Microsoft-authenticatie mislukt: ' . mb_substr($detail, 0, 300)];
    }
    return [true, (string) $data['access_token']];
}

function getInlineLogoAttachment(): ?array
{
    $logoPath = __DIR__ . '/assets/aab-logo-email.jpg';
    if (!is_file($logoPath) || !is_readable($logoPath)) {
        return null;
    }

    $logoBytes = file_get_contents($logoPath);
    if ($logoBytes === false || $logoBytes === '') {
        return null;
    }

    return [
        '@odata.type' => '#microsoft.graph.fileAttachment',
        'name' => 'aab-logo-email.jpg',
        'contentType' => 'image/jpeg',
        'contentId' => 'aab-logo',
        'isInline' => true,
        'contentBytes' => base64_encode($logoBytes),
    ];
}

function sendViaGraph(string $accessToken, string $email, string $subject, string $htmlBody): array
{
    $endpoint = rtrim(MS_GRAPH_BASE_URL, '/') . '/users/' . rawurlencode(MAIL_FROM_ADDRESS) . '/sendMail';
    $message = [
        'subject' => $subject,
        'body' => ['contentType' => 'HTML', 'content' => $htmlBody],
        'toRecipients' => [['emailAddress' => ['address' => $email]]],
        'replyTo' => [['emailAddress' => ['address' => MAIL_REPLY_TO]]],
    ];

    $logoAttachment = getInlineLogoAttachment();
    if ($logoAttachment !== null) {
        $message['attachments'] = [$logoAttachment];
    }

    $payload = [
        'message' => $message,
        'saveToSentItems' => true,
    ];

    $curl = curl_init($endpoint);
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($response === false) return [false, 'Netwerkfout bij Microsoft Graph: ' . $curlError];
    if ($httpStatus === 202) return [true, ''];
    $data = json_decode((string) $response, true);
    $detail = is_array($data) ? (string) ($data['error']['message'] ?? 'Onbekende Microsoft Graph-fout') : 'Ongeldig antwoord van Microsoft Graph';
    return [false, 'Microsoft Graph-fout (' . $httpStatus . '): ' . mb_substr($detail, 0, 300)];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirectWithMessage('error', 'Ongeldige aanvraag.');
if (!authVerifyCsrf($_POST['csrf_token'] ?? null)) redirectWithMessage('error', 'De sessie is verlopen. Herlaad de pagina en probeer opnieuw.');

$name = trim((string) ($_POST['customer_name'] ?? ''));
$email = trim((string) ($_POST['customer_email'] ?? ''));
$bikeType = trim((string) ($_POST['bike_type'] ?? ''));
$pickupNote = trim((string) ($_POST['pickup_note'] ?? ''));
$mode = (string) ($_POST['mode'] ?? 'graph');

if ($name === '' || mb_strlen($name) > 100) redirectWithMessage('error', 'Vul een geldige klantnaam in.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) redirectWithMessage('error', 'Vul een geldig e-mailadres in.');
if ($bikeType === '' || mb_strlen($bikeType) > 150) redirectWithMessage('error', 'Vul een geldige fietsomschrijving in.');
if (mb_strlen($pickupNote) > 500) redirectWithMessage('error', 'De extra boodschap is te lang.');

$subject = 'Je nieuwe fiets staat klaar voor afhaling';
$htmlBody = buildMailHtml($name, $bikeType, $pickupNote);
$textBody = buildMailText($name, $bikeType, $pickupNote);
authRotateCsrf();

if ($mode === 'eml') {
    authAudit('mail_eml_created');
    authLogMail((int) $currentUser['id'], $name, $email, $bikeType, $pickupNote, $subject, 'eml', 'eml_created');
    downloadEml($name, $email, $subject, $htmlBody, $textBody);
}

if (!defined('INTERNAL_SEND_ENABLED') || INTERNAL_SEND_ENABLED !== true) {
    $error = 'Interne send mode is niet ingeschakeld.';
    authLogMail((int) $currentUser['id'], $name, $email, $bikeType, $pickupNote, $subject, 'graph', 'failed', $error);
    if (defined('EML_FALLBACK_ENABLED') && EML_FALLBACK_ENABLED === true) downloadEml($name, $email, $subject, $htmlBody, $textBody, true);
    redirectWithMessage('error', $error);
}

foreach (['MS_TENANT_ID','MS_CLIENT_ID','MS_CLIENT_SECRET','MS_GRAPH_BASE_URL','MS_GRAPH_SCOPE','MAIL_FROM_ADDRESS','MAIL_REPLY_TO'] as $constant) {
    if (!defined($constant) || trim((string) constant($constant)) === '' || str_contains((string) constant($constant), 'VUL_HIER')) {
        $error = 'De Microsoft Graph-configuratie is nog niet volledig ingesteld in src/config.php.';
        authLogMail((int) $currentUser['id'], $name, $email, $bikeType, $pickupNote, $subject, 'graph', 'failed', $error);
        redirectWithMessage('error', $error);
    }
}

[$tokenOk, $tokenResult] = requestGraphToken();
if (!$tokenOk) {
    error_log($tokenResult);
    authLogMail((int) $currentUser['id'], $name, $email, $bikeType, $pickupNote, $subject, 'graph', 'failed', $tokenResult);
    if (defined('EML_FALLBACK_ENABLED') && EML_FALLBACK_ENABLED === true) downloadEml($name, $email, $subject, $htmlBody, $textBody, true);
    redirectWithMessage('error', $tokenResult);
}

[$sendOk, $sendError] = sendViaGraph($tokenResult, $email, $subject, $htmlBody);
if (!$sendOk) {
    error_log($sendError);
    authAudit('mail_graph_failed');
    authLogMail((int) $currentUser['id'], $name, $email, $bikeType, $pickupNote, $subject, 'graph', 'failed', $sendError);
    if (defined('EML_FALLBACK_ENABLED') && EML_FALLBACK_ENABLED === true) downloadEml($name, $email, $subject, $htmlBody, $textBody, true);
    redirectWithMessage('error', $sendError);
}

authAudit('mail_graph_sent');
authLogMail((int) $currentUser['id'], $name, $email, $bikeType, $pickupNote, $subject, 'graph', 'sent');
redirectWithMessage('success', 'De mail werd rechtstreeks via Microsoft 365 verstuurd naar ' . $email . '.');
