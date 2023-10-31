<?php

namespace Yandex\Market\Trading\Service\Reference;

interface HasItemsChangeReason
{
	/** @return ItemsChangeReason */
	public function getItemsChangeReason();
}