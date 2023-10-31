<?php

namespace Yandex\Market\Type;

use Yandex\Market;

class DatePeriodType extends DateType
{
	/** @var Market\Type\PeriodType*/
	protected $periodType;
	/** @var Market\Result\XmlNode */
	protected $nodeResultProxy;

	public function __construct()
	{
		$this->periodType = Manager::getType(Manager::TYPE_PERIOD);
	}

	public function validate($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$proxyResult = $this->makeNodeResultProxy($nodeResult);
		$isMatch = $this->periodType->validate($value, $context, $node, $proxyResult);

		if (!$isMatch)
		{
			$isMatch = parent::validate($value, $context, $node, $proxyResult);
		}

		if (!$isMatch)
		{
			$this->copyNodeResultProxy($proxyResult, $nodeResult);
		}

		return $isMatch;
	}

	public function format($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$proxyResult = $this->makeNodeResultProxy($nodeResult);
		$result = $this->periodType->format($value, $context, $node, $proxyResult);

		if ($result === '')
		{
			$proxyResult = $this->makeNodeResultProxy($nodeResult);

			$result = parent::format($value, $context, $node, $proxyResult);
		}

		$this->copyNodeResultProxy($proxyResult, $nodeResult);

		return $result;
	}

	/** @deprecated */
	protected function isPeriod($value)
	{
		return $this->periodType->isPrepared($value);
	}

	protected function makeNodeResultProxy(Market\Result\XmlNode $nodeResult = null)
	{
		if ($nodeResult === null) { return null; }

		if ($this->nodeResultProxy === null || !$this->nodeResultProxy->isSuccess())
		{
			$this->nodeResultProxy = new Market\Result\XmlNode();
		}

		return $this->nodeResultProxy;
	}

	protected function copyNodeResultProxy(Market\Result\XmlNode $from = null, Market\Result\XmlNode $to = null)
	{
		if ($from === null || $to === null) { return; }

		$errors = $from->getErrors();
		$canSkip = [
			'NUMBER_NOT_FOUND' => true,
		];

		foreach ($errors as $error)
		{
			$code = $error->getCode();

			if ($code !== null && isset($canSkip[$code]) && count($errors) > 1) { continue; }

			$to->registerError($error->getMessage(), $code);
			break;
		}
	}
}