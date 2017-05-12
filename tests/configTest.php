<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testErrorsForMissingTwilioConfiguration()
    {
        $twilioAccountSid = getenv('TWILIO_ACCOUNT_SID');
        putenv('TWILIO_ACCOUNT_SID=');

        $this->expectException(\DomainException::class);
        $config = include(__DIR__ . '/../app/config.php');

        putenv("TWILIO_ACCOUNT_SID={$twilioAccountSid}");
    }

    public function testErrorsForMissingSendingPhoneNumber()
    {
        $sendingPhoneNumber = getenv('SENDING_PHONE_NUMBER');
        putenv('SENDING_PHONE_NUMBER=');

        $this->expectException(\DomainException::class);
        $config = include(__DIR__ . '/../app/config.php');

        putenv("SENDING_PHONE_NUMBER={$sendingPhoneNumber}");
    }

    public function testErrorsForMissingAppHash()
    {
        $appHash = getenv('APP_HASH');
        putenv('APP_HASH=');

        $this->expectException(\DomainException::class);
        $config = include(__DIR__ . '/../app/config.php');

        putenv("APP_HASH={$appHash}");
    }

    public function testErrorsForMissingClientSecret()
    {
        $clientSecret = getenv('CLIENT_SECRET');
        putenv('CLIENT_SECRET=');

        $this->expectException(\DomainException::class);
        $config = include(__DIR__ . '/../app/config.php');

        putenv("CLIENT_SECRET={$clientSecret}");
    }
}
