<?php

namespace SocketBus;

use Illuminate\Support\ServiceProvider;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;

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
     * @var Curl Contains a curl instance
     */
    private $curl;

    /**
     * @var string Contains the path for the State Base Class
     */
    protected $stateBaseClass;

    /**
     * @var string Contains the state model parser
     */
    protected $stateModelParser;

    /**
     * Constructor store the keys and instanciate Curl
     * @throws \Exception
     */
    public function __construct($options)
    {
        $this->validateOptions($options);

        $this->publicKey = $options['app_id'];
        $this->secretKey = $options['secret'];
        $this->customEncryptionKey = isset($options['custom_encryption_key']) ? $options['custom_encryption_key'] : null;

        $this->stateBaseClass = isset($options['state_base_class']) ? $options['state_base_class'] : null;
        $this->stateModelParser = isset($options['state_model_parser']) ? $options['state_model_parser'] : null;

        $this->curl = new Curl($options);
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
     * Parses state results
     * @return array
     */
    private function parseStateResult(array $response, $result, string $channelName)
    {
        $merge = [];

        if ($this->stateBaseClass && $this->stateModelParser && is_object($result) && $result instanceof $this->stateBaseClass) {
            $_stateModelParser = $this->stateModelParser;
            $stateModelParser = new $_stateModelParser($result);
            $merge['state_data'] = [
                'state' => $this->encrypt($stateModelParser->parse()),
                'rows' => $stateModelParser->getRows()
            ];
        }

        if ($this->customEncryptionKey) {
            $merge['e2e'] = $this->generateE2EPassword($channelName);
        }

        return array_merge($response, $merge);
    }

    /**
     * Generates a auth token and mixes the data for response
     * @return array
     */
    public function auth(string $socketId, string $channelName, $result)
    {
        return $this->parseStateResult([
            'auth' => $this->generateHash($socketId, $channelName)
        ], $result, $channelName);
    }

    /**
     * Authenticates for presence data
     */
    public function authPresence(string $socketId, string $channelName, $userId, $result)
    {
        $encryption = $this->encrypt([
            'user_id' => $userId
        ]);

        return $this->parseStateResult([
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

    private function encrypt(array $data)
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

        $json = json_encode($data);

        return openssl_encrypt($json, "AES-256-ECB", $this->generateE2EPassword($channelName), 0);
    }

    public function broadcast(array $channels, string $eventName, array $data = [])
    {
        foreach($channels as $channel) {
            $this->curl->post("/channels/$channel/broadcast", [
                'event' => $eventName,
                'data' => $this->encryptData($data, $channel)
            ]);
        }
        return true;
    }

    private function request()
    {

    }

}
