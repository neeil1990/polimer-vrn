<?php

namespace Yandex\Market\Component\Promo;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class EditForm extends Market\Component\Model\EditForm
{
	public function modifyRequest($request, $fields)
	{
		$result = parent::modifyRequest($request, $fields);
		$result = $this->modifyRequestSetup($result, $fields);
		$result = $this->modifyRequestProductIblock($result, $fields);
		$result = $this->modifyRequestProductFilter($result, $fields);
		$result = $this->modifyRequestUnsetDiscount($result, $fields);

		return $result;
	}

	protected function modifyRequestSetup($request, $fields)
	{
		$result = $request;
		$hasSetupRequest = isset($request['SETUP']);
		$hasSetupLinkRequest = isset($request['SETUP_LINK']);

		if ($hasSetupRequest || $hasSetupLinkRequest)
		{
			$ids = $hasSetupRequest ? (array)$request['SETUP'] : [];
			$idsMap = array_flip($ids);
			$usedIds = [];
			$result['SETUP_LINK'] = $hasSetupLinkRequest ? (array)$request['SETUP_LINK'] : [];

			foreach ($result['SETUP_LINK'] as $setupLinkKey => $setupLink)
			{
				$setupId = (int)$setupLink['SETUP_ID'];

				if ($setupId > 0 && isset($idsMap[$setupId]))
				{
					$usedIds[$setupId] = true;
				}
				else
				{
					unset($result['SETUP_LINK'][$setupLinkKey]);
				}
			}

			foreach ($ids as $id)
			{
				if ($id > 0 && !isset($usedIds[$id]))
				{
					$result['SETUP_LINK'][] = [
						'SETUP_ID' => $id
					];
				}
			}
		}

		return $result;
	}

	protected function modifyRequestProductIblock($request, $fields)
	{
		$result = $request;
		$productFieldKeys = [ 'PROMO_PRODUCT', 'PROMO_GIFT' ];
		$setupIblockList = null;

		foreach ($productFieldKeys as $productFieldKey)
		{
			if (isset($fields[$productFieldKey]))
			{
				$iblockIdList = [];

				if ($setupIblockList === null) { $setupIblockList = $this->getUsedIblockList($request); }

				if ($productFieldKey === 'PROMO_GIFT')
				{
					$giftIblockId = isset($result['PROMO_GIFT_IBLOCK_ID']) ? (int)$result['PROMO_GIFT_IBLOCK_ID'] : null;

					if ($giftIblockId === null || $giftIblockId <= 0)
					{
						$giftIblockId = (int)reset($setupIblockList);
					}

					if ($giftIblockId > 0)
					{
						$iblockIdList = [ $giftIblockId ];
					}
				}
				else
				{
					$iblockIdList = $setupIblockList;
				}

				$iblockIdMap = array_flip($iblockIdList);
				$usedIblockMap = [];
				$result[$productFieldKey] = isset($request[$productFieldKey]) ? (array)$request[$productFieldKey] : [];

				foreach ($result[$productFieldKey] as $promoProductKey => $promoProduct)
				{
					$iblockId = isset($promoProduct['IBLOCK_ID']) ? (int)$promoProduct['IBLOCK_ID'] : null;

					if ($iblockId > 0 && isset($iblockIdMap[$iblockId]))
					{
						$usedIblockMap[$iblockId] = true;
					}
					else
					{
						unset($result[$productFieldKey][$promoProductKey]);
					}
				}

				foreach ($iblockIdList as $iblockId)
				{
					if ($iblockId > 0 && !isset($usedIblockMap[$iblockId]))
					{
						$result[$productFieldKey][] = [
							'IBLOCK_ID' => $iblockId
						];
					}
				}
			}
		}

		return $result;
	}

	protected function modifyRequestProductFilter($request, $fields)
	{
		$result = $request;
		$productFieldKeys = [ 'PROMO_PRODUCT', 'PROMO_GIFT' ];

		foreach ($productFieldKeys as $productFieldKey)
		{
			if (
				isset($fields[$productFieldKey], $result[$productFieldKey])
				&& is_array($result[$productFieldKey])
			)
			{
				foreach ($result[$productFieldKey] as &$productData)
				{
					if (!isset($productData['FILTER']))
					{
						$productData['FILTER'] = [];
					}
				}
				unset($productData);
			}
		}

		return $result;
	}

	protected function modifyRequestUnsetDiscount($request, $fields)
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
		$this->validateSetupSelected($result, $data, $fields);
		$this->validateProductFilter($result, $data, $fields);

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

