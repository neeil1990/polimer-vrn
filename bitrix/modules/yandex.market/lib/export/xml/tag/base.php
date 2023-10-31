<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market\Export\Xml;
use Yandex\Market;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class Base extends Xml\Reference\Node
{
	/** @var Xml\Attribute\Base[] */
	protected $attributes;
	/** @var Market\Export\Xml\Attribute\Base|null */
	protected $primaryAttribute;
	/** @var Xml\Tag\Base[] */
	protected $children;
	/** @var bool */
	protected $hasEmptyValue;
	/** @var bool */
	protected $isMultiple;
	/** @var bool */
	protected $isUnion;
	/** @var int|null */
	protected $maxCount;
	/** @var string|null */
	protected $wrapperName;

	protected function refreshParameters()
	{
		parent::refreshParameters();

		$parameters = $this->parameters;

		$this->children = isset($parameters['children']) ? (array)$parameters['children'] : [];
		$this->attributes = isset($parameters['attributes']) ? (array)$parameters['attributes'] : [];
		$this->hasEmptyValue = !empty($this->children) || !empty($parameters['empty_value']);
		$this->isMultiple = !empty($parameters['multiple']);
		$this->isUnion = !empty($parameters['union']);
		$this->maxCount = isset($parameters['max_count']) ? (int)$parameters['max_count'] : null;
		$this->wrapperName = isset($parameters['wrapper_name']) ? (string)$parameters['wrapper_name'] : null;
	}

	public function isUnion()
	{
		return $this->isUnion;
	}

	public function isMultiple()
	{
		return $this->isMultiple;
	}

	public function isSelfClosed()
	{
		return $this->hasEmptyValue && empty($this->children);
	}

	public function getChild($tagName)
	{
		$result = null;

		foreach ($this->children as $child)
		{
			if ($child->getName() === $tagName)
			{
				$result = $child;
				break;
			}
		}

		return $result;
	}

	public function hasChild($tagName)
	{
		return ($this->getChild($tagName) !== null);
	}

	public function hasChildren()
	{
		return !empty($this->children);
	}

	/**
	 * @return Base[]
	 */
	public function getChildren()
	{
		return $this->children;
	}

	public function addChildren(array $tags, $position = null, $after = false)
	{
		if (empty($tags)) { return; }

		$offset = null;

		if (is_numeric($position))
		{
			$offset = (int)$position;
		}
		else if (is_string($position))
		{
			$positionChild = $this->getChild($position);
			$positionOffset = $positionChild !== null
				? array_search($positionChild, $this->children, true)
				: false;

			if ($positionOffset !== false)
			{
				$offset = $positionOffset;
			}
		}

		if ($offset !== null && $after)
		{
			$offset += 1;
		}

		if ($offset !== null)
		{
			array_splice($this->children, $offset, 0, $tags);
		}
		else
		{
			array_push($this->children, ...$tags);
		}

		$this->hasEmptyValue = true;
	}

	public function addChild(Base $tag, $position = null, $after = false)
	{
		$this->addChildren([ $tag ], $position, $after);
	}

	public function removeChild(Base $tag)
	{
		$tagIndex = array_search($tag, $this->children);

		if ($tagIndex !== false)
		{
			array_splice($this->children, $tagIndex, 1);
			$this->hasEmptyValue = !empty($this->children) || !empty($parameters['empty_value']);
		}
	}

	public function getLangKey()
	{
		$nameLang = str_replace(['.', ' ', '-'], '_', $this->id);
		$nameLang = Market\Data\TextString::toUpper($nameLang);

		return 'EXPORT_TAG_' . $nameLang;
	}

	public function tune(array $context)
	{
		$this->tuneSelf($context);
		$this->tuneAttributes($context);
		$this->tuneChildren($context);
	}

	protected function tuneSelf(array $context)
	{
		// nothing by default
	}

	protected function tuneChildren(array $context)
	{
		foreach ($this->children as $child)
		{
			$child->tune($context);
		}
	}

	protected function tuneAttributes(array $context)
	{
		foreach ($this->attributes as $attribute)
		{
			$attribute->tune($context);
		}
	}

	public function getPrimary()
	{
		if ($this->primaryAttribute === null)
		{
			$this->primaryAttribute = $this->resolvePrimary();
		}

		return $this->primaryAttribute;
	}

	protected function resolvePrimary()
	{
		$result = null;

		foreach ($this->attributes as $attribute)
		{
			if ($attribute->isPrimary())
			{
				$result = $attribute;
				break;
			}
		}

		return $result;
	}

	public function getAttribute($attributeName)
	{
		$result = null;

		foreach ($this->attributes as $attribute)
		{
			if ($attribute->getName() === $attributeName)
			{
				$result = $attribute;
				break;
			}
		}

		return $result;
	}

	public function hasAttribute($attributeName)
	{
		return ($this->getAttribute($attributeName) !== null);
	}

	public function hasAttributes()
	{
		return !empty($this->attributes);
	}

	/**
	 * @return Xml\Attribute\Base[]
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	public function addAttribute(Xml\Attribute\Base $attribute, $position = null)
	{
		if ($position !== null)
		{
			array_splice($this->attributes, $position, 0, [ $attribute ]);
		}
		else
		{
			$this->attributes[] = $attribute;
		}
	}

	/**
	 * Имеет собственное значение
	 *
	 * @return bool
	 */
	public function hasEmptyValue()
	{
		return $this->hasEmptyValue;
	}

	/**
	 * Максмимальное количество тегов для множественного поля
	 *
	 * @return int|null
	 */
	public function getMaxCount()
	{
		return $this->maxCount;
	}

	/**
	 * Расширяем описание источников для тега и внутренних тегов
	 *
	 * @param       $tagDescriptionList
	 * @param array $context
	 */
	public function extendTagDescriptionList(&$tagDescriptionList, array $context)
	{
		if (!isset($context['TAG_LEVEL'])) { $context['TAG_LEVEL'] = 0; }

		$foundTags = [];

		// search exists

		foreach ($tagDescriptionList as &$existDescription)
		{
			if ($existDescription['TAG'] === $this->id)
			{
				$existDescription = $this->extendTagDescription($existDescription, $context);

				$foundTags[] = &$existDescription;
			}
		}
		unset($existDescription);

		// create default

		if (empty($foundTags))
		{
			$newDescription = $this->extendTagDescription([], $context);

			if (!empty($newDescription))
			{
				$newDescription['TAG'] = $this->id;

				$foundTags[] = &$newDescription;
				$tagDescriptionList[] = &$newDescription;
			}
		}

		// apply

		foreach ($foundTags as &$foundDescription)
		{
			$foundDescription = $this->extendChildrenDescription($tagDescriptionList, $foundDescription, $context);
		}
		unset($foundDescription);
	}

	/**
	 * Расширяем описание источников для тега и аттрибутов
	 *
	 * @param       $tagDescription
	 * @param array $context
	 *
	 * @return mixed
	 */
	public function extendTagDescription($tagDescription, array $context)
	{
		$result = $tagDescription;

		if (empty($result['VALUE']) || $this->isDefined())
		{
			$definedSource = $this->getDefinedSource($context);

			if ($definedSource !== null)
			{
				$result['VALUE'] = $definedSource;
			}
		}

		foreach ($this->getAttributes() as $attribute)
		{
			$attributeId = $attribute->getId();

			if (empty($result['ATTRIBUTES'][$attributeId]) || $attribute->isDefined())
			{
				$definedSource = $attribute->getDefinedSource($context);

				if ($definedSource !== null)
				{
					if (!isset($result['ATTRIBUTES']))
					{
						$result['ATTRIBUTES'] = [];
					}

					$result['ATTRIBUTES'][$attributeId] = $definedSource;
				}
			}
		}

		return $result;
	}

	/**
	 * Расширяем описание источников для дочерних тегов
	 *
	 * @param array $tagDescriptionList
	 * @param array $tagDescription
	 * @param array $context
	 *
	 * @return array
	 */
	protected function extendChildrenDescription(&$tagDescriptionList, $tagDescription, array $context)
	{
		if ($context['TAG_LEVEL'] > 0)
		{
			if (!isset($tagDescription['CHILDREN']) || !is_array($tagDescription['CHILDREN']))
			{
				$tagDescription['CHILDREN'] = [];
			}

			$childrenValues = &$tagDescription['CHILDREN'];
			$childrenContext = $context;

			if (!isset($childrenContext['TAG_CHAIN'])) { $childrenContext['TAG_CHAIN'] = []; }

			$childrenContext['TAG_CHAIN'][] = $tagDescriptionList;
		}
		else
		{
			$childrenValues = &$tagDescriptionList;
			$childrenContext = $context;
		}

		++$childrenContext['TAG_LEVEL'];

		foreach ($this->getChildren() as $child)
		{
			$child->extendTagDescriptionList($childrenValues, $childrenContext);
		}

		return $tagDescription;
	}

	/**
	 * Описание дополнительных настроек для тега
	 *
	 * @param array $context
	 *
	 * @return array|null
	 */
	public function getSettingsDescription(array $context = [])
	{
		return null;
	}

	public function exportDocument()
	{
		$encoding = Market\Utils\Encoding::getCharset();

		return new \SimpleXMLElement('<?xml version="1.0" encoding="' . $encoding . '"?><root />', LIBXML_COMPACT);
	}

	/**
	 * Выгрузка тега вместе с дочерними
	 *
	 * @param $tagValuesList
	 * @param $context
	 * @param $parent
	 *
	 * @return Market\Result\XmlNode
	 */
	public function exportTag($tagValuesList, $context, \SimpleXMLElement $parent = null)
	{
		if ($parent === null) { $parent = $this->exportDocument(); }

		$tagValue = $this->getTagValues($tagValuesList, $this->id, false);

		return $this->exportTagValue($tagValue, $tagValuesList, $context, $parent);
	}

	/**
	 * @param $tagValuesList array
	 * @param $context array
	 * @param \SimpleXMLElement $parent
	 * @return Market\Result\XmlNode
	 */
	public function exportTagSingle($tagValuesList, $context, \SimpleXMLElement $parent)
	{
		$tagValue = $this->getTagValues($tagValuesList, $this->id);

		return $this->exportTagValue($tagValue, $tagValuesList, $context, $parent);
	}

	/**
	 * @param $tagValuesList array
	 * @param $context array
	 * @param \SimpleXMLElement $parent
	 * @return Market\Result\XmlNode[]
	 */
	public function exportTagMultiple($tagValuesList, $context, \SimpleXMLElement $parent)
	{
		$result = [];
		$maxCount = $this->getMaxCount();
		$tagCount = 0;
		$tagValues = $this->getTagValues($tagValuesList, $this->id, true);

		if (empty($tagValues)) { $tagValues[] = []; } // try export defaults

		foreach ($tagValues as $tagValue)
		{
			$exportResult = $this->exportTagValue($tagValue, $tagValuesList, $context, $parent);
			$result[] = $exportResult;

			if ($exportResult->isSuccess())
			{
				++$tagCount;

				if ($maxCount !== null && $tagCount >= $maxCount)
				{
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * @param $tagValuesList array
	 * @param $context array
	 * @param \SimpleXMLElement $parent
	 * @return Market\Result\XmlNode[]
	 */
	public function exportTagUnion($tagValuesList, $context, \SimpleXMLElement $parent)
	{
		$result = [];
		$maxCount = $this->getMaxCount();
		$tagCount = 0;
		$tagValues = $this->getTagValues($tagValuesList, $this->id, true);
		$unionTag = null;

		if (empty($tagValues)) { $tagValues[] = []; } // try export defaults

		foreach ($tagValues as $tagValue)
		{
			$exportResult = $this->exportTagValue($tagValue, $tagValuesList, $context, $parent, $unionTag);

			if ($exportResult->isSuccess())
			{
				++$tagCount;

				if ($unionTag === null)
				{
					$unionTag = $exportResult->getXmlElement();
				}

				if ($maxCount !== null && $tagCount >= $maxCount)
				{
					break;
				}
			}

			$result[] = $exportResult;
		}

		return $result;
	}

	/**
	 * Выгружаем значение тега (запускает выгрузку дочерних тегов и аттрибутов)
	 *
	 * @param array                		$tagValue
	 * @param array                		$tagValuesList
	 * @param array               		$context
	 * @param \SimpleXMLElement 		$parent
	 * @param \SimpleXMLElement|null 	$unionTag
	 *
	 * @return \Yandex\Market\Result\XmlNode
	 */
	protected function exportTagValue($tagValue, $tagValuesList, $context, \SimpleXMLElement $parent, \SimpleXMLElement $unionTag = null)
	{
		$result = new Market\Result\XmlNode();
		$isValid = true;
		$value = null;
		$settings = isset($tagValue['SETTINGS']) ? $tagValue['SETTINGS'] : null;

		$context['TAG_LEVEL'] = isset($context['TAG_LEVEL']) ? $context['TAG_LEVEL'] + 1 : 0;

		if (!$this->hasEmptyValue)
		{
			$result->setErrorTagName($this->id);
			$value = isset($tagValue['VALUE']) && $tagValue['VALUE'] !== '' ? $tagValue['VALUE'] : $this->getDefaultValue($context, $tagValuesList);

			$isValid = $this->validate($value, $context, $tagValuesList, $result, $settings);
		}

		if ($isValid)
		{
			if ($unionTag !== null)
			{
				$node = $this->appendNode($value, $context, $unionTag, $result, $settings);
			}
			else
			{
				$node = $this->exportNode($value, $context, $parent, $result, $settings);
			}

			$attributes = isset($tagValue['ATTRIBUTES']) ? $tagValue['ATTRIBUTES'] : [];
			$children = $context['TAG_LEVEL'] > 0 && $this->getParameter('tree') === true ? (array)$tagValue['CHILDREN'] : $tagValuesList;

			$hasAttributes = $this->exportTagAttributes($attributes, $context, $node, $result, $settings);
			$hasChildren = $this->exportTagChildren($children, $context, $node, $result);

			if ($this->hasEmptyValue && !$hasChildren && !$hasAttributes)
			{
				if ($this->isRequired && !$result->hasErrors())
				{
					$result->setErrorTagName($this->id);
					$result->registerError(
						Market\Config::getLang('XML_NODE_TAG_EMPTY'),
						Market\Error\XmlNode::XML_NODE_TAG_EMPTY
					);
				}
				else
				{
					$result->invalidate();
				}
			}

			if (($hasChildren || $hasAttributes) && $this->getParameter('critical') === true)
			{
				foreach ($result->getErrors() as $error)
				{
					$error->markCritical();
				}
			}

			if ($result->isSuccess())
			{
				$result->setXmlElement($node);
			}
			else
			{
				$this->detachNode($parent, $node);
			}
		}

		return $result;
	}

	/**
	 * Выгружаем аттрибуты тега
	 *
	 * @param                               $values
	 * @param array                         $context
	 * @param \SimpleXMLElement             $parent
	 * @param Market\Result\XmlNode         $tagResult
	 * @param array|null                    $settings
	 *
	 * @return bool
	 */
	protected function exportTagAttributes($values, array $context, \SimpleXMLElement $parent, Market\Result\XmlNode $tagResult, $settings = null)
	{
		$result = false;

		foreach ($this->getAttributes() as $attribute)
		{
			$id = $attribute->getId();
			$value = isset($values[$id]) && $values[$id] !== '' ? $values[$id] : $attribute->getDefaultValue($context, $values);
			$isRequired = $attribute->isRequired();

			$tagResult->setErrorStrict($isRequired);
			$tagResult->setErrorTagName($this->id);
			$tagResult->setErrorAttributeName($id);

			if ($attribute->validate($value, $context, $values, $tagResult, $settings))
			{
				$result = true;
				$attribute->exportNode($value, $context, $parent, $tagResult, $settings);
			}
		}

		$tagResult->setErrorStrict(true);
		$tagResult->setErrorAttributeName(null);

		return $result;
	}

	/**
	 * Выгружаем дочерние теги
	 *
	 * @param                           $tagValuesList
	 * @param                           $context
	 * @param \SimpleXMLElement         $parent
	 * @param Market\Result\XmlNode     $tagResult
	 *
	 * @return bool
	 */
	protected function exportTagChildren($tagValuesList, $context, \SimpleXMLElement $parent, Market\Result\XmlNode $tagResult)
	{
		$result = false;

		foreach ($this->getChildren() as $child)
		{
			$isError = $child->isRequired(); // error for parent if children required

			if ($child->isUnion())
			{
				$childResultList = $child->exportTagUnion($tagValuesList, $context, $parent);
			}
			else if ($child->isMultiple())
			{
				$childResultList = $child->exportTagMultiple($tagValuesList, $context, $parent);
			}
			else
			{
				$childResult = $child->exportTagSingle($tagValuesList, $context, $parent);
				$childResultList = [ $childResult ];
			}

			foreach ($childResultList as $childResult)
			{
				if ($childResult->isSuccess())
				{
					$result = true;
					$isError = false;
					break;
				}
			}

			$this->copyResultList($childResultList, $tagResult, $isError);
		}

		return $result;
	}

	/**
	 * Добавляем дочерний тег для xml-элемента
	 *
	 * @param                                   $value
	 * @param array                             $context
	 * @param \SimpleXMLElement                 $parent
	 * @param Market\Result\XmlNode|null        $nodeResult
	 * @param array|null                        $settings
	 *
	 * @return \SimpleXMLElement
	 */
	public function exportNode($value, array $context, \SimpleXMLElement $parent, Market\Result\XmlNode $nodeResult = null, $settings = null)
	{
		$tagName = $this->name;

		if ($this->wrapperName !== null)
		{
			if (isset($parent->{$this->wrapperName}))
			{
				$parent = $parent->{$this->wrapperName};
			}
			else
			{
				$parent = $parent->addChild($this->wrapperName);
			}
		}

		if ($this->hasEmptyValue)
		{
			$result = $parent->addChild($tagName);
		}
		else
		{
			$valueExport = $this->formatValue($value, $context, $nodeResult, $settings);

			$result = $parent->addChild($tagName, $valueExport);
		}

		return $result;
	}

	/**
	 * Добавляем значение тега для xml-элемента
	 *
	 * @param                                   $value
	 * @param array                             $context
	 * @param \SimpleXMLElement                 $node
	 * @param Market\Result\XmlNode|null        $nodeResult
	 * @param array|null                        $settings
	 *
	 * @return \SimpleXMLElement
	 */
	public function appendNode($value, array $context, \SimpleXMLElement $node, Market\Result\XmlNode $nodeResult = null, $settings = null)
	{
		$result = $node;

		if (!$this->hasEmptyValue)
		{
			$valueExport = (string)$this->formatValue($value, $context, $nodeResult, $settings);
			$existValue = (string)$result[0];

			if ($valueExport === '')
			{
				// nothing
			}
			else if ($existValue !== '')
			{
				$glue = $this->getParameter('glue');

				if ($glue === null)
				{
					$glue = ', ';
				}

				$result[0] = $existValue . $glue . $valueExport;
			}
			else
			{
				$result[0] = $valueExport;
			}
		}

		return $result;
	}

	/**
	 * Удаляем дочерний тег xml-элемента
	 *
	 * @param \SimpleXMLElement      $parent
	 * @param \SimpleXMLElement|null $node
	 */
	public function detachNode(\SimpleXMLElement $parent, \SimpleXMLElement $node = null)
	{
		if ($node !== null)
		{
			unset($node[0]);

			if ($this->wrapperName !== null && isset($parent->{$this->wrapperName}))
			{
				$wrapperTag = $parent->{$this->wrapperName};

				if (count($wrapperTag->children()) === 0)
				{
					unset($wrapperTag[0]);
				}
			}
		}
	}

	/**
	 * Копируем результат
	 *
	 * @param \Yandex\Market\Result\XmlNode[]   $fromList
	 * @param \Yandex\Market\Result\XmlNode     $to
	 * @param bool                              $isError
	 */
	protected function copyResultList(array $fromList, Market\Result\XmlNode $to, $isError)
	{
		$foundErrorMessages = [];
		$foundWarningMessages = [];

		foreach ($fromList as $from)
		{
			// copy errors

			foreach ($from->getErrors() as $error)
			{
				$errorUniqueKey = $error->getUniqueKey();

				if ($isError || $error->isCritical())
				{
					if (!isset($foundErrorMessages[$errorUniqueKey]))
					{
						$foundErrorMessages[$errorUniqueKey] = true;

						$to->addError($error);
					}
				}
				else if (
					!isset($foundWarningMessages[$errorUniqueKey])
					&& $error->getCode() !== Market\Error\XmlNode::XML_NODE_VALIDATE_EMPTY
				)
				{
					$foundWarningMessages[$errorUniqueKey] = true;

					$to->addWarning($error);
				}
			}

			// copy warnings

			foreach ($from->getWarnings() as $warning)
			{
				$warningUniqueKey = $warning->getUniqueKey();

				if (!isset($foundWarningMessages[$warningUniqueKey]))
				{
					$foundWarningMessages[$warningUniqueKey] = true;

					$to->addWarning($warning);
				}
			}

			// copy replaces

			if (!$isError && $from->isSuccess())
			{
				foreach ($from->getReplaces() as $index => $replace)
				{
					$to->addReplace($replace, $index);
				}

				if ($from->hasPlain())
				{
					$to->registerPlain();
				}
			}
		}

		if ($isError && empty($foundErrorMessages))
		{
			$to->invalidate();
		}
	}

	/**
	 * Получаем значение тега (вспомогательный метод)
	 *
	 * @param      $tagValuesList
	 * @param      $tagId
	 * @param bool $isMultiple
	 *
	 * @return mixed
	 */
	protected function getTagValues($tagValuesList, $tagId, $isMultiple = false)
	{
		$result = null;

		if (isset($tagValuesList[$tagId]))
		{
			$tagValues = $tagValuesList[$tagId];
			$isSingleValue = array_key_exists('VALUE', $tagValues);

			if ($isMultiple)
			{
				$result = $isSingleValue ? [ $tagValues ] : $tagValues;
			}
			else
			{
				$result = $isSingleValue ? $tagValues : reset($tagValues);
			}
		}
		else if ($isMultiple)
		{
			$result = [];
		}

		return $result;
	}
}
