<?php

namespace Darneo\Ozon\Api\v4;

use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Darneo\Ozon\Api\Base;
use Darneo\Ozon\Api\Config;

class Product extends Base
{
    public function infoLimit(): array
    {
        $uri = new Uri(Config::HOST . '/v4/product/info/limit');
        $result = $this->httpClient->post($uri->getUri());
        $result = Json::decode($result);

        return $result;
    }
}
