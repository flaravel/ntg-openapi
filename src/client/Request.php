<?php

namespace Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;


class Request
{

    private $signPrefix = 'ntgw';

    private $appKey;
    private $appSecret;
    private $appName;

    private $url;
    /**
     * @var string json string
     */
    private $body;
    /**
     * @var array
     */
    private $customOptions = [];
    private $endpoint;

    public function __construct($appKey, $appSecret, $appName, $endpoint)
    {
        if (empty($appKey) || empty($appSecret)) {
            throw new \Exception('invalid parameters');
        }
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $this->appName = strtolower($appName);
        $this->endpoint = ltrim($endpoint, '/');
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function setBody($body)
    {
        $this->body = is_array($body) ? json_encode($body) : $body;
        return $this;
    }

    public function appendOptions(array $options)
    {
        $this->customOptions = array_merge($this->customOptions, $options);
        return $this;
    }

    public function post($url = '', $body = '')
    {
        if ($url) {
            $this->url = $url;
        }
        if ($body) {
            $this->setBody($body);
        }
        return $this->doRequest('POST');
    }

    public function get($url = '')
    {
        if ($url) {
            $this->url = $url;
        }
        return $this->doRequest('GET');
    }

    public function doRequest(string $method)
    {
        if (empty($this->endpoint)) {
            throw new \Exception("endpoint is required");
        }

        $headers = $this->defaultHeader();
        $sign = $this->mkSign($method, $headers);
        // Authorization: ntgw base64_encode(appKey:sign)
        $headers['Authorization'] = $this->buildAuth($sign);

        if (isset($this->customOptions['headers'])) {
            $headers = array_merge($this->customOptions['headers'], $headers);
            unset($this->customOptions['headers']);
        }

        try {
            $response = (new Client([
                'http_errors' => false
            ]))->request($method, $this->url, array_merge($this->customOptions, [
                'headers' => $headers,
                'body'    => $this->body,
            ]));
        } catch (GuzzleException $e) {
            return new Response($e->getMessage(), $e->getCode() ?: 599);
        }

        $contents = (string) $response->getBody()->getContents();
        $response->getBody()->close();
        return new Response($contents, $response->getStatusCode());
    }

    private function defaultHeader(): array
    {
        return [
            'App' => $this->appName,
            'Endpoint' => $this->endpoint,
            'Content-Type' => 'application/json',
            'NTGW-Sign-Nonce' => $this->nonce(),
            'NTGW-Sign-Version' => '1.0',
            'NTGW-Sign-Method' => 'hmac_sha1',
            'NTGW-Sign-Date' => date('r'),
        ];
    }

    private function mkSign($method, array $headers)
    {
        $urlInfo = parse_url($this->url);
        $buf = sprintf("%s\n%s\n%s\n%s\n%s",
            $method,
            $headers['Content-Type'],
            $this->md5Body(),
            $this->serializedHeader($headers),
            $this->endpoint . (isset($urlInfo["query"]) && !empty($urlInfo["query"]) ? "?" . $urlInfo["query"] : '')
        );
        $str = str_replace("\n", "\\n", $buf);

//        print_r("plain str: " . $str)."\n";
        return base64_encode(hash_hmac("sha1", $str, $this->appSecret, true));
    }

    private function nonce($length = 16)
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
    }

    private function serializedHeader(array $headers): string
    {
        $tmp = [];
        foreach ($headers as $key => $value) {
            if (substr($key, 0, 4) != "NTGW") {
                continue;
            }
            $tmp[] = sprintf('%s:%s', strtolower($key), $value);
        }
        return join("\n", $tmp);
    }

    private function buildAuth(string $sign): string
    {
        return sprintf("%s %s", $this->signPrefix, base64_encode(
            sprintf("%s:%s", $this->appKey, $sign)
        ));
    }

    private function md5Body()
    {
        if (empty($this->body)) {
            return "";
        }
        return bin2hex(md5($this->body, true));
    }

}

