<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Yandex\Market\Ui;
use Yandex\Market\Ui\UserField\Helper\Attributes;
use Yandex\Market\Data\TextString;

foreach ($buttons as $button)
{
	$buttonName = isset($button['NAME']) ? $button['NAME'] : null;
	$buttonAttributes = isset($button['ATTRIBUTES']) ? $button['ATTRIBUTES'] : [];
	$buttonAttributes += [
		'class' => 'adm-btn',
		'type' => 'button',
	];

	if (isset($button['PLUGINS']))
	{
		Ui\Assets::loadPlugins($button['PLUGINS']);

		$buttonAttributes['class'] .= ' js-plugin-click';
		$buttonAttributes['data-plugin'] = implode(',', $button['PLUGINS']);
	}

	if (isset($button['OPTIONS']))
	{
		$isValid = true;

		foreach ($button['OPTIONS'] as $name => $value)
		{
			if (is_string($value) && TextString::getPosition($value, '#') !== false)
			{
				$replaces = [
					'ID' => $arParams['PRIMARY'],
				];

				foreach ($replaces as $replaceName => $replaceValue)
				{
					$replaceAnchor = '#' . $replaceName . '#';

					if (!empty($replaceValue))
					{
						$value = str_replace($replaceAnchor, $replaceValue, $value);
					}
					else if (TextString::getPosition($value, $replaceAnchor) !== false)
					{
						$isValid = false;
						break;
					}
				}
			}

			if (!$isValid) { break; }

			$buttonAttributes['data-' . $name] = $value;
		}

		if (!$isValid) { continue; }
	}

	$buttonAttributesString = Attributes::stringify($buttonAttributes);

	?>
	<button <?= $buttonAttributesString; ?>><?= $buttonName; ?></button>
	<?php
}
