<?php

namespace Darneo\Ozon\Api;

use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Web\HttpClient;
use Darneo\Ozon\Main\Helper\Settings as HelperSettings;
use Darneo\Ozon\Main\Table\ClientKeyTable;

class Base
{
    protected HttpClient $httpClient;

    public function __construct()
    {
        $keyId = HelperSettings::getKeyIdCurrent();
        $dataKey = $this->getDataKey($keyId);

        $config = Configuration::getValue('http_client_options');
        $this->httpClient = new HttpClient($config);
        $this->httpClient->setHeader('Content-Type', 'application/json');
        $this->httpClient->setHeader('Client-Id', $dataKey['CLIENT_ID']);
        $this->httpClient->setHeader('Api-Key', $dataKey['KEY']);

        $cookie = $this->httpClient->getCookies()->toArray();
        $this->httpClient->setCookies($cookie);
    }

    protected function getDataKey(int $keyId): array
    {
        $rows = [];
        $parameters = [
            'filter' => ['ID' => $keyId],
            'select' => ['CLIENT_ID', 'KEY'],
            'order' => ['ID' => 'DESC']
        ];
        $result = ClientKeyTable::getList($parameters);
        if ($row = $result->fetch()) {
            $rows = $row;
        }

        return $rows;
    }

    protected function isTest(): bool
    {
        return HelperSettings::isTest();
    }
}
