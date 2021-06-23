<?php

namespace Yandex\Market\Trading\Entity\Sale;

use Yandex\Market;
use Bitrix\Main;
use Bitrix\Sale;

class Platform extends Market\Trading\Entity\Reference\Platform
{
	use Market\Reference\Concerns\HasLang;

	const PLATFORM_PREFIX = 'yamarket_';

	protected $systemPlatform;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function getId()
	{
		return $this->getSystemPlatform()->getId();
	}

	public function isInstalled()
	{
		return $this->getSystemPlatform()->isInstalled();
	}

	public function install(Market\Trading\Service\Reference\Info $info)
	{
		$data = $this->getInfoData($info);
		$result = $this->getSystemPlatform()->installExtended($data);

		if (!$result->isSuccess())
		{
			$message = implode(PHP_EOL, $result->getErrorMessages());
			throw new Main\SystemException($message);
		}

		return $result->getId();
	}

	public function uninstall()
	{
		$callResult = $this->getSystemPlatform()->uninstall();

		return $this->makeSystemCallResult('uninstall', $callResult);
	}

	public function migrate($newCode)
	{
		$this->serviceCode = $newCode;
		$newCode = $this->getSystemPlatformCode();

		return $this->getSystemPlatform()->migrate($newCode);
	}

	public function update(Market\Trading\Service\Reference\Info $info)
	{
		$data = $this->getInfoData($info);
		$result = $this->getSystemPlatform()->updateExtended($data);

		if (!$result->isSuccess())
		{
			$message = implode(PHP_EOL, $result->getErrorMessages());
			throw new Main\SystemException($message);
		}

		return $result;
	}

	public function isActive()
	{
		return $this->getSystemPlatform()->isActive();
	}

	public function activate()
	{
		$callResult = $this->getSystemPlatform()->setActive();

		return $this->makeSystemCallResult('activate', $callResult);
	}

	public function deactivate()
	{
		$callResult = $this->getSystemPlatform()->unsetActive();

		return $this->makeSystemCallResult('deactivate', $callResult);
	}

	public function getSalePlatform()
	{
		return $this->getSystemPlatform();
	}

	protected function getInfoData(Market\Trading\Service\Reference\Info $info)
	{
		return [
			'NAME' => $info->getTitle(),
			'DESCRIPTION' => $info->getDescription(),
		];
	}

	protected function getSystemPlatform()
	{
		if ($this->systemPlatform === null)
		{
			$this->systemPlatform = $this->loadSystemPlatform();
		}

		return $this->systemPlatform;
	}

	/**
	 * @return Internals\Platform
	 * @throws Main\ArgumentNullException
	 */
	protected function loadSystemPlatform()
	{
		$systemCode = $this->getSystemPlatformCode();

		if (\method_exists(Internals\Platform::class, 'getInstanceByCode'))
		{
			$result = Internals\Platform::getInstanceByCode($systemCode);
		}
		else
		{
			$result = Internals\Platform::getInstance($systemCode);
		}

		return $result;
	}

	protected function makeSystemCallResult($method, $callResult)
	{
		$result = new Main\Result();

		if ($callResult === false)
		{
			$methodUpper = Market\Data\TextString::toUpper($method);
			$message = static::getLang('TRADING_ENTITY_SALE_PLATFORM_METHOD_ERROR_' . $methodUpper);

			$result->addError(new Main\Error($message));
		}

		return $result;
	}

	protected function getSystemPlatformCode()
	{
		$result = static::PLATFORM_PREFIX;
		$totalLength = 20;
		$serviceCodeNormalized = str_replace([' ', '.'], '_', $this->serviceCode);
		$serviceCodeNormalized = Market\Data\TextString::toLower($serviceCodeNormalized);
		$leftLength = $totalLength - Market\Data\TextString::getLength($result);

		if ($leftLength >= Market\Data\TextString::getLength($serviceCodeNormalized))
		{
			$serviceCodeNormalized = str_replace(':', '_', $serviceCodeNormalized);

			$result .= $serviceCodeNormalized;
		}
		else
		{
			$behaviorPosition = Market\Data\TextString::getPosition($serviceCodeNormalized, ':');

			if ($behaviorPosition !== false)
			{
				$behavior = Market\Data\TextString::getSubstring($serviceCodeNormalized, $behaviorPosition + 1);
				$leftLength -= Market\Data\TextString::getLength($behavior) + 1;

				$result .=
					Market\Data\TextString::getSubstring($serviceCodeNormalized, 0, $leftLength)
					. '_' . $behavior;
			}
			else
			{
				$result .= Market\Data\TextString::getSubstring($serviceCodeNormalized, 0, $leftLength);
			}
		}

		return $result;
	}
}