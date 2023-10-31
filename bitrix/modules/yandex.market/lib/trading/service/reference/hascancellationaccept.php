<?php

namespace Yandex\Market\Trading\Service\Reference;

interface HasCancellationAccept
{
	/** @return CancellationAccept */
	public function getCancellationAccept();
}