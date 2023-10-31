<?php

namespace Darneo\Ozon\Api\v3;

use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Darneo\Ozon\Api\Base;
use Darneo\Ozon\Api\Config;

class Product extends Base
{
    public function infoStocks(array $productIds, string $lastId = '', $visibility = 'ALL'): array
    {
        $uri = new Uri(Config::HOST . '/v3/product/info/stocks');
        $dataPost = [
            'filter' => [
                'visibility' => $visibility,
                'product_id' => $productIds,
            ],
            'last_id' => $lastId,
            'limit' => 1000,
        ];
        $encode = Json::encode($dataPost);
        $result = $this->httpClient->post($uri->getUri(), $encode);
        $result = Json::decode($result);

        return $result;
    }
}
