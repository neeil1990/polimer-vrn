<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market;
use Bitrix\Main;

class AnonymousUser extends User
{
	/** @var Environment */
	protected $environment;
	protected $serviceCode;
	protected $siteId;

	protected static function includeMessages()
	{
		parent::includeMessages();
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function __construct(Environment $environment, $serviceCode, $siteId)
	{
		parent::__construct($environment, []);
		$this->serviceCode = $serviceCode;
		$this->siteId = $siteId;
	}

	public function checkInstall()
	{
		$userId = $this->getOptionValue();

		if ($userId !== null)
		{
			if (!$this->isExistUser($userId))
			{
				$this->id = null;
				$this->releaseOptionValue();
			}
			else
			{
				$this->clearUserPhoneAuth($userId);
			}
		}
	}

	public function getId()
	{
		if ($this->id === null)
		{
			$this->id = $this->getOptionValue() ?: $this->searchUser();
		}

		return $this->id;
	}

	protected function getSearchFilters()
	{
		return [
			[ '=XML_ID' => $this->getXmlId() ],
		];
	}

	public function install(array $data = [])
	{
		$result = parent::install($data);

		if ($result->isSuccess())
		{
			$this->saveOptionValue($result->getId());
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
			$this->saveOptionValue($this->getId());
		}

		return $updateResult;
	}

	protected function getMigrateData($code)
	{
		$currentData = $this->getDefaultData();
		$newData = $this->getDefaultData($code);
		$changedData = array_diff($newData, $currentData);
		$changedData = array_diff_key($changedData, [ 'XML_ID' => true ]);
		$currentLogin = $this->getDataLogin($currentData);
		$newLogin = $this->getDataLogin($newData);

		if (isset($changedData['EMAIL']))
		{
			$changedData['EMAIL'] = $this->resolveEmail($changedData['EMAIL']);
		}

		if ($currentLogin !== $newLogin)
		{
			$changedData['LOGIN'] = $this->makeUniqueLogin($newLogin);
		}

		return $changedData;
	}

	protected function getOptionName()
	{
		return 'trading_' . $this->serviceCode . '_user_id';
	}

	protected function getOptionValue()
	{
		$name = $this->getOptionName();
		$value = (int)Market\Config::getOption($name);
		$result = null;

		if ($value > 0)
		{
			$result = $value;
		}

		return $result;
	}

	protected function saveOptionValue($userId)
	{
		$name = $this->getOptionName();

		Market\Config::setOption($name, $userId);
	}

	protected function releaseOptionValue()
	{
		$name = $this->getOptionName();

		Market\Config::removeOption($name);
	}

	protected function getDefaultData($serviceCode = null)
	{
		if ($serviceCode === null) { $serviceCode = $this->serviceCode; }

		return [
			'LID' => $this->siteId,
			'EMAIL' => $serviceCode . 'anonymous@market.yandex.ru',
			'ACTIVE' => 'N',
			'NAME' => static::getLang('TRADING_ENTITY_SALE_ANONYMOUS_USER_NAME'),
			'XML_ID' => $this->getXmlId($serviceCode),
			'EXTERNAL_AUTH_ID' => 'saleanonymous',
		];
	}

	protected function getXmlId($serviceCode = null)
	{
		if ($serviceCode === null) { $serviceCode = $this->serviceCode; }

		return 'yamarket_' . $serviceCode . '_anonymous';
	}

	protected function isExistUser($id)
	{
		$id = (int)$id;
		$result = false;

		if ($id > 0)
		{
			$query = Main\UserTable::getList([
				'filter' => [ '=ID' => $id ],
				'select' => [ 'ID' ],
			]);

			$result = (bool)$query->fetch();
		}

		return $result;
	}

	protected function clearUserPhoneAuth($id)
	{
		$id = (int)$id;

		if ($id > 0 && $this->hasPhoneRegistration())
		{
			$query = Main\UserPhoneAuthTable::getList([
				'select' => [ 'USER_ID' ],
				'filter' => [
					'=USER_ID' => $id,
					'=CONFIRMED' => 'N',
				],
			]);

			if ($row = $query->fetch())
			{
				Main\UserPhoneAuthTable::delete($row['USER_ID']);
			}
		}
	}
}