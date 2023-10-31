<?php

namespace Yandex\Market\Export\Entity\Reference;

interface HasSectionValues
{
	public function getSectionListValues($sectionList, $select, $context);

	public function getSectionFields(array $context = []);
}