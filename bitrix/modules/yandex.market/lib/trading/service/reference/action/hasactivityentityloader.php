<?php

namespace Yandex\Market\Trading\Service\Reference\Action;

interface HasActivityEntityLoader
{
	public function loadEntity($primary);
}