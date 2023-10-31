<?php

namespace Yandex\Market\Trading\Service\MarketplaceDbs\Options;

use Bitrix\Main;
use Bitrix\Sale;
use Yandex\Market;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

class PaySystemOption extends TradingService\Reference\Options\Fieldset
{
	use Market\Reference\Concerns\HasLang;

	/** @var TradingService\MarketplaceDbs\Provider $provider */
	protected $provider;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	/** @return int */
	public function getPaySystemId()
	{
		return (int)$this->getRequiredValue('ID');
	}

	/** @return string */
	public function getType()
	{
		return (string)$this->getRequiredValue('TYPE');
	}

	/** @return string|null */
	protected function resolveType()
	{
		$result = null;

		if ($this->useMethod())
		{
			$usageMap = $this->provider->getPaySystem()->getUsageMap();
			$method = $this->getMethod();

			foreach ($usageMap as $type => $methods)
			{
				if (in_array($method, $methods, true))
				{
					$result = $type;
					break;
				}
			}
		}
		else
		{
			$result = $this->getType();
		}

		return $result;
	}

	/** @return bool */
	public function useMethod()
	{
		return (string)$this->getValue('USE_METHOD') === Market\Ui\UserField\BooleanType::VALUE_Y;
	}

	/** @return string */
	public function getMethod()
	{
		return (string)$this->getRequiredValue('METHOD');
	}

	public function getCashboxCheck()
	{
		return (
			$this->resolveType() === TradingService\Marketplace\PaySystem::TYPE_PREPAID
				? $this->getCashboxCheckPrepaid()
				: $this->getCashboxCheckPostpaid()
		);
	}

	protected function getCashboxCheckPrepaid()
	{
		return $this->getValue('CASHBOX_CHECK_PREPAID', TradingService\Marketplace\PaySystem::CASHBOX_CHECK_DISABLED);
	}

	protected function getCashboxCheckPostpaid()
	{
		return $this->getValue('CASHBOX_CHECK_POSTPAID', TradingService\Marketplace\PaySystem::CASHBOX_CHECK_ENABLED);
	}

	protected function applyValues()
	{
		$this->applyUseMethod();
	}

	protected function applyUseMethod()
	{
		if (isset($this->values['USE_METHOD']) || empty($this->values['METHOD'])) { return; }

		$this->values['USE_METHOD'] = Market\Ui\UserField\BooleanType::VALUE_Y;
	}

	public function getFieldDescription(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return parent::getFieldDescription($environment, $siteId) + [
			'SETTINGS' => [
				'SUMMARY' => '&laquo;#ID#&raquo; (#TYPE#, #METHOD#)',
				'LAYOUT' => 'summary',
				'VALIGN_PUSH' => 'pill',
			],
		];
	}

	public function getFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$servicePaySystem = $this->provider->getPaySystem();
			$hasCashboxSupport = (Main\Loader::includeModule('sale') && class_exists(Sale\Cashbox\Cashbox::class));

			$result = [
				'ID' => [
					'TYPE' => 'enumeration',
					'MANDATORY' => 'Y',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTIONS_PAY_SYSTEM_OPTION_ID'),
					'VALUES' => $environment->getPaySystem()->getEnum($siteId),
					'SETTINGS' => [
						'STYLE' => 'max-width: 220px;',
						'ALLOW_UNKNOWN' => 'Y', // preserve deactivated services
					],
				],
				'TYPE' => [
					'TYPE' => 'enumeration',
					'MANDATORY' => 'Y',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTIONS_PAY_SYSTEM_OPTION_TYPE'),
					'VALUES' => $this->provider->getPaySystem()->getTypeEnum(),
					'DEPEND' => [
						'USE_METHOD' => [
							'RULE' => Market\Utils\UserField\DependField::RULE_EMPTY,
							'VALUE' => true,
						],
					],
				],
				'METHOD' => [
					'TYPE' => 'enumeration',
					'MANDATORY' => 'Y',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTIONS_PAY_SYSTEM_OPTION_METHOD'),
					'VALUES' => $servicePaySystem->getMethodEnum(),
					'DEPEND' => [
						'USE_METHOD' => [
							'RULE' => Market\Utils\UserField\DependField::RULE_EMPTY,
							'VALUE' => false,
						],
					],
				],
				'USE_METHOD' => [
					'TYPE' => 'boolean',
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTIONS_PAY_SYSTEM_OPTION_USE_METHOD'),
				],
				'CASHBOX_CHECK_PREPAID' => [
					'TYPE' => 'enumeration',
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTIONS_PAY_SYSTEM_OPTION_ADDITIONAL'),
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTIONS_PAY_SYSTEM_OPTION_CASHBOX_CHECK_PREPAID'),
					'HELP_MESSAGE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTIONS_PAY_SYSTEM_OPTION_CASHBOX_CHECK_PREPAID_HELP'),
					'VALUES' => $this->sortCashboxCheckEnum(
						$servicePaySystem->getCashboxCheckEnum(),
						$servicePaySystem::CASHBOX_CHECK_DISABLED
					),
					'SETTINGS' => [
						'DEFAULT_VALUE' => $servicePaySystem::CASHBOX_CHECK_DISABLED,
						'ALLOW_NO_VALUE' => 'N',
					],
					'HIDDEN' => $hasCashboxSupport ? 'N' : 'Y',
					'DEPEND' => $this->makeCashboxCheckDepend($servicePaySystem, $servicePaySystem::TYPE_PREPAID),
				],
				'CASHBOX_CHECK_POSTPAID' => [
					'TYPE' => 'enumeration',
					'GROUP' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTIONS_PAY_SYSTEM_OPTION_ADDITIONAL'),
					'NAME' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTIONS_PAY_SYSTEM_OPTION_CASHBOX_CHECK_POSTPAID'),
					'HELP_MESSAGE' => static::getLang('TRADING_SERVICE_MARKETPLACE_OPTIONS_PAY_SYSTEM_OPTION_CASHBOX_CHECK_POSTPAID_HELP'),
					'VALUES' => $this->sortCashboxCheckEnum(
						$servicePaySystem->getCashboxCheckEnum(),
						$servicePaySystem::CASHBOX_CHECK_ENABLED
					),
					'SETTINGS' => [
						'DEFAULT_VALUE' => $servicePaySystem::CASHBOX_CHECK_ENABLED,
						'ALLOW_NO_VALUE' => 'N',
					],
					'HIDDEN' => $hasCashboxSupport ? 'N' : 'Y',
					'DEPEND' => $this->makeCashboxCheckDepend($servicePaySystem, $servicePaySystem::TYPE_POSTPAID),
				],
			];
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function sortCashboxCheckEnum(array $enum, $default)
	{
		usort($enum, static function($optionA, $optionB) use ($default) {
			$sortA = $optionA['ID'] === $default ? 0 : 1;
			$sortB = $optionB['ID'] === $default ? 0 : 1;

			if ($sortA === $sortB) { return 0; }

			return $sortA < $sortB ? -1 : 1;
		});

		return $enum;
	}

	protected function makeCashboxCheckDepend(TradingService\Marketplace\PaySystem $paySystem, $paymentType)
	{
		$usageMap = $paySystem->getUsageMap();

		return [
			'LOGIC' => 'OR',
			'TYPE' => [
				'RULE' => Market\Utils\UserField\DependField::RULE_ANY,
				'VALUE' => [$paymentType],
			],
			'METHOD' => [
				'RULE' => Market\Utils\UserField\DependField::RULE_ANY,
				'VALUE' => isset($usageMap[$paymentType]) ? $usageMap[$paymentType] : [],
			],
		];
	}
}
