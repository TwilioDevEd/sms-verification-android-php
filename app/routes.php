<?php

use \App\Controllers\SmsVerification;

$config = include(__DIR__ . '/config.php');
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

    if (!$clientSecret || !$phone) {
        $res->status(400);
        return 'Both client_secret and phone are required.';
    }

    if ($config['clientSecret'] != $clientSecret) {
        $res->status(400);
        return 'The client_secret parameter does not match.';
    }

    SmsVerification::request($phone);
    $res->send([
        'success' => true,
        'time' => SmsVerification::EXPIRATION_IN_SECONDS
    ]);
});

$k->respond('POST', '/api/verify', function($req, $res) {
    $clientSecret = $req->param('client_secret');
    $smsMessage = $req->param('phone');
    $phone = $req->param('sms_message');

    if (!$clientSecret || !$phone || !$smsMessage) {
        $res->status(400);
        return 'The client_secret, phone, and ' .
               'sms_message parameters are required';
    }

    if ($config['clientSecret'] != $clientSecret) {
        $res->status(400);
        return 'The client_secret parameter does not match.';
    }

    if (!SmsVerification::verify($phone, $smsMessage)) {
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

$k->respond('POST', '/api/reset', function($req, $res) {
    $clientSecret = $req->param('client_secret');
    $phone = $req->param('phone');

    if (!$clientSecret || !$phone) {
        $res->status(400);
        return 'The client_secret and phone parameters are required';
    }

    if ($config['clientSecret'] != $clientSecret) {
        $res->status(400);
        return 'The client_secret parameter does not match.';
    }

    if (!SmsVerification::reset($phone)) {
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
