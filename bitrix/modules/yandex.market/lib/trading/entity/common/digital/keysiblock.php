<?php

namespace Yandex\Market\Trading\Entity\Common\Digital;

use Bitrix\Main;
use Yandex\Market\Config;
use Yandex\Market\Data\TextString;
use Yandex\Market\Reference\Assert;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Trading\Entity\Reference as TradingReference;
use Yandex\Market\Ui\Admin;
use Yandex\Market\Ui\UserField;
use Yandex\Market\Utils;

/** @noinspection PhpUnused */
class KeysIblock extends Skeleton
{
	use Concerns\HasMessage;

	public function getTitle()
	{
		return self::getMessage('TITLE');
	}

	public function getFields($siteId)
	{
		return
			$this->getSelfFields($siteId)
			+ $this->productData->getFields();
	}

	protected function getSelfFields($siteId)
	{
		$iblockFile = '/bitrix/js/yandex.market/resources/digital/keysiblock/iblock.xml';

		return [
			'IBLOCK' => [
				'TYPE' => 'enumeration',
				'NAME' => self::getMessage('IBLOCK'),
				'HELP_MESSAGE' => self::getMessage('IBLOCK_HELP', [
					'#UPLOAD_URL#' => Admin\Path::getPageUrl('iblock_xml_import', [
						'lang' => LANGUAGE_ID,
						'URL_DATA_FILE' => $iblockFile,
					]),
					'#XML_FILE#' => $iblockFile,
				]),
				'HELP_POSITION' => 'top',
				'VALUES' => UserField\Data\Iblock::getEnum($siteId),
				'MANDATORY' => 'Y',
			],
		];
	}

	protected function requiredModules()
	{
		return [
			'iblock',
		];
	}

	protected function fetchExists(TradingReference\Order $order, array $basketQuantities)
	{
		if ($order->getId() === null) { return []; }

		$result = [];
		$iblockId = $this->setting('IBLOCK');
		$keyProperty = $this->keyPropertyCode();
		$orderProperty = $this->orderPropertyCode();
		$productProperty = $this->productPropertyCode();
		$basketProperty = $this->basketPropertyCode();

		Assert::notNull($iblockId, 'DIGITAL_SETTINGS[IBLOCK]');

		$query = \CIBlockElement::GetList(
			[ 'ID' => 'ASC' ],
			[
				'IBLOCK_ID' => $iblockId,
				'=' . $orderProperty => $order->getId(),
			],
			false,
			[ 'nTopCount' => array_sum($basketQuantities) ],
			[ 'IBLOCK_ID', 'ID', $keyProperty, $orderProperty, $productProperty, $basketProperty ]
		);

		while ($row = $query->Fetch())
		{
			list($key, $orderId, $productId, $basketCode) = $this->propertyValues($row, [
				$keyProperty,
				$orderProperty,
				$productProperty,
				$basketProperty,
			]);

			if ((int)$orderId !== (int)$order->getId()) { continue; }

			$basketCode = $basketCode ?: $this->productBasketCode($productId, $order, $basketQuantities);

			if (!isset($basketQuantities[$basketCode])) { continue; }

			if (!isset($result[$basketCode])) { $result[$basketCode] = []; }

			$result[$basketCode][] = [
				'ID' => $row['ID'],
				'BASKET_CODE' => $basketCode,
				'CODE' => $key,
			];

			if (--$basketQuantities[$basketCode] <= 0)
			{
				unset($basketQuantities[$basketCode]);
			}
		}

		return $result;
	}

	protected function iblockElement(TradingReference\Order $order, $basketCode)
	{
		$basketData = $order->getBasketItemData($basketCode)->getData();
		$productId = (int)$basketData['PRODUCT_ID'];
		$element = \CIBlockElement::GetByID($productId)->Fetch();

		if (!$element)
		{
			throw new Main\SystemException(self::getMessage(
				'ELEMENT_NOT_FOUND',
				$this->basketErrorVariables($order, $basketCode, $basketData)
			));
		}

		return $element;
	}

