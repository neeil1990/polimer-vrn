<?php

namespace Darneo\Ozon\Api\v2;

use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Darneo\Ozon\Api\Base;
use Darneo\Ozon\Api\Config;

class Posting extends Base
{
    public function fboList(int $limit, int $offset = 0, array $filter = []): array
    {
        $uri = new Uri(Config::HOST . '/v2/posting/fbo/list');
        $dataPost = [
            'dir' => 'desc',
            'limit' => $limit,
            'offset' => $offset,
            'translit' => false,
            'with' => [
                'analytics_data' => true,
                'financial_data' => true,
            ],
        ];

        if ($filter) {
            $dataPost['filter'] = $filter;
        }

        $encode = Json::encode($dataPost);
        $result = $this->httpClient->post($uri->getUri(), $encode);
        $result = Json::decode($result);

        return $result;
    }
}
