<?php

namespace App\Tests;

use \GuzzleHttp\Client;
use \PHPUnit\Framework\TestCase;
use \Wa72\HtmlPageDom\HtmlPage;
use \DOMDocument;

class SmsVerificationRoutesTest extends TestCase
{
    private $base_uri;

    function setup()
    {
      $host = WEB_SERVER_HOST;
      $port = WEB_SERVER_PORT;
      $this->base_uri = "http://{$host}:{$port}";
    }

    function getClient()
    {
      return new Client([
        'base_uri' => $this->base_uri,
        'http_errors' => false
      ]);
    }

    function testHomeConfigurationValid()
    {
        $response = $this->getClient()->request('GET', '/');
        $this->assertEquals(200, $response->getStatusCode());

        $page = new HtmlPage($response->getBody()->getContents());
        $document = $page->getDOMDocument();
        $cells = $page->filter('.table > tr > td');

        $accountSid = trim($cells->getNode(1)->nodeValue);
        $this->assertEquals($accountSid, getenv('TWILIO_ACCOUNT_SID'));

        $apiKey = trim($cells->getNode(3)->nodeValue);
        $this->assertEquals($apiKey, getenv('TWILIO_API_KEY'));

        $apiSecret = trim($cells->getNode(5)->nodeValue);
        $this->assertEquals($apiSecret, 'Configured properly');

        $sendingPhoneNumber = trim($cells->getNode(7)->nodeValue);
        $this->assertEquals($sendingPhoneNumber, getenv('SENDING_PHONE_NUMBER'));

        $appHash = trim($cells->getNode(9)->nodeValue);
        $this->assertEquals($appHash, getenv('APP_HASH'));

        $clientSecret = trim($cells->getNode(11)->nodeValue);
        $this->assertEquals($clientSecret, getenv('CLIENT_SECRET'));
    }

    function testKickoffVerificationWithValidRequest()
    {
        $requestData = [
          'client_secret' => getenv('CLIENT_SECRET'),
          'phone' => getenv('SENDING_PHONE_NUMBER')
        ];
        $response = $this->getClient()->post('/api/request', ['json' => $requestData]);
        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        extract($body);
        $this->assertEquals($success, true);
    }

    function testKickoffVerificationWithInvalidRequest()
    {
        $requestData = [];
        $response = $this->getClient()->post('/api/request', ['json' => $requestData]);
        $body = $response->getBody()->getContents();
        $this->assertEquals(400, $response->getStatusCode());
    }

    function testKickoffVerificationWithIncorrectClientSecret()
    {
        $requestData = [
          'client_secret' => 'not_the_right_secret',
          'phone' => getenv('SENDING_PHONE_NUMBER')
        ];
        $response = $this->getClient()->post('/api/request', ['json' => $requestData]);

        $body = $response->getBody()->getContents();
        $this->assertEquals(400, $response->getStatusCode());
    }

    function testInvalidVerificationCodeRequest()
    {
        $requestData = [
            'client_secret' => getenv('CLIENT_SECRET'),
            'sms_message' => 'verification code',
            'phone' => getenv('SENDING_PHONE_NUMBER')
        ];

        $response = $this->getClient()->post('/api/verify', ['json' => $requestData]);
        $this->assertEquals(400, $response->getStatusCode());
    }

    function testResetVerificationCode()
    {
        $requestData = [
            'client_secret' => getenv('CLIENT_SECRET'),
            'phone' => getenv('SENDING_PHONE_NUMBER')
        ];

        $response = $this->getClient()->post('/api/reset', ['json' => $requestData]);
        $this->assertEquals(200, $response->getStatusCode());
    }


    function testInvalidVerificationResetRequest()
    {
        $requestData = [
        ];

        $response = $this->getClient()->post('/api/reset', ['json' => $requestData]);
        $this->assertEquals(400, $response->getStatusCode());
    }

    function testInvalidResetRequestWithInvalidSecret()
    {
        $requestData = [
            'client_secret' => 'not_the_correct_client_secret',
            'phone' => getenv('SENDING_PHONE_NUMBER')
        ];

        $response = $this->getClient()->post('/api/reset', ['json' => $requestData]);
        $this->assertEquals(400, $response->getStatusCode());
    }
}
