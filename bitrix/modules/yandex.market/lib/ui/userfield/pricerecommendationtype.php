<?php
/** @noinspection PhpUnused */
namespace Yandex\Market\Ui\UserField;

use Yandex\Market\Data;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Trading;
use Yandex\Market\Ui;

class PriceRecommendationType
{
	use Concerns\HasMessage;

	protected static $campaigns = [];

	public static function GetAdminListViewHTML($userField, $htmlControl)
	{
		Ui\Assets::loadPlugin('Ui.BidRecommendation', 'css');

		$value = Helper\ComplexValue::asSingle($userField, $htmlControl);
		$values = $value !== null ? [ $value ] : [];

		return static::render($values);
	}

	public static function GetAdminListViewHTMLMulty($userField, $htmlControl)
	{
		Ui\Assets::loadPlugin('Ui.BidRecommendation', 'css');

		$values = Helper\ComplexValue::asMultiple($userField, $htmlControl);

		return static::render($values);
	}

	protected static function render(array $values)
	{
		if (empty($values)) { return ''; }

		$usedCampaigns = array_column($values, 'CAMPAIGN_ID');
		$campaigns = static::campaigns($usedCampaigns);
		$result = '';

		foreach ($values as $value)
		{
			if (isset($campaigns[$value['CAMPAIGN_ID']]))
			{
				$campaign = $campaigns[$value['CAMPAIGN_ID']];

				$campaignText = sprintf(
					'<a href="%s">[%s] %s</a>',
					$campaign['URL'],
					$campaign['CAMPAIGN_ID'],
					$campaign['NAME']
				);
			}
			else
			{
				$campaignText = sprintf('[%s]', $value['CAMPAIGN_ID']);
			}

			$result .= sprintf(
				'<div>%s %s %s</div>',
				Data\Currency::format($value['PRICE'], 'RUR'),
				self::getMessage('PRICE_FOR'),
				$campaignText
			);
		}

		return $result;
	}

	protected static function campaigns(array $ids)
	{
		$idsMap = array_flip($ids);
		$result = array_intersect_key(static::$campaigns, $idsMap);
		$need = array_diff_key($idsMap, static::$campaigns);

		if (!empty($need))
		{
			$result += static::loadCampaigns(array_keys($need));
			static::$campaigns += $result;
		}

		return $result;
	}

	protected static function loadCampaigns(array $ids)
	{
		if (empty($ids)) { return []; }

		$result = array_fill_keys($ids, null);

		$query = Trading\Setup\Table::getList([
			'filter' => [
				'=SETTINGS.NAME' => 'CAMPAIGN_ID',
				'=SETTINGS.VALUE' => $ids,
			],
			'select' => [
				'CAMPAIGN_ID' => 'SETTINGS.VALUE',
				'ID',
				'NAME',
			],
		]);

		while ($row = $query->fetch())
		{
			$result[$row['CAMPAIGN_ID']] = [
				'ID' => $row['ID'],
				'NAME' => $row['NAME'],
				'CAMPAIGN_ID' => $row['CAMPAIGN_ID'],
				'URL' => Ui\Admin\Path::getModuleUrl('trading_edit', [
					'id' => $row['ID'],
				]),
			];
		}

		return $result;
	}
}