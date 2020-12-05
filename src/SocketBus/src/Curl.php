<?php

namespace ValterLorran\SocketBus;

class Curl {
    private $domain;
    private $publicKey;
    private $secretKey;
    private $customDomain;

    public function __construct($options)
    {
        $this->customDomain = isset($options['customDomain']) ? $options['customDomain'] : null;
        $this->buildDomain();
        $this->publicKey = $options['app_id'];
        $this->secretKey = $options['secret'];
    }

    private function buildDomain()
    {
        if (!$this->customDomain) {
            $this->domain = "https://app.socketbus.com/api/";
        } else {
            $this->domain = "{$this->customDomain}/api/";
        }
    }

    private function buildUrl(string $path)
    {
        if (!$path) {
            throw new \Exception("\$path must not be empty");
        }

        if ($path[0] == '/') {
            $path = substr($path, 1);
        }

        return $this->domain . $path;
    }

    private function buildResponse($response)
    {
        return [
            'status' => true,
            'response' => $response,
            'code' => 200
        ];
    }

    private function buildError(string $error, $code)
    {
        return [
            'status' => false,
            'error' => $error,
            'code' => $code
        ];
    }

    public function post(string $path, array $data)
    {
        $curl = curl_init($this->buildUrl($path));
        $payload = json_encode($data);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);

        $authorization = hash("sha256", "{$this->publicKey}:{$this->secretKey}");

        $authorization = "{$this->publicKey}:$authorization";

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload),
            'Authorization: ' . $authorization
        ));

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            dd(curl_error($curl));
        }
        curl_close($curl);

        return $this->buildResponse($response);
    }

    public function get(string $path, array $parameters = [])
    {
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->buildUrl($path) . (count($parameters) ? "?". http_build_query($parameters) : '')
        ]);

        $authorization = hash("sha256", "{$this->publicKey}:{$this->secretKey}");

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Authorization: ' . $authorization
        ));
        
        $response = curl_exec($curl);

        if (!$response) {
            return $this->buildError(curl_error($curl), curl_errno($curl));
        }

        curl_close($curl);

        return $this->buildResponse($response);
    }

    public function stateEvent(string $event, string $model, $id, array $data = [])
    {
        return $this->post("/states/{$event}/{$model}/{$id}", $data);
    }
}