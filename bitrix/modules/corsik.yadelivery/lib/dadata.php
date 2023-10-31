<?php

namespace Corsik\YaDelivery;

use \Bitrix\Main\Config\Option;

class Dadata
{
    private $module_id = 'corsik.yadelivery';

    public function __construct()
    {
        $this->apiKey = Option::get($this->module_id, 'api_key_dadata');
        $this->url = "https://suggestions.dadata.ru/suggestions/api/4_1/rs";
    }

    public function clean($type, $data)
    {
        $requestData = [
            "query" => $data
        ];
        return $this->executeRequest("$this->url/suggest/$type", $requestData);
    }

    public function iplocate($ip = null)
    {
        return $this->Get("$this->url/iplocate/address", ['ip' => $ip]);
    }

    public function Get($url, array $get = null, array $options = [])
    {
        $defaults = [
            CURLOPT_URL => $url . (strpos($url, "?") === false ? "?" : "") . http_build_query($get),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                "Content-Type: application/json",
                'Authorization: Token ' . $this->apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_DNS_USE_GLOBAL_CACHE => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ];

        $ch = curl_init();
        curl_setopt_array($ch, ($options + $defaults));

        if (!$result = curl_exec($ch)) {
            trigger_error(curl_error($ch));
        }

        curl_close($ch);
        return json_decode($result, true);
    }

    function Post($url, $get = null, array $post = null, array $options = [])
    {
        $defaults = [
            CURLOPT_POST => 1,
            CURLOPT_URL => $url . (strpos($url, "?") === false ? "?" : "") . http_build_query($get),
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                "Content-Type: application/json",
                'Authorization: Token ' . $this->apiKey,
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ];
        $ch = curl_init();
        curl_setopt_array($ch, ($options + $defaults));
        if (!$result = curl_exec($ch)) {
            trigger_error(curl_error($ch));
        }
        curl_close($ch);
        return json_decode($result, true);
    }

}