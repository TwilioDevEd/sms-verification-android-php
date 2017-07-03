<?php

use \App\Controllers\SmsVerification;

$config = include_once(__DIR__ . '/config.php');
$k = new \Klein\Klein();

$k->respond('GET', '/', function($req, $res, $service) {
    global $config;
    $view = __DIR__ . '/views/index.php';
    return $service->render($view, array_merge($config, [
        'configured' => 'Configured properly',
        'notConfigured' => 'Not configured in .env'
    ]));
});

$k->respond('POST', '/api/request', function($req, $res) {
    global $config;
    $body = json_decode($req->body(), true);
    $client_secret = $body['client_secret'];
    $phone = $body['phone'];

    if (empty($client_secret) && empty($phone)) {
        $res->code(400);
        return $res->body(['message' => 'Both client_secret and phone are required.']);
    }

    if (!matchSecretKey($client_secret)) {
        $res->code(400);
        return $res->body(['message' => 'The client_secret parameter does not match.']);
    }

    $smsResult = SmsVerification::of($config)->request($phone);

    return $res->json([
        'success' => true,
        'time' => SmsVerification::EXPIRATION_IN_SECONDS
    ]);
});

$k->respond('POST', '/api/verify', function($req, $res) {
    global $config;
    $body = json_decode($req->body(), true);
    $client_secret = $body['client_secret'];
    $sms_message = $body['sms_message'];
    $phone =$body['phone'];

    if (empty($client_secret) && empty($phone) && empty($sms_message)) {
        $res->code(400);
        return $res->json(['message' => 'The client_secret, phone, and ' .
               'sms_message parameters are required']);
    }

    if (!matchSecretKey($client_secret)) {
        $res->code(400);
        return $res->json(['message' => 'The client_secret parameter does not match.']);
    }

    if (!SmsVerification::of($config)->verify($phone, $sms_message)) {
        $res->code(401);
        return $res->json([
            'success' => false,
            'msg' => 'Unable to validate code for this phone number'
        ]);
    }

    return $res->json([
        'success' => true,
        'phone' => $phone
    ]);
});

$k->respond('POST', '/api/reset', function($req, $res) {
    global $config;
    $body = json_decode($req->body(), true);
    $client_secret = $body['client_secret'];
    $phone = $body['phone'];

    if (empty($client_secret) && empty($phone)) {
        $res->code(400);
        return $res->json(['message' => 'The client_secret and phone parameters are required']);
    }

    if (!matchSecretKey($client_secret)) {
        $res->code(400);
        return $res->json(['message' => 'The client_secret parameter does not match.']);
    }

    if (!SmsVerification::of($config)->reset($phone)) {
        return $res->json([
            'success' => false,
            'msg' => 'Unable to reset code for this phone number'
        ]);
    }

    return $res->json([
        'success' => true,
        'phone' => $phone
    ]);
});

$k->dispatch();

function matchSecretKey($clientSecret)
{
    global $config;
    return $config['clientSecret'] == $clientSecret;
}
