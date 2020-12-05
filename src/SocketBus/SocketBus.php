<?php

namespace ValterLorran\SocketBus;

use Illuminate\Support\ServiceProvider;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;

class SocketBus
{
    private $publicKey;
    private $secretKey;

    private $curl;

    protected $stateBaseClass;
    protected $stateModelParser;

    public function __construct($options)
    {
        $this->validateOptions($options);

        $this->publicKey = $options['app_id'];
        $this->secretKey = $options['secret'];

        $this->stateBaseClass = isset($options['state_base_class']) ? $options['state_base_class'] : null;
        $this->stateModelParser = isset($options['state_model_parser']) ? $options['state_model_parser'] : null;

        $this->curl = new Curl($options);
    }

    private function validateOptions($options)
    {
        $must_have = ['app_id', 'secret'];

        foreach($must_have as $m) {
            if (!isset($options[$m])) {
                throw new \Exception("Setting {$m} is missing!");
            }
        }
    }

    private function parseStateResult(array $response, $result)
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

        return array_merge($response, $merge);
    }

    public function auth(string $socketId, string $channelName, $result)
    {
        return $this->parseStateResult([
            'auth' => $this->generateHash($socketId, $channelName)
        ], $result);
    }

    public function authPresence(string $socketId, string $channelName, $userId, $result)
    {
        $encryption = $this->encrypt([
            'user_id' => $userId
        ]);

        return $this->parseStateResult([
            'auth' => $this->generateHash($socketId, $channelName),
            'data' => $encryption,
            'presence' => true
        ], $result);
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

    public function broadcast(array $channels, string $eventName, array $data = [])
    {
        foreach($channels as $channel) {
            $this->curl->post("/channels/$channel/broadcast", [
                'event' => $eventName,
                'data' => $data
            ]);
        }
        return true;
    }

    private function request()
    {

    }

}
