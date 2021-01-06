<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SocketBus\SocketBus;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

class ApiTest extends TestCase {

    private function getSocketBus(): SocketBus
    {
        return new SocketBus([
            'app_id' => $_ENV['APP_ID'],
            'secret' => $_ENV['APP_SECRET'],
            'custom_encryption_key' => $_ENV['CUSTOM_ENCRYPTION_KEY'],
        ]);
    }

    public function testBroadcast() 
    {
        $response = $this->getSocketBus()->broadcast('private-app.20', 'App\\Events\\AppReadingReceivedEvent', [
            'food' => 'cupcacke'
        ]);

        self::assertTrue($response);
    }

    public function testAuthWebhook() 
    {
        $authorization1 = hash('sha256', "webhook:{$_ENV['APP_ID']}:{$_ENV['APP_SECRET']}");
        $authorization2 = hash('sha256', "webhook-wrong:{$_ENV['APP_ID']}:{$_ENV['APP_SECRET']}");

        self::assertTrue($this->getSocketBus()->authWebhook($authorization1));
        self::assertTrue(!$this->getSocketBus()->authWebhook($authorization2));
    }

    public function testAuthNormal()
    {
        $auth = $this->getSocketBus()->auth('socket-id', 'some-channel', true);
        
        self::assertTrue(strlen($auth['auth']) == 64);

        $auth2 = $this->getSocketBus()->auth('socket-id', 'some-channel', false);
        
        self::assertEquals($auth2['status'], 'noauth');
    }

    public function testAuthPresence()
    {
        $auth = $this->getSocketBus()->authPresence('socket-id', 'some-channel', 1, ['user_id' => 20]);
        self::assertTrue(isset($auth['data']));

        $auth2 = $this->getSocketBus()->authPresence('socket-id', 'some-channel', 1, false);
        self::assertEquals($auth2['status'], 'noauth');
    }


    public function testApi()
    {
        $status = $this->getSocketBus()->getStatus();
        self::assertTrue(isset($status->users_count));

        $channels = $this->getSocketBus()->getChannels();
        self::assertIsArray($channels->rooms);

        $countInChannel = $this->getSocketBus()->getCountUsersInChannel('private-teste');
        self::assertTrue(isset($countInChannel->users_count));

        $usersInChannel = $this->getSocketBus()->getChannelUsers('presence-teste');
        self::assertIsArray($usersInChannel->users);

    }
}