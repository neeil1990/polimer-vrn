<?php

namespace Darneo\Ozon\Fields\Value;

interface ValueFromDbInterface extends ValueInterface
{
    public function getRaw();

    public function setValueFromDb($row, array $selectFields = []);
}
