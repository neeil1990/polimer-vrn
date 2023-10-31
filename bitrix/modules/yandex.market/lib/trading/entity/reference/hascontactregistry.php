<?php

namespace Yandex\Market\Trading\Entity\Reference;

interface HasContactRegistry
{
	/** @return ContactRegistry */
	public function getContactRegistry();
}