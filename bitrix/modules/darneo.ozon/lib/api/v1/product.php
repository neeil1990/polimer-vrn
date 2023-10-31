<?php

namespace Darneo\Ozon\Api\v1;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Darneo\Ozon\Api\Base;
use Darneo\Ozon\Api\Config;

class Product extends Base
{
    public function importPrices(array $data)
    {
        if ($this->isTest()) {
            $result = [Loc::getMessage('DARNEO_OZON_API_IS_TEST_MESSAGE')];
        } else {
            $uri = new Uri(Config::HOST . '/v1/product/import/prices');
            $dataPost = [
                'prices' => $data
            ];
            $encode = Json::encode($dataPost);
            $result = $this->httpClient->post($uri->getUri(), $encode);
            $result = Json::decode($result);
        }

        return $result;
    }

    public function importInfo(int $taskId): array
    {
        $uri = new Uri(Config::HOST . '/v1/product/import/info');
        $dataPost = [
            'task_id' => $taskId
        ];
        $encode = Json::encode($dataPost);
        $result = $this->httpClient->post($uri->getUri(), $encode);
        $result = Json::decode($result);

        return $result;
    }
}
