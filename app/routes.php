<?php

use \App\Controllers\SmsVerification;

$config = include_once(__DIR__ . '/config.php');
$k = new \Klein\Klein();

$k->respond('GET', '/', function($req, $res, $service) use ($config) {
    $view = __DIR__ . '/views/index.php';
    return $service->render($view, array_merge($config, [
        'configured' => 'Configured properly',
        'notConfigured' => 'Not configured in .env'
    ]));
});

$k->respond('POST', '/api/request', function($req, $res) use($config) {
    $clientSecret = $req->param('client_secret');
    $phone = $req->param('phone');

    if (!requiredParams([$clientSecret, $phone])) {
        $res->status(400);
        return 'Both client_secret and phone are required.';
    }

    if (!matchSecretKey($clientSecret)) {
        $res->status(400);
        return 'The client_secret parameter does not match.';
    }

    SmsVerification::of($config)->request($phone);
    $res->send([
        'success' => true,
        'time' => SmsVerification::EXPIRATION_IN_SECONDS
    ]);
});

$k->respond('POST', '/api/verify', function($req, $res) use ($config) {
    $clientSecret = $req->param('client_secret');
    $smsMessage = $req->param('phone');
    $phone = $req->param('sms_message');

    if (!requiredParams([$clientSecret, $phone, $smsMessage])) {
        $res->status(400);
        return 'The client_secret, phone, and ' .
               'sms_message parameters are required';
    }

    if (!matchSecretKey($clientSecret)) {
        $res->status(400);
        return 'The client_secret parameter does not match.';
    }

    if (!SmsVerification::of($config)->verify($phone, $smsMessage)) {
        return [
            'success' => false,
            'msg' => 'Unable to validate code for this phone number'
        ];
    }
    return [
        'success' => true,
        'phone' => phone
    ];
});

$k->respond('POST', '/api/reset', function($req, $res) use ($config) {
    $clientSecret = $req->param('client_secret');
    $phone = $req->param('phone');

    if (!requiredParams([$clientSecret, $phone])) {
        $res->status(400);
        return 'The client_secret and phone parameters are required';
    }

    if (!matchSecretKey($clientSecret)) {
        $res->status(400);
        return 'The client_secret parameter does not match.';
    }

    if (!SmsVerification::of($config)->reset($phone)) {
        return [
            'success' => false,
            'msg' => 'Unable to reset code for this phone number'
        ];
    }

    return [
        'success' => true,
        'phone' => $phone
    ];
});

$k->dispatch();

function matchSecretKey($clientSecret)
{
    return $config['clientSecret'] == $clientSecret;
}

function requiredParams($params)
{
    return !in_array('', array_map('trim', $params));
}
