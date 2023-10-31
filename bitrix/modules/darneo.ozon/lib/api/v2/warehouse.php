<?php

namespace Darneo\Ozon\Api\v2;

use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Darneo\Ozon\Api\Base;
use Darneo\Ozon\Api\Config;

class Warehouse extends Base
{
    public function list(): array
    {
        $uri = new Uri(Config::HOST . '/v1/warehouse/list');
        $result = $this->httpClient->post($uri->getUri());
        $result = Json::decode($result);

        return $result;
    }
}