<?php

namespace App\Controllers;

use \Twilio\Rest\Client;
use \Stash\Pool;
use \Stash\Driver\FileSystem;

class SmsVerification
{
    public const EXPIRATION_IN_SECONDS = 9;

    private function __construct($config, $twilioClient, $pool)
    {
        $this->config = $config;
        $this->twilioClient = $twilioClient;
        $this->pool = $pool;
        return $this;
    }

    public static function of($config, $twilioClient = null, $pool = null)
    {
        $twilioClient = $twilioClient ?: new Client(
          $config['twilioApiKey'],
          $config['twilioApiSecret'],
          $config['twilioAccountSID']
        );
        $pool = $pool ?: new Pool(new FileSystem([]));

        return new self($config, $twilioClient, $pool);
    }

    public function request($phone)
    {
        $otp = rand(100000, 999999);

        $item = $this->pool
                     ->getItem($phone)
                     ->set($otp)
                     ->expiresAfter(self::EXPIRATION_IN_SECONDS * 100);

        $this->pool->save($item);

        $smsBody = "\n[#] Use {$otp} as your code for the app!\n" .
                   "{$this->config['appHash']}\n";

        if (getenv('RUN_ENV') !== 'test') {
            $this->twilioClient->messages->create($phone, [
              'from' => $this->config['sendingPhoneNumber'],
              'body' => $smsBody
            ]);
        }

        return $otp;
    }

    public function verify($phone, $smsBody)
    {
        if (!$this->pool->hasItem($phone)) {
            return false;
        }

        $otp = $this->pool->getItem($phone)->get();
        return (bool) strpos("$smsBody", "$otp");
    }

    public function reset($phone)
    {
        if (!$this->pool->hasItem($phone)) {
            return false;
        }

        return $this->pool->deleteItems([$phone]);
    }
}
