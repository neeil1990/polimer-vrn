<?php

namespace Yandex\Market\Trading\Data;

use Yandex\Market;
use Yandex\Market\Reference\Concerns;

class Agent extends Market\Export\Run\Data\Agent
{
    use Concerns\HasMessage;

    public static function getNamespace()
    {
        return Market\Config::getNamespace() . '\\Trading';
    }
}