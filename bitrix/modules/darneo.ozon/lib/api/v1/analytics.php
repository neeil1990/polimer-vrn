<?php

namespace Darneo\Ozon\Api\v1;

use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Darneo\Ozon\Api\Base;
use Darneo\Ozon\Api\Config;

class Analytics extends Base
{
    public function data(string $dateFrom, string $dateTo, array $dimension, array $metrics, $offset = 0)
    {
        $uri = new Uri(Config::HOST . '/v1/analytics/data');
        $dataPost = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'dimension' => $dimension,
            'metrics' => $metrics,
            'offset' => $offset,
            'limit' => 1000,
        ];
        $encode = Json::encode($dataPost);
        $result = $this->httpClient->post($uri->getUri(), $encode);
        $result = Json::decode($result);

        return $result;
    }
}