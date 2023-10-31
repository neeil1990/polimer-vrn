<?php

namespace Yandex\Market\Export\Entity\Template;

use Bitrix\Main;
use Bitrix\Iblock;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class Source extends Market\Export\Entity\Reference\Source
{
	protected $templateCache = [];

	public function getOrder()
	{
		return 1000; // last
	}

	public function initializeQueryContext($select, &$queryContext, &$sourceSelect)
	{
		if (Market\Template\Engine::load())
		{
			$templateToKeyMap = [];

			foreach ($select as $templateKey => $template)
			{
				$templateNode = Market\Template\Engine::compileTemplate($template);
				$templateSourceSelect = $templateNode->getSourceSelect();

				foreach ($templateSourceSelect as $sourceType => $sourceFields)
				{
					if (!isset($sourceSelect[$sourceType]))
					{
						$sourceSelect[$sourceType] = $sourceFields;
					}
					else
					{
						$sourceSelect[$sourceType] = array_merge(
							$sourceSelect[$sourceType],
							$sourceFields
						);
					}
				}

				$this->setTemplateCache($templateKey, $templateNode);

				$templateToKeyMap[$template] = $templateKey;
			}

			if (!isset($queryContext['SELECT_MAP'])) { $queryContext['SELECT_MAP'] = []; }

			$queryContext['SELECT_MAP'][$this->getType()] = $templateToKeyMap;
		}
	}

	public function releaseQueryContext($select, $queryContext, $sourceSelect)
	{
		$this->releaseTemplateCache();
	}

	public function getElementListValues($elementList, $parentList, $selectFields, $queryContext, $sourceValues)
	{
		$result = [];

		if (Market\Template\Engine::load())
		{
			foreach ($elementList as $elementId => $element)
			{
				$entity = new Market\Template\Entity\SourceValue($elementId);
				$result[$elementId] = [];

				if (isset($sourceValues[$elementId]))
				{
					$entity->setFields($sourceValues[$elementId]);
				}

				foreach ($selectFields as $templateKey => $template)
				{
					/** @var Market\Template\Node\Root $templateNode */
					$templateNode = $this->getTemplateCache($templateKey);
					$templateResult = '';

					if ($templateNode)
					{
						$templateResult = $templateNode->processValue($entity);
					}

					$result[$elementId][$templateKey] = $templateResult;
				}
			}
		}

		return $result;
	}

	public function getFields(array $context = [])
	{
		return null;
	}

	public function getControl()
	{
		return Market\Export\Entity\Manager::CONTROL_TEMPLATE;
	}

	protected function getLangPrefix()
	{
		return 'TEMPLATE_';
	}

	protected function setTemplateCache($template, Market\Template\Node\Root $node)
	{
		$this->templateCache[$template] = $node;
	}

	protected function getTemplateCache($template)
	{
		return (isset($this->templateCache[$template]) ? $this->templateCache[$template] : null);
	}

	protected function releaseTemplateCache()
	{
		$this->templateCache = [];
	}
}