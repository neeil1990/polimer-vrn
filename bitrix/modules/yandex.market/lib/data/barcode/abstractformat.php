<?php

namespace Yandex\Market\Data\Barcode;

abstract class AbstractFormat
{
	abstract public function getImage($text, $size = 20, $factor = 1);
}