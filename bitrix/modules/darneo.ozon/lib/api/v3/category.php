<?php

namespace Darneo\Ozon\Api\v3;

use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Darneo\Ozon\Api\Base;
use Darneo\Ozon\Api\Config;

class Category extends Base
{
    public function attribute(int $categoryId, $attributeType = 'ALL'): array
    {
        $uri = new Uri(Config::HOST . '/v3/category/attribute');
        $dataPost = [
            'attribute_type' => $attributeType,
            'category_id' => [$categoryId],
            'language' => Config::LANG,
        ];
        $encode = Json::encode($dataPost);
        $result = $this->httpClient->post($uri->getUri(), $encode);
        $result = Json::decode($result);

        return $result;
    }
}