	protected function makeBasketItemCodes(TradingReference\Order $order, $basketCode, array $iblockElement, $quantity)
	{
		if ($quantity <= 0) { return []; }

		$result = [];
		$iblockId = $this->setting('IBLOCK');
		$keyProperty = $this->keyPropertyCode();
		$orderProperty = $this->orderPropertyCode();
		$productProperty = $this->productPropertyCode();
		$basketProperty = $this->basketPropertyCode();

		Assert::notNull($iblockId, 'DIGITAL_SETTINGS[IBLOCK]');

		$query = \CIBlockElement::GetList(
			[ 'ID' => 'ASC' ],
			[
				'IBLOCK_ID' => $iblockId,
				'ACTIVE' => 'Y',
				'ACTIVE_DATE' => 'Y',
				$orderProperty => false,
				$productProperty => $iblockElement['ID'],
				'!' . $keyProperty => false,
			],
			false,
			[ 'nTopCount' => $quantity ],
			[ 'IBLOCK_ID', 'ID', $keyProperty ]
		);

		while ($row = $query->Fetch())
		{
			$key = $this->propertyValue($row, $keyProperty);

			if (Utils\Value::isEmpty($key))
			{
				throw new Main\SystemException(self::getMessage('KEY_EMPTY', [
					'#ID#' => $row['ID'],
				]));
			}

			$this->updateKey($row['ID'], [
				'ACTIVE' => 'N',
				$orderProperty => $order->getId(),
				$basketProperty => $basketCode,
				$this->statusPropertyCode() => 'RESERVE',
			]);

			$result[] = [
				'ID' => $row['ID'],
				'BASKET_CODE' => $basketCode,
				'CODE' => $key,
			];
		}

		return $result;
	}

	public function fail(TradingReference\Order $order, array $codes)
	{
		foreach ($codes as $code)
		{
			Assert::notNull($code['ID'], 'code[ID]');

			$this->updateKey($code['ID'], [
				$this->statusPropertyCode() => 'FAIL',
			]);
		}
	}

	public function ship(TradingReference\Order $order, array $codes)
	{
		foreach ($codes as $code)
		{
			Assert::notNull($code['ID'], 'code[ID]');

			$this->updateKey($code['ID'], [
				$this->statusPropertyCode() => 'SHIP',
			]);
		}
	}

	protected function updateKey($keyId, array $values)
	{
		$fields = [];
		$properties = [];

		foreach ($values as $name => $value)
		{
			if (TextString::getPosition($name, 'PROPERTY_') === 0)
			{
				$propertyCode = TextString::getSubstring($name, TextString::getLength('PROPERTY_'));

				$properties[$propertyCode] = $value;
			}
			else
			{
				$fields[$name] = $value;
			}
		}

		if (!empty($fields))
		{
			$updateProvider = new \CIBlockElement();
			$updated = $updateProvider->Update($keyId, $fields);

			if (!$updated)
			{
				throw new Main\SystemException(self::getMessage('UPDATE_FAILED', [
					'#ID#' => $keyId,
					'#ERROR#' => $updateProvider->LAST_ERROR,
				]));
			}
		}

		if (!empty($properties))
		{
			\CIBlockElement::SetPropertyValuesEx($keyId, $this->setting('IBLOCK'), $properties);
		}
	}

	protected function keyPropertyCode()
	{
		return $this->propertyCode('key', 'NAME');
	}

	protected function orderPropertyCode()
	{
		return $this->propertyCode('order', 'PROPERTY_ORDER_ID');
	}

	protected function productPropertyCode()
	{
		return $this->propertyCode('product', 'PROPERTY_PRODUCT_ID');
	}

	protected function basketPropertyCode()
	{
		return $this->propertyCode('basket', 'PROPERTY_BASKET_ID');
	}

	protected function statusPropertyCode()
	{
		return $this->propertyCode('basket', 'PROPERTY_STATUS');
	}

	protected function propertyCode($type, $default)
	{
		return Config::getOption('trading_digital_keys_property_' . $type, $default);
	}

	protected function propertyValues(array $element, array $codes)
	{
		$result = [];

		foreach ($codes as $code)
		{
			$result[] = $this->propertyValue($element, $code);
		}

		return $result;
	}

	protected function propertyValue(array $element, $code)
	{
		$valueCode = $code . '_VALUE';

		if (isset($element[$valueCode]))
		{
			$result = $element[$valueCode];
		}
		else if (isset($element[$code]))
		{
			$result = $element[$code];
		}
		else
		{
			$result = null;
		}

		return $result;
	}
}