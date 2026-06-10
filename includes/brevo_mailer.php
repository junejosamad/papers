<?php

function getBrevoApiKey()
{
    global $pdo;

    $apiKey = '';
    if (isset($pdo) && function_exists('getSystemSetting')) {
        $apiKey = trim((string) getSystemSetting($pdo, 'brevo_api_key', ''));
    }

    if ($apiKey === '' && defined('BREVO_API_KEY')) {
        $apiKey = trim((string) BREVO_API_KEY);
    }

    return $apiKey;
}

function getConfiguredFromEmail()
{
    global $pdo;

    if (isset($pdo) && function_exists('getSystemSetting')) {
        $fromEmail = trim((string) getSystemSetting($pdo, 'from_email', ''));
        if ($fromEmail !== '') {
            return $fromEmail;
        }
    }

    return defined('FROM_EMAIL') ? FROM_EMAIL : 'papers@example.com';
}

function getConfiguredFromName()
{
    global $pdo;

    if (isset($pdo) && function_exists('getSystemSetting')) {
        $fromName = trim((string) getSystemSetting($pdo, 'from_name', ''));
        if ($fromName !== '') {
            return $fromName;
        }
    }

    return defined('FROM_NAME') ? FROM_NAME : 'Bahria Papers Portal';
}

function sendBrevoEmail($to, $subject, $htmlBody, $plainBody = '', $toName = '')
{
    $to = trim((string) $to);
    $subject = (string) $subject;
    $htmlBody = (string) $htmlBody;
    $plainBody = (string) $plainBody;
    $toName = trim((string) $toName);

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'http_code' => 0,
            'response' => '',
            'error' => 'Invalid recipient email address.'
        ];
    }

    $apiKey = getBrevoApiKey();
    if ($apiKey === '') {
        return [
            'success' => false,
            'http_code' => 0,
            'response' => '',
            'error' => 'Brevo API key is not configured.'
        ];
    }

    $fromEmail = getConfiguredFromEmail();
    $fromName = getConfiguredFromName();

    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'http_code' => 0,
            'response' => '',
            'error' => 'Sender email is not configured.'
        ];
    }

    if ($plainBody === '') {
        $plainBody = trim(html_entity_decode(strip_tags($htmlBody), ENT_QUOTES, 'UTF-8'));
    }

    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'http_code' => 0,
            'response' => '',
            'error' => 'PHP cURL extension is not enabled.'
        ];
    }

    $recipient = ['email' => $to];
    if ($toName !== '') {
        $recipient['name'] = $toName;
    }

    $payload = [
        'sender' => [
            'name' => $fromName,
            'email' => $fromEmail
        ],
        'to' => [$recipient],
        'subject' => $subject,
        'htmlContent' => $htmlBody,
        'textContent' => $plainBody
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    if ($ch === false) {
        return [
            'success' => false,
            'http_code' => 0,
            'response' => '',
            'error' => 'Unable to initialize cURL.'
        ];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = [
        'success' => ($curlError === '' && $httpCode >= 200 && $httpCode < 300),
        'http_code' => $httpCode,
        'response' => $response === false ? '' : $response,
        'error' => $curlError
    ];

    if (!$result['success']) {
        error_log('Brevo API email failure: ' . json_encode($result));
    }

    return $result;
}

function sendAppEmail($to, $subject, $htmlBody, $plainBody = '', $toName = '')
{
    return sendBrevoEmail($to, $subject, $htmlBody, $plainBody, $toName);
}
