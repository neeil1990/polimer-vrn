<?php

namespace Yandex\Market\Trading\Entity\Common;

use Yandex\Market;
use Bitrix\Main;

class UserGroup extends Market\Trading\Entity\Reference\UserGroup
{
	protected $id;

	public function getId()
	{
		if ($this->id === null)
		{
			$this->id = $this->searchGroup();
		}

		return $this->id;
	}

	public function install(array $data = [])
	{
		$fullData = $this->getDefaultData() + $data;
		$result = new Main\Entity\AddResult();

		$addProvider = new \CGroup();
		$addResult = $addProvider->Add($fullData);

		if ($addResult !== false)
		{
			$this->id = $addResult;
			$result->setId($addResult);
		}
		else
		{
			$error = new Main\Error($addProvider->LAST_ERROR);
			$result->addError($error);
		}

		return $result;
	}

	public function migrate($code)
	{
		$data = $this->getMigrateData($code);
		$updateResult = $this->update($data);

		if ($updateResult->isSuccess())
		{
			$this->serviceCode = $code;
		}

		return $updateResult;
	}

	protected function getMigrateData($code)
	{
		$currentData = $this->getDefaultData();
		$newData = $this->getDefaultData($code);

		return array_diff($newData, $currentData);
	}

	public function update(array $data)
	{
		$groupId = $this->getId();
		$result = new Main\Entity\UpdateResult();

		if ($groupId === null)
		{
			$error = new Main\Error('cant update not installed group');
			$result->addError($error);
		}
		else if (!empty($data))
		{
			$updateProvider = new \CGroup();
			$updateResult = $updateProvider->Update($groupId, $data);

			if ($updateResult === false)
			{
				$error = new Main\Error($updateProvider->LAST_ERROR);
				$result->addError($error);
			}
		}

		return $result;
	}

	protected function getDefaultData($serviceCode = null)
	{
		if ($serviceCode === null) { $serviceCode = $this->serviceCode; }

		return [
			'ACTIVE' => 'Y',
			'C_SORT' => 1000,
			'IS_SYSTEM' => 'Y',
			'ANONYMOUS' => 'N',
			'STRING_ID' => $this->getXmlId($serviceCode),
		];
	}

	protected function searchGroup()
	{
		$result = null;

		$query = Main\GroupTable::getList([
			'filter' => [ '=STRING_ID' => $this->getXmlId(), ],
			'select' => [ 'ID', ],
			'limit' => 1,
		]);

		if ($row = $query->fetch())
		{
			$result = (int)$row['ID'];
		}

		return $result;
	}

	protected function getXmlId($serviceCode = null)
	{
		if ($serviceCode === null) { $serviceCode = $this->serviceCode; }

		$serviceCodeLower = Market\Data\TextString::toLower($serviceCode);

		return 'yamarket_' . $serviceCodeLower;
	}
}