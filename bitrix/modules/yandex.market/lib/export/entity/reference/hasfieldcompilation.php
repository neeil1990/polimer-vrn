<?php

namespace Yandex\Market\Export\Entity\Reference;

interface HasFieldCompilation
{
	public function compileField($field);

	public function parseField($compiledField);
}
