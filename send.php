<?php
session_start();

require __DIR__ . '/src/config.php';
require __DIR__ . '/src/mail-template.php';

function redirectWithError(string $message): never
{
    header('Location: index.php?status=error&message=' . urlencode($message));
    exit;
}

function graphRequest(string $url, array $options): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('De PHP cURL-extensie is niet actief.');
    }

    $curl = curl_init($url);
    curl_setopt_array($curl, $options + [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
    ]);

    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($body === false) {
        throw new RuntimeException('Netwerkfout: ' . $curlError);
    }

    return [$status, $body];
}

function getGraphAccessToken(): string
{
    $tokenUrl = sprintf(
        'https://login.microsoftonline.com/%s/oauth2/v2.0/token',
        rawurlencode(GRAPH_TENANT_ID)
    );

    [$status, $body] = graphRequest($tokenUrl, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POSTFIELDS => http_build_query([
            'client_id' => GRAPH_CLIENT_ID,
            'client_secret' => GRAPH_CLIENT_SECRET,
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
        ], '', '&', PHP_QUERY_RFC3986),
    ]);

    $data = json_decode($body, true);

    if ($status !== 200 || !is_array($data) || empty($data['access_token'])) {
        $detail = $data['error_description'] ?? $data['error'] ?? 'Onbekende tokenfout';
        throw new RuntimeException('OAuth-token kon niet worden opgehaald: ' . $detail);
    }

    return $data['access_token'];
}

function sendGraphMail(string $accessToken, string $name, string $email, string $bikeType, string $pickupNote): void
{
    $endpoint = sprintf(
        'https://graph.microsoft.com/v1.0/users/%s/sendMail',
        rawurlencode(GRAPH_SENDER_ADDRESS)
    );

    $payload = [
        'message' => [
            'subject' => 'Je fiets staat klaar voor afhaling',
            'body' => [
                'contentType' => 'HTML',
                'content' => buildMailHtml($name, $bikeType, $pickupNote),
            ],
            'toRecipients' => [[
                'emailAddress' => [
                    'address' => $email,
                    'name' => $name,
                ],
            ]],
            'replyTo' => [[
                'emailAddress' => [
                    'address' => MAIL_REPLY_TO,
                    'name' => MAIL_FROM_NAME,
                ],
            ]],
        ],
        'saveToSentItems' => true,
    ];

    [$status, $body] = graphRequest($endpoint, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
    ]);

    if ($status !== 202) {
        $data = json_decode($body, true);
        $detail = $data['error']['message'] ?? $body ?: 'Onbekende Microsoft Graph-fout';
        throw new RuntimeException(sprintf('Microsoft Graph weigerde de mail (%d): %s', $status, $detail));
    }
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

try {
    $accessToken = getGraphAccessToken();
    sendGraphMail($accessToken, $name, $email, $bikeType, $pickupNote);

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header('Location: index.php?status=success');
    exit;
} catch (Throwable $exception) {
    error_log('AAB Graph mail error: ' . $exception->getMessage());
    redirectWithError('Microsoft Graph-fout: ' . $exception->getMessage());
}
