<?php
namespace Yandex\Market\Export\Entity\Iblock\Property\Feature\Set;

interface Set
{
	public function key();

	public function title();

	public function deprecated();

	public function properties();
}