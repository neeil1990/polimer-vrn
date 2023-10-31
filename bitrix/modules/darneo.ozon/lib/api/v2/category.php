<?php

namespace Darneo\Ozon\Api\v2;

use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Darneo\Ozon\Api\Base;
use Darneo\Ozon\Api\Config;

class Category extends Base
{
    public function tree(int $categoryId = null): array
    {
        $uri = new Uri(Config::HOST . '/v2/category/tree');
        $dataPost = [
            'language' => Config::LANG,
        ];

        if ($categoryId) {
            $dataPost['category_id'] = $categoryId;
        }

        $encode = Json::encode($dataPost);
        $result = $this->httpClient->post($uri->getUri(), $encode);
        $result = Json::decode($result);

        return $result;
    }

    public function attributeValues(int $attributeId, int $categoryId, int $lastValue = 0): array
    {
        $uri = new Uri(Config::HOST . '/v2/category/attribute/values');
        $dataPost = [
            'attribute_id' => $attributeId,
            'category_id' => $categoryId,
            'language' => Config::LANG,
            'last_value_id' => $lastValue,
            'limit' => 1000,
        ];
        $encode = Json::encode($dataPost);
        $result = $this->httpClient->post($uri->getUri(), $encode);
        $result = Json::decode($result);

        return $result;
    }
}
