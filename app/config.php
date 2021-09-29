<?php

$file = file_exists(__DIR__ . '/../.env') ? '.env' : '.env.example';
$dotenv = new \Dotenv\Dotenv(__DIR__ . '/../', $file);
$dotenv->load();

$DISPLAY_ERRORS = $_ENV['DISPLAY_ERRORS'];
ini_set('display_errors', $DISPLAY_ERRORS);

$config = [
    'twilioAccountSID' => getenv('TWILIO_ACCOUNT_SID'),
    'twilioApiKey' => getenv('TWILIO_API_KEY'),
    'twilioApiSecret' => getenv('TWILIO_API_SECRET'),
    'sendingPhoneNumber' => getenv('SENDING_PHONE_NUMBER'),
    'appHash' => getenv('APP_HASH'),
    'clientSecret' => getenv('CLIENT_SECRET')
];

// Check configuration variables
if (!$config['twilioAccountSID'] ||
    !$config['twilioApiKey'] ||
    !$config['twilioApiSecret']) {
    throw new DomainException(
        'Please copy the .env.example file to .env, and then add your ' .
        'Twilio API Key, API Secret, and Account SID to the .env file. ' .
        'Find them on https://www.twilio.com/console'
    );
}

if (!$config['sendingPhoneNumber']) {
    throw new DomainException(
        'Please provide a valid phone number, ' .
        'such as +15125551212, in the .env file'
    );
}

if (!$config['appHash']) {
    throw new DomainException(
        'Please provide a valid Android app hash, which you can find in ' .
        'the Settings menu item of the Android app, in the .env file'
    );
}

if (!$config['clientSecret']) {
    throw new DomainException(
        'Please provide a secret string to share, between the app and the ' .
        'server in the .env file'
    );
}

return $config;
