<?php

namespace Darneo\Ozon\Main\Helper;

use Bitrix\Main\Web\Json;

class Hash
{
    public static function generate(array $data): string
    {
        $json = Json::encode($data);
        return md5($json);
    }
}
