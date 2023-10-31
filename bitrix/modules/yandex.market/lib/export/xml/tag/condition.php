<?php

namespace Yandex\Market\Export\Xml\Tag;

class Condition extends Base
{
	public function getDefaultParameters()
	{
		return [
			'name' => 'condition',
		];
	}

	public function extendTagDescription($tagDescription, array $context)
	{
		if (!empty($tagDescription['VALUE']) && empty($tagDescription['CHILDREN'])) // move reason to child tag
		{
			$tagDescription['CHILDREN'] = [
				[
					'TAG' => 'reason',
					'VALUE' => $tagDescription['VALUE'],
				],
			];

			$tagDescription['VALUE'] = null;
		}

		return parent::extendTagDescription($tagDescription, $context);
	}
}