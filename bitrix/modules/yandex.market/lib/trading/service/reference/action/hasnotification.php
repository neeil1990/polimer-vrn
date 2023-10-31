<?php

namespace Yandex\Market\Trading\Service\Reference\Action;

interface HasNotification
{
	/** @return AbstractNotification */
	public function getNotification();
}