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
			$publicWriter = $this->getPublicWriter();

			if ($publicWriter)
			{
				$writer = $this->getWriter();

				$writer->copy($publicWriter->getPath());
				$publicWriter->refresh();
			}
		}

		return $result;
	}

	public function updateDate()
	{
		$tag = $this->getTag();
		$attribute = $tag ? $tag->getAttribute('date') : null;

		if ($tag === null || $attribute === null) { return; }

		$dateType = Market\Type\Manager::getType($attribute->getValueType());
		$writer = $this->getPublicWriter() ?: $this->getWriter();

		$writer->setPointer(0);
		$writer->updateAttribute(
			$tag->getName(),
			0,
			[ $attribute->getName() => $dateType->format(new Main\Type\DateTime(), $this->getContext(), $attribute) ],
			''
		);
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