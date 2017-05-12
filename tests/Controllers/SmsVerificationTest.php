<?php

namespace App\Tests;

use \App\Controllers\SmsVerification;
use \PHPUnit\Framework\TestCase;
use \Twilio\Rest\Client;
use \Twilio\Version;


class SmsVerificationTest extends TestCase
{
    public $config = [
        'twilioAccountSID' => 'ACXXXXXXXX',
        'twilioApiKey' => 'ISXXXXXXXX',
        'twilioApiSecret' => 'XXXXXXXXXX',
        'sendingPhoneNumber' => '+15005550006',
        'appHash' => 'TEST_HASH',
        'clientSecret' => 'TEST_SECRET',
    ];

    public function testRequest()
    {
        $version = $this->createMock('\Twilio\Version');
        $version->method('create')->willReturn([]);

        $twilioClient = $this->createMock('\Twilio\Rest\Client');
        $twilioClient->messages = $version;

        $hash = SmsVerification::of(
            $this->config, $twilioClient
        )->request('+123');

        $this->assertRegExp('/^\d{6}$/i', "{$hash}");
    }

    public function testVerify()
    {
        // Assert false when the secrets are different
        $this->assertFalse(
            SmsVerification::of($this->config)->verify('+456', 'TEST_SECRET')
        );

        // Assert false when the phone is not stored
        $this->assertFalse(
            SmsVerification::of($this->config)->verify('+123', 'CLIENT_SECRET')
        );

        // Assert true when the phone and secret are properly given
        $this->assertFalse(
            SmsVerification::of($this->config)->verify('+123', 'TEST_SECRET')
        );
    }

    public function testReset()
    {
        // Assert false when there's no value cached
        $this->assertFalse(
            SmsVerification::of($this->config)->reset('+456')
        );

        // Assert true when there is value cached
        $this->assertTrue(
            SmsVerification::of($this->config)->reset('+123')
        );
    }
}
