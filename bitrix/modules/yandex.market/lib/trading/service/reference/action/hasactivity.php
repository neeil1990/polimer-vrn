<?php

namespace Yandex\Market\Trading\Service\Reference\Action;

interface HasActivity
{
	/** @return AbstractActivity */
	public function getActivity();
}