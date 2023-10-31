<?php

namespace Yandex\Market\Export\Run\Steps;

use Bitrix\Main;
use Yandex\Market;

class Root extends Base
{
	public function getName()
	{
		return 'root';
	}

	public function clear($isStrict = false)
	{
		parent::clear($isStrict);

		if ($isStrict)
		{
			$writer = $this->getWriter();

			$writer->lock(true);
			$writer->unlock();
			$writer->remove();
		}
	}

	public function run($action, $offset = null)
	{
		$result = new Market\Result\Step();

		$this->setRunAction($action);

		if ($action === 'full') // on full export reset file
		{
			$context = $this->getContext();
			$tagValuesList = [
				$this->createTagValue($context)
			];
			$elementList = [ [] ]; // one empty array

			$this->extendData($tagValuesList, $elementList, $context);
			$this->writeData($tagValuesList, $elementList, $context);
		}
		else if ($action === 'refresh')
		{
			$publicPath = $this->getProcessor()->getPublicFilePath();
			$writer = $this->getWriter();

			if ($publicPath !== null)
			{
				$writer->copy($publicPath);
			}
		}

		return $result;
	}

	public function updateDate()
	{
		$tag = $this->getTag();
		$attribute = $tag ? $tag->getAttribute('date') : null;

		if ($tag === null || $attribute === null) { return; }

		$updated = $this->resolveUpdated();
		$dateType = Market\Type\Manager::getType($attribute->getValueType());
		$writer = $this->getWriter();

		$writer->setPointer(0);
		$writer->updateAttribute(
			$tag->getName(),
			0,
			[ $attribute->getName() => $dateType->format($updated, $this->getContext(), $attribute) ],
			''
		);

		$this->commitUpdated($updated);
	}

	protected function resolveUpdated()
	{
		$initTime = $this->getParameter('initTime');

		if (!($initTime instanceof Main\Type\DateTime))
		{
			$initTime = new Main\Type\DateTime();
		}

		if ($initTime instanceof Market\Data\Type\CanonicalDateTime)
		{
			if (!Market\Utils::isCli() && $this->getRunAction() === 'full')
			{
				$timezone = date_default_timezone_get();
			}
			else
			{
				$timezone = Market\Environment::getTimezone() ?: date_default_timezone_get();
			}

			$initTime = clone $initTime;
			$initTime->setTimeZone(new \DateTimeZone($timezone));
		}

		$lastUpdated = $this->lastUpdated();

		if ($lastUpdated !== null && Market\Data\DateTime::compare($lastUpdated, $initTime) === 1)
		{
			$lastUpdated->add('PT1S'); // add one second for last updated

			return $lastUpdated;
		}

		return $initTime;
	}

	protected function lastUpdated()
	{
		$stateName = $this->updatedStateName();
		$dateString = (string)Market\State::get($stateName);

		if ($dateString === '') { return null; }

		return new Main\Type\DateTime($dateString, \DateTime::ATOM);
	}

	protected function commitUpdated(Main\Type\DateTime $date)
	{
		$stateName = $this->updatedStateName();
		$value = $date->format(\DateTime::ATOM);

		Market\State::set($stateName, $value);
	}

	protected function updatedStateName()
	{
		return 'feed_updated_' . $this->getSetup()->getId();
	}

	protected function writeDataFile($storageResultList, $context)
	{
		$storageResult = reset($storageResultList);

		if (
			$storageResult !== false
			&& $storageResult['STATUS'] === static::STORAGE_STATUS_SUCCESS
		)
		{
			$header = $this->getFormat()->getHeader();

			$this->getWriter()->writeRoot($storageResult['CONTENTS'], $header);
		}
	}

	protected function getDataLogEntityType()
	{
		return Market\Logger\Table::ENTITY_TYPE_EXPORT_RUN_ROOT;
	}

	public function getFormatTag(Market\Export\Xml\Format\Reference\Base $format, $type = null)
	{
		return $format->getRoot();
	}

	public function getFormatTagParentName(Market\Export\Xml\Format\Reference\Base $format)
	{
		return null;
	}

	protected function createTagValue($context)
	{
		$result = new Market\Result\XmlValue();

		if (isset($context['SHOP_DATA']['NAME']))
		{
			$shopName = trim($context['SHOP_DATA']['NAME']);

			if ($shopName !== '')
			{
				$result->addTag('name', $shopName);
			}
		}

		if (isset($context['SHOP_DATA']['COMPANY']))
		{
			$shopCompany = trim($context['SHOP_DATA']['COMPANY']);

			if ($shopCompany !== '')
			{
				$result->addTag('company', $shopCompany);
			}
		}

		return $result;
	}
}