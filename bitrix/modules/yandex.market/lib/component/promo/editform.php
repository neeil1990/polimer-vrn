<?php

namespace Yandex\Market\Component\Promo;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Component;

Main\Localization\Loc::loadMessages(__FILE__);

class EditForm extends Market\Component\Model\EditForm
{
	protected $exportLink;
	protected $productFilter;

	public function __construct(\CBitrixComponent $component)
	{
		parent::__construct($component);

		$this->exportLink = new Component\Molecules\ExportLink();
		$this->productFilter = new Component\Molecules\ProductFilter([
			'PROMO_PRODUCT',
			'PROMO_GIFT',
		]);
	}

	public function modifyRequest($request, $fields)
	{
		$result = parent::modifyRequest($request, $fields);
		$result = $this->exportLink->sanitize($result);
		$result = $this->productFilter->sanitizeIblock($result, $fields, $this->exportLink->usedIblockIds($result), [
			'PROMO_GIFT' => 'PROMO_GIFT_IBLOCK_ID',
		]);
		$result = $this->productFilter->sanitizeFilter($result, $fields);
		$result = $this->modifyRequestUnsetDiscount($result);

		return $result;
	}

	protected function modifyRequestUnsetDiscount($request)
	{
		$result = $request;

		if (isset($request['PROMO_TYPE']))
		{
			$allRuleFields = $this->getAllDiscountRuleFields();
			$typeRuleFields = $this->getTypeDiscountRuleFields($request['PROMO_TYPE']);
			$typeRuleFieldsMap = array_flip($typeRuleFields);

			foreach ($allRuleFields as $ruleField)
			{
				if (!isset($typeRuleFieldsMap[$ruleField]) && isset($request[$ruleField]))
				{
					$result[$ruleField] = null;
				}
			}
		}

		return $result;
	}

	public function validate($data, array $fields = null)
	{
	    $tableData = $data;

		if (array_key_exists('PROMO_GIFT_IBLOCK_ID', $tableData))
		{
			unset($tableData['PROMO_GIFT_IBLOCK_ID']);
		}

		$result = parent::validate($tableData, $fields);

		$this->validateUrlRequired($result, $data, $fields);
		$this->validateDiscountRequired($result, $data, $fields);
		$this->exportLink->validate($result, $data, $fields);
		$this->productFilter->validate($result, $data, $fields);

		return $result;
	}

	protected function validateUrlRequired(Main\Entity\Result $result, $data, $fields)
	{
		if (
			isset($fields['URL'], $data['PROMO_TYPE'])
			&& $data['PROMO_TYPE'] === Market\Export\Promo\Table::PROMO_TYPE_BONUS_CARD
			&& trim($data['URL']) === ''
		)
		{
			$result->addError(new Main\Entity\EntityError(
				Main\Localization\Loc::getMessage('MAIN_ENTITY_FIELD_REQUIRED', [ '#FIELD#' => $fields['URL']['LIST_COLUMN_LABEL'] ]),
				Main\Entity\FieldError::EMPTY_REQUIRED
			));
		}
	}

	protected function validateDiscountRequired(Main\Entity\Result $result, $data, $fields)
	{
		if ($fields !== null && $result->isSuccess())
        {
            foreach ($fields as $fieldName => $field)
            {
                if ($field['MANDATORY'] === 'Y')
                {
                    $hasFieldValue = false;

                    if (!isset($data[$fieldName]))
                    {
                        // nothing
                    }
                    else if (is_scalar($data[$fieldName]))
                    {
                        $hasFieldValue = (trim($data[$fieldName]) !== '');
                    }
                    else
                    {
                        $hasFieldValue = !empty($data[$fieldName]);
                    }

                    if (!$hasFieldValue)
                    {
                        $result->addError(new Main\Entity\EntityError(
                            Main\Localization\Loc::getMessage('MAIN_ENTITY_FIELD_REQUIRED', [ '#FIELD#' => $field['LIST_COLUMN_LABEL'] ]),
                            Main\Entity\FieldError::EMPTY_REQUIRED
                        ));
                    }
                }
            }
        }
	}

	public function add($fields)
	{
		if (array_key_exists('PROMO_GIFT_IBLOCK_ID', $fields))
		{
			unset($fields['PROMO_GIFT_IBLOCK_ID']);
		}

		return parent::add($fields);
	}

	public function update($primary, $fields)
	{
		if (array_key_exists('PROMO_GIFT_IBLOCK_ID', $fields))
		{
			unset($fields['PROMO_GIFT_IBLOCK_ID']);
		}

		return parent::update($primary, $fields);
	}

