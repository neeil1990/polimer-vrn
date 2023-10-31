<?php

namespace Yandex\Market\Export\Xml\Tag\Concerns;

use Yandex\Market;

/**
 * @property array $parameters
 */
trait HasPackUnit
{
	protected $ratioNode;

	protected function validateRatio(array $context, Market\Result\XmlNode $nodeResult = null, $settings = null)
	{
		if (!isset($settings['PACK_RATIO']) || Market\Utils\Value::isEmpty($settings['PACK_RATIO'])) { return true; }

		$numberType = Market\Type\Manager::getType(Market\Type\Manager::TYPE_NUMBER);

		if ($nodeResult !== null)
		{
			/** @var Market\Result\XmlNode $packResult */
			$resultPool = Market\Result\Pool::getInstance(Market\Result\XmlNode::class);
			$packNode = $this->getRatioNode();
			$packResult = $resultPool->get();

			$result = $numberType->validate($settings['PACK_RATIO'], $context, $packNode, $packResult);

			if (!$packResult->isSuccess())
			{
				$nodeResult->registerError(self::getMessage('ERROR_PACK_RATIO', [
					'#MESSAGE#' => Market\Data\TextString::lcfirst(implode(', ', $packResult->getErrorMessages())),
				]));
			}

			$resultPool->release($packResult);
		}
		else
		{
			$result = $numberType->validate($settings['PACK_RATIO'], $context);
		}

		return $result;
	}

	protected function resolveValueRatio($settings)
	{
		if (isset($settings['PACK_RATIO']) && is_scalar($settings['PACK_RATIO']) && (string)$settings['PACK_RATIO'] !== '')
		{
			$numberType = Market\Type\Manager::getType(Market\Type\Manager::TYPE_NUMBER);
			$packNode = $this->getRatioNode();
			$value = (float)$numberType->format($settings['PACK_RATIO'], [], $packNode);

			if ($this->isPackRatioInverted())
			{
				$this->parameters['value_ratio'] = $value !== 0.0 ? (1 / $value) : null;
			}
			else
			{
				$this->parameters['value_ratio'] = $value;
			}
		}
		else
		{
			$this->parameters['value_ratio'] = null;
		}
	}

	protected function isPackRatioInverted()
	{
		return false;
	}

	protected function getRatioNode()
	{
		if ($this->ratioNode === null)
		{
			$this->ratioNode = new Market\Export\Xml\Tag\Base([
				'name' => 'dummy',
				'value_precision' => 4,
			]);
		}

		return $this->ratioNode;
	}
}