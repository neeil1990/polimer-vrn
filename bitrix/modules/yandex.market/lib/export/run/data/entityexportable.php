<?php
namespace Yandex\Market\Export\Run\Data;

use Bitrix\Main\Type;

interface EntityExportable
{
	public function isActive();

	public function isActiveDate();

	/** @return Type\DateTime|null */
	public function getNextActiveDate();

	public function getId();

	public function getName();

	public function isExportForAll();
}