	public function getFields(array $select = [], $item = null)
	{
		global $USER_FIELD_MANAGER;

		$result = parent::getFields($select, $item);
		$promoType = (isset($item['PROMO_TYPE']) ? $item['PROMO_TYPE'] : null);

		if (in_array('PROMO_GIFT_IBLOCK_ID', $select) && !isset($result['PROMO_GIFT_IBLOCK_ID']))
		{
			$result['PROMO_GIFT_IBLOCK_ID'] = [
				'FIELD_NAME' => 'PROMO_GIFT_IBLOCK_ID',
				'EDIT_IN_LIST' => 'Y',
				'LIST_COLUMN_LABEL' => Market\Config::getLang('COMPONENT_PROMO_EDIT_FORM_FIELD_PROMO_GIFT_IBLOCK_ID'),
				'USER_TYPE' => $USER_FIELD_MANAGER->GetUserType('enumeration'),
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N'
			];

			$result['PROMO_GIFT_IBLOCK_ID']['USER_TYPE']['CLASS_NAME'] = 'Yandex\Market\Ui\UserField\IblockType';
 		}

		// deprecated promo types

		if (isset($result['PROMO_TYPE']))
		{
			$result['PROMO_TYPE'] = $this->deprecatePromoTypes($result['PROMO_TYPE'], $promoType);
		}

		// settings

		if (isset($result['EXTERNAL_ID']))
		{
			$result += $this->getTypeDiscountSettings($promoType);
		}

		// unset discount rule fields by promo type

		$allRuleFields = $this->getAllDiscountRuleFields();
		$typeRuleFields = $this->getTypeDiscountRuleFields($promoType);
		$typeRuleFieldsMap = array_flip($typeRuleFields);

		foreach ($allRuleFields as $ruleField)
		{
			if (
				!isset($typeRuleFieldsMap[$ruleField])
				&& isset($result[$ruleField])
			)
			{
				unset($result[$ruleField]);
			}
		}

		// overrides

		$typeOverrides = $this->getTypeDiscountOverrides($promoType);

		foreach ($typeOverrides as $fieldName => $fieldOverrides)
		{
			if (isset($result[$fieldName]))
			{
				$result[$fieldName] = array_merge(
					$result[$fieldName],
					$fieldOverrides
				);
			}
		}

		return $result;
	}

	protected function deprecatePromoTypes(array $field, $selected)
	{
		$deprecated = array_flip([
			'catalog_discount',
			'catalog_price',
			Market\Export\Promo\Table::PROMO_TYPE_FLASH_DISCOUNT,
			Market\Export\Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE,
			Market\Export\Promo\Table::PROMO_TYPE_GIFT_N_PLUS_M,
			Market\Export\Promo\Table::PROMO_TYPE_BONUS_CARD,
		]);

		foreach ($field['VALUES'] as $optionKey => $option)
		{
			if ($option['ID'] !== $selected && isset($deprecated[$option['ID']]))
			{
				unset($field['VALUES'][$optionKey]);
			}
		}

		return $field;
	}

	public function extend($data, array $select = [])
	{
        if (!isset($data['PROMO_GIFT_IBLOCK_ID']) && !empty($data['PROMO_GIFT']))
        {
            foreach ($data['PROMO_GIFT'] as $promoGift)
            {
                if (isset($promoGift['IBLOCK_ID']))
                {
	                $data['PROMO_GIFT_IBLOCK_ID'] = $promoGift['IBLOCK_ID'];
                    break;
                }
            }
        }

		$data = $this->productFilter->extend($data);

		return $data;
	}

	protected function getAllDiscountRuleFields()
	{
		return [
			'EXTERNAL_ID',
			'START_DATE',
			'FINISH_DATE',
			'DISCOUNT_UNIT',
			'DISCOUNT_CURRENCY',
			'DISCOUNT_VALUE',
			'PROMO_CODE',
			'GIFT_REQUIRED_QUANTITY',
			'GIFT_FREE_QUANTITY',
			'PROMO_PRODUCT',
            //'PROMO_GIFT_IBLOCK_ID',
			'PROMO_GIFT'
		];
	}

	protected function getTypeDiscountRuleFields($promoType)
	{
		switch ($promoType)
		{
			case Market\Export\Promo\Table::PROMO_TYPE_FLASH_DISCOUNT:
			case Market\Export\Promo\Table::PROMO_TYPE_BONUS_CARD:
				$result = [
					'START_DATE',
					'FINISH_DATE',
					'DISCOUNT_UNIT',
					'DISCOUNT_CURRENCY',
					'DISCOUNT_VALUE',
					'PROMO_PRODUCT'
				];
			break;

			case Market\Export\Promo\Table::PROMO_TYPE_PROMO_CODE:
				$result = [
					'START_DATE',
					'FINISH_DATE',
					'PROMO_CODE',
					'DISCOUNT_UNIT',
					'DISCOUNT_CURRENCY',
					'DISCOUNT_VALUE',
					'PROMO_PRODUCT'
				];
			break;

			case Market\Export\Promo\Table::PROMO_TYPE_GIFT_N_PLUS_M:
				$result = [
					'START_DATE',
					'FINISH_DATE',
					'PROMO_PRODUCT',
					'GIFT_REQUIRED_QUANTITY',
					'GIFT_FREE_QUANTITY',
				];
			break;

			case Market\Export\Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE:
				$result = [
					'START_DATE',
					'FINISH_DATE',
					'GIFT_REQUIRED_QUANTITY',
					'PROMO_PRODUCT',
                    'PROMO_GIFT_IBLOCK_ID',
					'PROMO_GIFT'
				];
			break;

			default:
				$result = $this->getPromoProviderUsedFields($promoType);
			break;
		}

		return $result;
	}

