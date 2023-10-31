<?php

namespace Yandex\Market\Export\Xml\Attribute;

use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class Base extends Market\Export\Xml\Reference\Node
{
	/** @var bool */
	protected $isPrimary;

	protected function refreshParameters()
	{
		parent::refreshParameters();

		$this->isPrimary = !empty($this->parameters['primary']);
	}

	public function isPrimary()
	{
		return $this->isPrimary;
	}

	public function getLangKey()
	{
		$nameLang = str_replace(['.', ' ', '-'], '_', $this->id);
		$nameLang = Market\Data\TextString::toUpper($nameLang);

		return 'EXPORT_ATTRIBUTE_' . $nameLang;
	}

	/**
	 * ��������� �������� xml-��������
	 *
	 * @param                                    $value
	 * @param array                              $context
	 * @param \SimpleXMLElement                  $parent
	 * @param Market\Result\XmlNode|null         $nodeResult
	 * @param array|null                         $settings
	 *
	 * @return null
	 */
	public function exportNode($value, array $context, \SimpleXMLElement $parent, Market\Result\XmlNode $nodeResult = null, $settings = null)
	{
		$attributeName = $this->name;
		$attributeExport = $this->formatValue($value, $context, $nodeResult, $settings);

		@$parent->addAttribute($attributeName, $attributeExport); // sanitize encoding warning (no convert, performance issue)

		return null;
	}

	/**
	 * ������� �������� xml-��������
	 *
	 * @param \SimpleXMLElement      $parent
	 * @param \SimpleXMLElement|null $node
	 */
	public function detachNode(\SimpleXMLElement $parent, \SimpleXMLElement $node = null)
	{
		$attributeName = $this->name;
		$attributes = $parent->attributes();

		if (isset($attributes[$attributeName]))
		{
			unset($attributes[$attributeName]);
		}
	}
}
