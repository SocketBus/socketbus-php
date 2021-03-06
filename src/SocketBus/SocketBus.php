<?php

namespace SocketBus;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SocketBus
{
    /**
     * @var string Contains the public key of the application
     */
    private $publicKey;

    /**
     * @var string Contains the secret of the application
     */
    private $secretKey;

    /**
     * @var string Contains the custom encryption key for end-to-end encryption
     */
    private $customEncryptionKey;

    /**
     * @var string Contains the custom encryption key for end-to-end encryption
     */
    private $customDomain;

    /**
     * @var Client
     */
    private $guzzleClient;

    /**
     * Constructor store the keys and instanciate Curl
     * @throws \Exception
     */
    public function __construct(array $options)
    {
        $this->validateOptions($options);

        $this->publicKey = $options['app_id'];
        $this->secretKey = $options['secret'];

        $exploded = explode("-", $this->publicKey);

        $this->customDomain = isset($options['customDomain']) ? $options['customDomain'] : "https://s{$exploded[1]}.socketbus.com";
        $this->customEncryptionKey = isset($options['custom_encryption_key']) ? $options['custom_encryption_key'] : null;

        $this->buildClient();
    }

    private function buildClient()
    {
        $authorization = hash("sha256", "{$this->publicKey}:{$this->secretKey}");

        $authorization = "{$this->publicKey}:$authorization";

        $this->guzzleClient = new Client([
            'base_uri' => $this->customDomain,
            'timeout' => 30,
            'headers' => [
                'Authorization' => $authorization
            ]
        ]);
    }

    /**
     * Validates the public and private key
     * @return void
     * @throws \Exception
     */
    private function validateOptions($options)
    {
        $must_have = ['app_id', 'secret'];

        foreach($must_have as $m) {
            if (!isset($options[$m])) {
                throw new \Exception("Setting {$m} is missing!");
            }
        }
    }

    /**
     * Parses results
     * @return array
     */
    private function parseResult(array $response, $result, string $channelName)
    {
        $merge = [];

        if ($this->customEncryptionKey) {
            $merge['e2e'] = $this->generateE2EPassword($channelName);
        }

        return array_merge($response, $merge);
    }

    /**
     * Generates a auth token and mixes the data for response
     * @return array
     */
    public function auth(string $socketId, string $channelName, $result = true)
    {
        if (!$result) {
            return [
                'status' => 'noauth'
            ];
        }

        return $this->parseResult([
            'auth' => $this->generateHash($socketId, $channelName)
        ], $result, $channelName);
    }

    /**
     * Authenticates for presence data
     */
    public function authPresence(string $socketId, string $channelName, $userId, $result)
    {
        if (!$result) {
            return [
                'status' => 'noauth'
            ];
        }

        $encryption = $this->encrypt($this->encryptData($result, $channelName));

        return $this->parseResult([
            'auth' => $this->generateHash($socketId, $channelName),
            'data' => $encryption,
            'presence' => true
        ], $result, $channelName);
    }

    private function decrypt($str)
    {
        $encrypt_method = "AES-256-CBC";

        $key = substr(hash('sha256', $this->secretKey), 0, 32);
        $iv = substr(hash('sha256', $this->publicKey), 0, 16);

        return openssl_decrypt(base64_decode($str), $encrypt_method, $key, 0, $iv);
    }

    private function encrypt($data)
    {
        $json = json_encode($data);

        $encrypt_method = "AES-256-CBC";

        $key = substr(hash('sha256', $this->secretKey), 0, 32);
        $iv = substr(hash('sha256', $this->publicKey), 0, 16);
        return base64_encode(openssl_encrypt($json, $encrypt_method, $key, 0, $iv));
    }

    private function generateHash(string $socketId, string $channelName)
    {
        $str = "{$this->publicKey}:{$this->secretKey}:{$socketId}:{$channelName}";
        return hash('sha256', $str);
    }

    private function generateE2EPassword(string $channelName) {
        $str = "{$this->customEncryptionKey}:$channelName";
        return substr(hash('sha256', $str), 0, 32);
    }

    private function encryptData(array $data, string $channelName)
    {
        if (!$this->customEncryptionKey) {
            return $data;
        }

        $passphrase = $this->generateE2EPassword($channelName);

        $salt = openssl_random_pseudo_bytes(8);
        $salted = '';
        $dx = '';
        while (strlen($salted) < 48) {
            $dx = md5($dx . $passphrase . $salt, true);
            $salted .= $dx;
        }
        $key = substr($salted, 0, 32);
        $iv = substr($salted, 32, 16);
        $encrypted_data = openssl_encrypt(json_encode($data), 'aes-256-cbc', $key, true, $iv);
        $data = ["ct" => base64_encode($encrypted_data), "iv" => bin2hex($iv), "s" => bin2hex($salt)];
        return json_encode($data);
    }

    public function broadcast($channels, string $eventName, array $data = [], array $options = [])
    {
        if (is_string($channels)) {
            $channels = [$channels];
        }

        foreach($channels as $channel) {
            $this->guzzleClient->post("/api/channels/$channel/broadcast", [
                'json' => [
                    'event' => $eventName,
                    'data' => $this->encryptData($data, $channel),
                    'i' => isset($options['i']) ? $options['i'] : null
                ]
            ]);
        }
        return true;
    }

    private function get($url)
    {
        try {
            $response = $this->guzzleClient->get($url);
            return json_decode($response->getBody());
        } catch (RequestException $e) {
            if ($e->hasResponse()) {

            }
        }
    }

    /**
     * Gets the status of the application
     * 
     */
    public function getStatus()
    {
        return $this->get("/api/status");
    }

    /**
     * Lists all the channels
     * 
     */
    public function getChannels()
    {
        return $this->get("/api/channels");
    }

    /**
     * Gets the total users in a channel
     * 
     */
    public function getCountUsersInChannel(string $channelName)
    {
        return $this->get("/api/channels/{$channelName}");
    }


    /**
     * Get all users information in a given channel
     * 
     */
    public function getChannelUsers(string $channelName)
    {
        return $this->get("/api/channels/{$channelName}/users");
    }

    public function authWebhook(string $authorization)
    {
        $hash = hash("sha256", "webhook:{$this->publicKey}:{$this->secretKey}");
        return $authorization === $hash;
    }

    private function checkChannelName(string $shouldMatch, $channelName): bool {
        return strpos($channelName, $shouldMatch.'-') === 0;
    }


    /**
     * Checks if the channel is private
     * 
     * @param $channelName string
     */
    public function isChannelPrivate(string $channelName): bool {
        return $this->checkChannelName('private', $channelName);
    }

    /**
     * Checks if the channel is presence
     * 
     * @param $channelName string
     */
    public function isChannelPresence(string $channelName): bool {
        return $this->checkChannelName('presence', $channelName);
    }

    /**
     * Checks if the channel is public
     * 
     * @param $channelName string
     */
    public function isChannelPublic(string $channelName): bool {
        return $this->checkChannelName('public', $channelName);
    }
}