	protected function getPromoProviderUsedFields($promoType)
	{
		if (!$this->hasTypePromoProvider($promoType)) { return []; }

		$providerClassName = Market\Export\Promo\Discount\Manager::getProviderTypeClassName($promoType);

		return (array)$providerClassName::getPromoUsedFields();
	}

	protected function getTypeDiscountOverrides($promoType)
	{
		switch ($promoType)
		{
			case Market\Export\Promo\Table::PROMO_TYPE_FLASH_DISCOUNT:
			case Market\Export\Promo\Table::PROMO_TYPE_BONUS_CARD:
				$result = [
					'START_DATE' => [ 'MANDATORY' => 'Y' ],
					'FINISH_DATE' => [ 'MANDATORY' => 'Y' ],
					'DISCOUNT_UNIT' => [ 'MANDATORY' => 'Y' ],
					'DISCOUNT_VALUE' => [ 'MANDATORY' => 'Y' ],
					'PROMO_PRODUCT' => [ 'MANDATORY' => 'Y' ],
				];
			break;

			case Market\Export\Promo\Table::PROMO_TYPE_PROMO_CODE:
				$result = [
					'PROMO_CODE' => [ 'MANDATORY' => 'Y' ],
					'DISCOUNT_UNIT' => [ 'MANDATORY' => 'Y' ],
					'DISCOUNT_VALUE' => [ 'MANDATORY' => 'Y' ],
					'PROMO_PRODUCT' => [ 'MANDATORY' => 'Y' ],
				];
			break;

			case Market\Export\Promo\Table::PROMO_TYPE_GIFT_N_PLUS_M:
				$result = [
					'GIFT_REQUIRED_QUANTITY' => [ 'MANDATORY' => 'Y' ],
					'GIFT_FREE_QUANTITY' => [ 'MANDATORY' => 'Y' ],
					'PROMO_PRODUCT' => [ 'MANDATORY' => 'Y' ],
				];
			break;

			case Market\Export\Promo\Table::PROMO_TYPE_GIFT_WITH_PURCHASE:
				$result = [
					'PROMO_PRODUCT' => [ 'MANDATORY' => 'Y' ],
                    'PROMO_GIFT_IBLOCK_ID' => [ 'MANDATORY' => 'Y' ],
					'PROMO_GIFT' => [ 'MANDATORY' => 'Y' ],
				];
			break;

			default:
				$result = $this->getPromoProviderOverrides($promoType);
			break;
		}

		return $result;
	}

	protected function getPromoProviderOverrides($promoType)
	{
		if (!$this->hasTypePromoProvider($promoType)) { return []; }

		$providerClassName = Market\Export\Promo\Discount\Manager::getProviderTypeClassName($promoType);

		// common

		$result = (array)$providerClassName::getPromoFieldsOverrides();

		// external id

		$providerEnum = $providerClassName::getExternalEnum();

		$result['EXTERNAL_ID']['NOTE'] = $providerClassName::getDescription();

		if ($providerEnum !== null)
		{
			$result['EXTERNAL_ID']['MANDATORY'] = 'Y';
			$result['EXTERNAL_ID']['USER_TYPE'] = Market\Ui\UserField\Manager::getUserType('enumeration');
			$result['EXTERNAL_ID']['VALUES'] = $providerEnum;
		}

		return $result;
	}

	protected function getTypeDiscountSettings($promoType)
	{
		if (!$this->hasTypePromoProvider($promoType)) { return []; }

		$providerClassName = Market\Export\Promo\Discount\Manager::getProviderTypeClassName($promoType);
		$result = [];

		foreach ($providerClassName::getSettingsDescription() as $settingName => $setting)
		{
			$fieldName = 'EXTERNAL_SETTINGS[' . $settingName . ']';

			$setting += [
				'MULTIPLE' => 'N',
				'EDIT_IN_LIST' => 'Y',
				'EDIT_FORM_LABEL' => $setting['NAME'],
				'FIELD_NAME' => $fieldName,
				'SETTINGS' => [],
			];

			if (!isset($setting['USER_TYPE']) && isset($setting['TYPE']))
			{
				$setting['USER_TYPE'] = Market\Ui\UserField\Manager::getUserType($setting['TYPE']);
			}

			$result[$fieldName] = $setting;
		}

		return $result;
	}

	protected function hasTypePromoProvider($promoType)
	{
		return (
			(string)$promoType !== ''
			&& !Market\Export\Promo\Discount\Manager::isInternalType($promoType)
		);
	}
}