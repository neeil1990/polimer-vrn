<?php

namespace Yandex\Market\Exceptions\Api;

use Bitrix\Main;

if (class_exists(Main\SystemException::class)) { /* bitrix16 hasnt InvalidOperationException in autoload */ }

class InvalidOperation extends Main\InvalidOperationException
{

}