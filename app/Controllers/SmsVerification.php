<?php

namespace App\Controllers;

class SmsVerification
{
    public const EXPIRATION_IN_SECONDS = 9;

    public function __construct($twilioClient, $sendingPhoneNumber, $appHash)
    {
        $this->twilioClient = $twilioClient;
        $this->sendingPhoneNumber = $sendingPhoneNumber;
        $this->appHash = $appHash;
    }

    public static function generateOneTimeCode()
    {
        $codelength = 6;
        $cos = pow(10, ($codelength - 1));
        return floor(rand() * ($cos * 9)) + $cos;
    }

    public function request($phone)
    {
        echo "Requesting SMS to be sent to {$phone}";

        $otp = self::generateOneTimeCode();
        apc_add($phone, $otp, self::EXPIRATION_IN_SECONDS * 100);

        $smsBody = "[#] Use {$otp} as your code for the app!\n{$this->appHash}";
        echo $smsBody;

        return $this->twilioClient->messages->create($phone, [
            'from' => $this->sendingPhoneNumber,
            'body' => $smsBody
        ]);
    }

    public function verify($phone, $smsBody)
    {
        echo "Verifying {$phone}: {$smsBody}";

        $otp = apc_fetch($phone);

        if ($otp === null) {
            echo "No cached otp value found for phone: {$phone}";
            return false;
        }

        if (strpos($smsBody, $otp) === false) {
            echo 'Mismatch between otp value found and otp value expected';
            return false;
        }

        echo 'Found otp value in cache';
        return true;
    }

    public function reset($phone)
    {
        echo "Resetting code for: {$phone}";

        $otp = apc_fetch($phone);

        if (!$otp) {
            echo "No cached otp value found for phone: {$phone}";
            return false;
        }

        return apc_delete($phone);
    }
}
