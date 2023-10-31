<?php

namespace Yandex\Market\Trading\Service\Reference;

interface HasCancelReason
{
	/** @return CancelReason */
	public function getCancelReason();
}