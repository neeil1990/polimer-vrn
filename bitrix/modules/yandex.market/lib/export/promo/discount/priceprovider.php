<?php

namespace Yandex\Market\Export\Promo\Discount;

use Bitrix\Main;
use Yandex\Market;

class PriceProvider extends AbstractProvider
{
	use Market\Reference\Concerns\HasLang;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public static function isEnvironmentSupport()
	{
		return Main\ModuleManager::isModuleInstalled('catalog');
	}

	public static function getTitle()
	{
		return static::getLang('EXPORT_PROMO_PROVIDER_CATALOG_PRICE_TITLE');
	}

	public static function getDescription()
	{
		return static::getLang('EXPORT_PROMO_PROVIDER_CATALOG_PRICE_DESCRIPTION');
	}

	public static function getExternalEnum()
	{
		$result = [];
		$context = [
			'HAS_CATALOG' => true,
		];
		$priceSource = Market\Export\Entity\Manager::getSource(
			Market\Export\Entity\Manager::TYPE_CATALOG_PRICE
		);

		foreach ($priceSource->getFields($context) as $field)
		{
			if (
				$field['SELECTABLE']
				&& $field['TYPE'] === Market\Export\Entity\Data::TYPE_NUMBER
				&& preg_match('/^(.*?)\.VALUE$/', $field['ID'], $matches)
			)
			{
				$id = $matches[1];
				$title = $field['VALUE'];
				$titleInnerPosition = Market\Data\TextString::getPosition($title, ':');

				if ($titleInnerPosition !== false)
				{
					$title = Market\Data\TextString::getSubstring($title, 0, $titleInnerPosition);
				}

				if (is_numeric($id))
				{
					$title = sprintf('[%s] %s', $id, $title);
				}

				$result[] = [
					'ID' => $id,
					'VALUE' => $title,
				];
			}
		}

		return $result;
	}

	public static function getPromoUsedFields()
	{
		return [
			'EXTERNAL_ID',
			'START_DATE',
			'FINISH_DATE',
		];
	}

	public static function getPromoFieldsOverrides()
	{
		return [
			'EXTERNAL_ID' => [
				'EDIT_FORM_LABEL' => static::getLang('EXPORT_PROMO_PROVIDER_CATALOG_PRICE_FIELD_EXTERNAL_ID'),
			],
			'START_DATE' => [
				'MANDATORY' => 'Y',
			],
			'FINISH_DATE' => [
				'MANDATORY' => 'Y',
			],
		];
	}

	public static function getSettingsDescription()
	{
		$defaultUserGroups = Market\Data\UserGroup::getDefaults();

		return [
			'USE_DISCOUNT' => [
				'NAME' => static::getLang('EXPORT_PROMO_PROVIDER_CATALOG_PRICE_SETTING_USE_DISCOUNT'),
				'TYPE' => 'boolean',
				'SETTINGS' => [
					'DEFAULT_VALUE' => Market\Export\Promo\Table::BOOLEAN_N,
				],
			],
			'USER_GROUP' => [
				'NAME' => static::getLang('EXPORT_PROMO_PROVIDER_CATALOG_PRICE_SETTING_USER_GROUP'),
				'TYPE' => 'enumeration',
				'VALUES' => Market\Data\UserGroup::getEnum(),
				'SETTINGS' => [
					'DEFAULT_VALUE' => reset($defaultUserGroups),
					'ALLOW_NO_VALUE' => 'N',
					'STYLE' => 'max-width: 220px;',
				]
			],
			'PROCESS_ALL' => [
				'NAME' => static::getLang('EXPORT_PROMO_PROVIDER_CATALOG_PRICE_SETTING_PROCESS_ALL'),
				'HELP_MESSAGE' => static::getLang('EXPORT_PROMO_PROVIDER_CATALOG_PRICE_SETTING_PROCESS_ALL_HELP'),
				'TYPE' => 'boolean',
				'SETTINGS' => [
					'DEFAULT_VALUE' => Market\Export\Promo\Table::BOOLEAN_N,
				],
				'HIDDEN' => Market\Config::isExpertMode() ? 'N' : 'Y',
			]
		];
	}

	public function isActive()
	{
		return true;
	}

	public function getPromoFields()
	{
		return [];
	}

	public function getProductFilterList($context)
	{
		$priceFilter = [];

		if ($this->isSpecificPrice() && !$this->needProcessAll())
		{
			$priceFilter = $this->getNotEmptyPriceFilter();
		}

		return [
			[
				'FILTER' => $priceFilter,
				'DATA' => null,
			],
		];
	}

	protected function isSpecificPrice()
	{
		return is_numeric($this->id);
	}

	protected function getNotEmptyPriceFilter()
	{
		$priceSourceType = $this->getProductSourceType();

		return [
			$priceSourceType => [
				[
					'FIELD' => $this->id . '.VALUE',
					'COMPARE' => '!',
					'VALUE' => false,
				],
			],
		];
	}

	public function getGiftFilterList($context)
	{
		return [];
	}

	public function getProductPriceSelect($context)
	{
		$priceSourceType = $this->getProductSourceType();

		return [
			'PRICE' => [
				'TYPE' => $priceSourceType,
				'FIELD' => $this->id . '.' . ($this->useDiscount() ? 'DISCOUNT_VALUE' : 'VALUE'),
			],
			'CURRENCY' => [
				'TYPE' => $priceSourceType,
				'FIELD' => $this->id . '.CURRENCY',
			],
		];
	}

	public function getProductContext()
	{
		return [
			'PROMO_USER_GROUPS' => $this->getUserGroups(),
			'PROMO_PRICE_SILENT' => true,
		];
	}

	protected function getProductSourceType()
	{
		if ($this->useDiscount() && !$this->isPublicUserGroup())
		{
			$result = Market\Export\Entity\Manager::TYPE_PROMO_PRICE;
		}
		else
		{
			$result = Market\Export\Entity\Manager::TYPE_CATALOG_PRICE;
		}

		return $result;
	}

	public function applyDiscountRules($productId, $price, $currency = null, $filterData = null)
	{
		return $price;
	}

	protected function detectPromoType()
	{
		return Market\Export\Promo\Table::PROMO_TYPE_BONUS_CARD;
	}

	protected function loadFields()
	{
		return [];
	}

	protected function useDiscount()
	{
		return (string)$this->getSetting('USE_DISCOUNT') === Market\Export\Promo\Table::BOOLEAN_Y;
	}

	protected function needProcessAll()
	{
		return (string)$this->getSetting('PROCESS_ALL') === Market\Export\Promo\Table::BOOLEAN_Y;
	}

	protected function isPublicUserGroup()
	{
		$groupId = (int)$this->getSetting('USER_GROUP');

		if ($groupId === 0)
		{
			$result = true;
		}
		else
		{
			$defaults = Market\Data\UserGroup::getDefaults();
			$result = in_array($groupId, $defaults, true);
		}

		return $result;
	}

	protected function getUserGroups()
	{
		$groupId = (int)$this->getSetting('USER_GROUP');

		return Market\Data\UserGroup::extendGroup($groupId);
	}
}