	protected function validateSetupSelected(Main\Entity\Result $result, $data, $fields)
	{
		$hasEmptySetupLink = false;
		$hasEmptySetupExportAll = false;

		if (isset($fields['SETUP']))
		{
			if (empty($data['SETUP']))
			{
				$hasEmptySetupLink = true;
			}
			else
			{
				Main\Type\Collection::normalizeArrayValuesByInt($data['SETUP']);

				$hasEmptySetupLink = (count($data['SETUP']) === 0);
			}
		}

		if (isset($fields['SETUP_EXPORT_ALL']))
		{
			$hasEmptySetupExportAll = (
				empty($data['SETUP_EXPORT_ALL'])
				|| (string)$data['SETUP_EXPORT_ALL'] === Market\Export\Promo\Table::BOOLEAN_N
			);
		}

		if ($hasEmptySetupLink && $hasEmptySetupExportAll)
		{
			$result->addError(new Main\Entity\EntityError(
				Market\Config::getLang('COMPONENT_PROMO_EDIT_FORM_FIELD_SETUP_LINK_EMPTY'),
				Main\Entity\FieldError::EMPTY_REQUIRED
			));
		}
	}

	protected function validateProductFilter(Main\Entity\Result $result, $data, $fields)
	{
		$productFieldKeys = [ 'PROMO_PRODUCT', 'PROMO_GIFT' ];

		foreach ($productFieldKeys as $productFieldKey)
		{
			if (isset($fields[$productFieldKey]))
			{
				$field = $fields[$productFieldKey];
				$hasProductFilter = false;

				if (!empty($data[$productFieldKey]))
				{
					foreach ($data[$productFieldKey] as $iblockLinkIndex => $promoProduct)
					{
						if (!empty($promoProduct['FILTER']))
						{
							foreach ($promoProduct['FILTER'] as $filterIndex => $filter)
							{
								$hasValidCondition = false;
								$hasProductFilter = true;

								if (!empty($filter['FILTER_CONDITION']))
								{
									foreach ($filter['FILTER_CONDITION'] as $filterCondition)
									{
										if (Market\Export\FilterCondition\Table::isValidData($filterCondition))
										{
											$hasValidCondition = true;
											break;
										}
									}
								}

								if (!$hasValidCondition)
								{
									$result->addError(new Market\Error\EntityError(
										Market\Config::getLang('COMPONENT_PROMO_EDIT_FORM_ERROR_FILTER_CONDITION_EMPTY', [
											'#FIELD_NAME#' => $field['LIST_COLUMN_LABEL']
										])
									));
									break 2;
								}
							}
						}
					}
				}

				if (!$hasProductFilter)
				{
					$result->addError(new Market\Error\EntityError(
						Market\Config::getLang('COMPONENT_PROMO_EDIT_FORM_ERROR_PRODUCT_EXPORT_EMPTY', [
							'#FIELD_NAME#' => $field['LIST_COLUMN_LABEL']
						])
					));
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

	public function extend($data, array $select = [])
	{
		$result = $data;
		$productFieldKeys = [ 'PROMO_PRODUCT', 'PROMO_GIFT' ];

        if (!isset($data['PROMO_GIFT_IBLOCK_ID']) && !empty($data['PROMO_GIFT']))
        {
            foreach ($data['PROMO_GIFT'] as $promoGift)
            {
                if (isset($promoGift['IBLOCK_ID']))
                {
                    $result['PROMO_GIFT_IBLOCK_ID'] = $promoGift['IBLOCK_ID'];
                    break;
                }
            }
        }

		foreach ($productFieldKeys as $productFieldKey)
		{
			if (!empty($result[$productFieldKey]))
			{
				foreach ($result[$productFieldKey] as &$promoProduct)
				{
					$promoProduct['CONTEXT'] = Market\Export\Entity\Iblock\Provider::getContext($promoProduct['IBLOCK_ID']);
				}
				unset($promoProduct);
			}
		}

		return $result;
	}

	protected function getUsedIblockList($data)
	{
		$iblockIdMap = [];

		$querySetupIblockLinkList = Market\Export\IblockLink\Table::getList([
			'filter' => $this->getSetupFilter($data, 'SETUP'),
			'select' => [
				'IBLOCK_ID'
			]
		]);

		while ($setupIblockLink = $querySetupIblockLinkList->fetch())
		{
			$iblockId = (int)$setupIblockLink['IBLOCK_ID'];

			if ($iblockId > 0 && !isset($iblockIdMap[$iblockId]))
			{
				$iblockIdMap[$iblockId] = true;
			}
		}

		return array_keys($iblockIdMap);
	}

	protected function getSetupFilter($data, $reference = null)
	{
		$result = [];

		if (isset($data['SETUP_EXPORT_ALL']) && $data['SETUP_EXPORT_ALL'] === Market\Export\Promo\Table::BOOLEAN_Y)
		{
			// nothing
		}
		else
		{
			$setupIds = [];

			if (!empty($data['SETUP']))
			{
				foreach ($data['SETUP'] as $setupId)
				{
					$setupId = (int)$setupId;

					if ($setupId > 0)
					{
						$setupIds[] = $setupId;
					}
				}
			}

			if (empty($setupIds))
			{
				$setupIds[] = -1;
			}

			$result['=' . ($reference !== null ? $reference . '.' : '') . 'ID'] = $setupIds;
		}

		return $result;
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
		$result = null;

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