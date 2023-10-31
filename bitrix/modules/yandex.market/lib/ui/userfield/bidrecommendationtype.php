<?php
/** @noinspection PhpUnused */
namespace Yandex\Market\Ui\UserField;

use Yandex\Market\Reference\Concerns;
use Yandex\Market\Ui;

class BidRecommendationType
{
	use Concerns\HasMessage;

	public static function GetAdminListViewHTML($userField, $htmlControl)
	{
		Ui\Assets::loadPlugin('Ui.BidRecommendation', 'css');

		$value = Helper\ComplexValue::asSingle($userField, $htmlControl);
		$values = $value !== null ? [ $value ] : [];

		return static::renderTree($values);
	}

	public static function GetAdminListViewHTMLMulty($userField, $htmlControl)
	{
		Ui\Assets::loadPlugin('Ui.BidRecommendation', 'css');

		$values = Helper\ComplexValue::asMultiple($userField, $htmlControl);

		return static::renderTree($values);
	}

	protected static function renderTree(array $values)
	{
		if (empty($values)) { return ''; }

		uasort($values, static function($a, $b) {
			if ($a['PERCENT'] === $b['PERCENT']) { return 0; }

			return ($a['PERCENT'] < $b['PERCENT'] ? -1 : 1);
		});

		$maxPercent = max(array_column($values, 'PERCENT'));
		$usedRate = 0;
		$first = true;

		$result = '<div class="b-bid-recommendation">';

		foreach ($values as $value)
		{
			$rate = $value['PERCENT'] / $maxPercent;
			$cellRate = $rate - $usedRate;
			$cellWidth = round($cellRate * 100, 2);
			$activeClass = $value['ACTIVE'] ? 'is--active' : '';

			if ($first)
			{
				$bidText = self::getMessage('FIRST_BID', [ '#BID#' => $value['BID_PERCENT'] ], $value['BID_PERCENT']);
				$viewText = self::getMessage('FIRST_VIEW', [ '#PERCENT#' => $value['PERCENT'] ], $value['PERCENT']);
			}
			else
			{
				$bidText = $value['BID_PERCENT'];
				$viewText = self::getMessage('VIEW', [ '#PERCENT#' => $value['PERCENT'] ], $value['PERCENT']);
			}

			$result .= <<<EOL
				<div class="b-bid-recommendation__cell" style="width: {$cellWidth}%">
					<span class="b-bid-recommendation__bid">{$bidText}</span>
					<span class="b-bid-recommendation__bar {$activeClass}"></span>
					<span class="b-bid-recommendation__percent">{$viewText}</span>
				</div>
EOL;

			$usedRate += $cellRate;
			$first = false;
		}

		$result .= '</div>';

		return $result;
	}
}