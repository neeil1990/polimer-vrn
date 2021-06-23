<?php

namespace Yandex\Market\Trading\Service\Common;

use Yandex\Market;
use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;
use Yandex\Market\Trading\Service as TradingService;

abstract class Options extends TradingService\Reference\Options
	implements Market\Api\Reference\HasOauthConfiguration
{
	use Market\Reference\Concerns\HasLang;

	/** @var Provider */
	protected $provider;
	/** @var Market\Api\OAuth2\Token\Model */
	protected $oauthToken;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function __construct(Provider $provider)
	{
		parent::__construct($provider);
	}

	public function getCampaignId()
	{
		return trim($this->getRequiredValue('CAMPAIGN_ID'));
	}

	/**
	 * @deprecated
	 * @return string
	 */
	public function getYandexToken()
	{
		$tokens = $this->getYandexTokens();
		$token = (string)reset($tokens);

		if ($token === '')
		{
			throw new Main\SystemException('Required option YANDEX_TOKEN is empty');
		}

		return $token;
	}

	public function getYandexTokens()
	{
		$tokens = (array)$this->getRequiredValue('YANDEX_TOKEN');

		return $this->sanitizeYandexTokens($tokens);
	}

	protected function sanitizeYandexTokens(array $tokens)
	{
		foreach ($tokens as $tokenKey => &$token)
		{
			$token = (string)$token;

			if ($token === '')
			{
				unset($tokens[$tokenKey]);
			}
		}

		return $tokens;
	}

	public function getOauthClientId()
	{
		return $this->getRequiredValue('OAUTH_CLIENT_ID');
	}

	public function getOauthClientPassword()
	{
		return $this->getRequiredValue('OAUTH_CLIENT_PASSWORD');
	}

	public function getOauthTokenId()
	{
		return $this->getRequiredValue('OAUTH_TOKEN');
	}

	/**
	 * @return Market\Api\OAuth2\Token\Model
	 */
	public function getOauthToken()
	{
		if ($this->oauthToken === null)
		{
			$this->oauthToken = $this->loadOauthToken();
		}

		return $this->oauthToken;
	}

	protected function loadOauthToken()
	{
		$tokenId = $this->getOauthTokenId();

		return Market\Api\OAuth2\Token\Model::loadById($tokenId);
	}

	public function getLogLevel()
	{
		return $this->getValue('LOG_LEVEL');
	}

	public function getTaxSystem()
	{
		return $this->useTaxSystem() ? (string)$this->getValue('TAX_SYSTEM') : '';
	}

	protected function useTaxSystem()
	{
		return Market\Config::getOption('trading_use_tax_system', 'N') === 'Y';
	}

	public function getCompanyLegalName()
	{
		return (string)$this->getValue('COMPANY_LEGAL_NAME');
	}

	public function getCompanyName()
	{
		return (string)$this->getValue('COMPANY_NAME');
	}

	public function getCompanyLogo()
	{
		$fileId = (int)$this->getValue('COMPANY_LOGO');
		$result = null;

		if ($fileId > 0)
		{
			$result = \CFile::GetFileArray($fileId);
		}

		return $result;
	}

	public function isAllowModifyPrice()
	{
		return false;
	}

	public function isAllowModifyBasket()
	{
		return false;
	}

	public function getPersonType()
	{
		return $this->getRequiredValue('PERSON_TYPE');
	}

	public function getProfileId()
	{
		return (string)$this->getValue('PROFILE_ID');
	}

	public function getProperty($fieldName)
	{
		return $this->getValue('PROPERTY_' . $fieldName);
	}

	public function getProductSkuMap()
	{
		return $this->getValue('PRODUCT_SKU_FIELD');
	}

	public function getProductStores()
	{
		return (array)$this->getValue('PRODUCT_STORE');
	}

	public function isProductStoresTrace()
	{
		return (string)$this->getValue('PRODUCT_STORE_TRACE') === Market\Reference\Storage\Table::BOOLEAN_Y;
	}

	public function getPriceSource()
	{
		return $this->getValue('PRODUCT_PRICE_SOURCE');
	}

	public function getPriceTypes()
	{
		return (array)$this->getValue('PRODUCT_PRICE_TYPE');
	}

	public function usePriceDiscount()
	{
		return ((string)$this->getValue('PRODUCT_PRICE_DISCOUNT') === '1');
	}

	public function getStatusIn($externalStatus)
	{
		$optionKey = 'STATUS_IN_' . $externalStatus;

		return $this->getValue($optionKey);
	}

	public function getStatusOut($bitrixStatus)
	{
		$result = null;

		foreach ($this->provider->getStatus()->getOutgoingVariants() as $status)
		{
			$value = $this->getValue('STATUS_OUT_' . Market\Data\TextString::toUpper($status));
			$isMatched = is_array($value) ? in_array($bitrixStatus, $value, true) : $value === $bitrixStatus;

			if ($isMatched)
			{
				$result = $status;
				break;
			}
		}

		return $result;
	}

	protected function getCommonFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		return [
			'CAMPAIGN_ID' => [
				'TYPE' => 'campaignId',
				'GROUP' => static::getLang('TRADING_SERVICE_COMMON_GROUP_COMPANY_INFO'),
				'MANDATORY' => 'Y',
				'NAME' => static::getLang('TRADING_SERVICE_COMMON_OPTION_CAMPAIGN_ID'),
				'DESCRIPTION' => static::getLang('TRADING_SERVICE_COMMON_OPTION_CAMPAIGN_ID_DESCRIPTION'),
				'SORT' => 1000,
				'SETTINGS' => [
					'PLACEHOLDER' => static::getLang('TRADING_SERVICE_COMMON_OPTION_CAMPAIGN_ID_PLACEHOLDER'),
				],
			],
		];
	}

	protected function getIncomingRequestFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$serviceCode = $this->provider->getCode();
			$request = Main\Context::getCurrent()->getRequest();
			$urlId = $this->getUrlId($siteId);
			$incomingPath = $environment->getRoute()->getPublicPath($serviceCode, $urlId);
			$incomingVariables = array_filter([
				'protocol' => 'https',
				'host' => Market\Data\SiteDomain::getHost($siteId),
			]);

			$result = [
				'YANDEX_TOKEN' => [
					'TYPE' => 'string',
					'GROUP' => static::getLang('TRADING_SERVICE_COMMON_GROUP_SERVICE_REQUEST'),
					'MANDATORY' => 'Y',
					'MULTIPLE' => 'Y',
					'NAME' => static::getLang('TRADING_SERVICE_COMMON_OPTION_YANDEX_TOKEN'),
					'DESCRIPTION' => static::getLang('TRADING_SERVICE_COMMON_OPTION_YANDEX_TOKEN_DESCRIPTION'),
					'SORT' => 2000,
				],
				'YANDEX_INCOMING_URL' => [
					'TYPE' => 'incomingUrl',
					'MANDATORY' => 'Y',
					'NAME' => static::getLang('TRADING_SERVICE_COMMON_OPTION_YANDEX_INCOMING_URL'),
					'DESCRIPTION' => static::getLang('TRADING_SERVICE_COMMON_OPTION_YANDEX_INCOMING_URL_DESCRIPTION'),
					'NOTE' => !$request->isHttps()
						? static::getLang('TRADING_SERVICE_COMMON_OPTION_YANDEX_INCOMING_URL_NOTE_HTTPS')
						: null,
					'VALUE' => Market\Utils\Url::absolutizePath($incomingPath, $incomingVariables),
					'SETTINGS' => [
						'READONLY' => true,
						'COPY_BUTTON' => true,
					],
					'SORT' => 2100,
				],
				'LOG_LEVEL' => [
					'TYPE' => 'enumeration',
					'NAME' => static::getLang('TRADING_SERVICE_COMMON_OPTION_LOG_LEVEL'),
					'DESCRIPTION' => static::getLang('TRADING_SERVICE_COMMON_OPTION_LOG_LEVEL_DESCRIPTION'),
					'VALUES' => [
						[
							'ID' => Market\Logger\Level::ERROR,
							'VALUE' => static::getLang('TRADING_SERVICE_COMMON_OPTION_LOG_LEVEL_ERROR'),
						],
						[
							'ID' => Market\Logger\Level::WARNING,
							'VALUE' => static::getLang('TRADING_SERVICE_COMMON_OPTION_LOG_LEVEL_WARNING'),
						],
						[
							'ID' => Market\Logger\Level::INFO,
							'VALUE' => static::getLang('TRADING_SERVICE_COMMON_OPTION_LOG_LEVEL_INFO'),
						],
						[
							'ID' => Market\Logger\Level::DEBUG,
							'VALUE' => static::getLang('TRADING_SERVICE_COMMON_OPTION_LOG_LEVEL_DEBUG'),
						],
					],
					'SETTINGS' => [
						'DEFAULT_VALUE' => Market\Logger\Level::INFO,
						'CAPTION_NO_VALUE' => static::getLang('TRADING_SERVICE_COMMON_OPTION_LOG_LEVEL_NO_VALUE'),
					],
					'SORT' => 2500,
				],
			];
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getOauthRequestFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$oauthRedirectPath = Market\Ui\UserField\TokenType::getCallbackPath();

		return [
			'OAUTH_CLIENT_ID' => [
				'TYPE' => 'string',
				'MANDATORY' => 'Y',
				'NAME' => static::getLang('TRADING_SERVICE_COMMON_OPTION_OAUTH_CLIENT_ID'),
				'INTRO' => static::getLang('TRADING_SERVICE_COMMON_OPTION_OAUTH_CLIENT_ID_INTRO', [
					'#CALLBACK_URI#' => Market\Utils\Url::absolutizePath($oauthRedirectPath),
				]),
				'SETTINGS' => [
					'PLACEHOLDER' => static::getLang('TRADING_SERVICE_COMMON_OPTION_OAUTH_CLIENT_ID_PLACEHOLDER'),
				],
				'SORT' => 2200,
			],
			'OAUTH_CLIENT_PASSWORD' => [
				'TYPE' => 'string',
				'MANDATORY' => 'Y',
				'NAME' => static::getLang('TRADING_SERVICE_COMMON_OPTION_OAUTH_CLIENT_PASSWORD'),
				'SETTINGS' => [
					'PLACEHOLDER' => static::getLang('TRADING_SERVICE_COMMON_OPTION_OAUTH_CLIENT_PASSWORD_PLACEHOLDER'),
				],
				'SORT' => 2300,
			],
			'OAUTH_TOKEN' => [
				'TYPE' => 'token',
				'MANDATORY' => 'Y',
				'NAME' => static::getLang('TRADING_SERVICE_COMMON_OPTION_OAUTH_TOKEN'),
				'SETTINGS' => [
					'CLIENT_ID_FIELD' => 'OAUTH_CLIENT_ID',
					'CLIENT_PASSWORD_FIELD' => 'OAUTH_CLIENT_PASSWORD',
					'SCOPE' => ['market:partner-api'],
					'STYLE' => 'max-width: 220px;',
				],
				'SORT' => 2400,
			],
		];
	}

	protected function getCompanyFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		/** @var TaxSystem $taxSystem */
		$taxSystem = $this->provider->getTaxSystem();

		return [
			'COMPANY_LEGAL_NAME' => [
				'TYPE' => 'string',
				'MANDATORY' => 'Y',
				'NAME' => static::getLang('TRADING_SERVICE_COMMON_OPTION_COMPANY_LEGAL_NAME'),
				'DESCRIPTION' => static::getLang('TRADING_SERVICE_COMMON_OPTION_COMPANY_LEGAL_NAME_HELP'),
				'SETTINGS' => [
					'PLACEHOLDER' => static::getLang('TRADING_SERVICE_COMMON_OPTION_COMPANY_LEGAL_NAME_PLACEHOLDER'),
					'SIZE' => 40,
				],
				'SORT' => 1100,
			],
			'COMPANY_NAME' => [
				'TYPE' => 'string',
				'NAME' => static::getLang('TRADING_SERVICE_COMMON_OPTION_COMPANY_NAME'),
				'DESCRIPTION' => static::getLang('TRADING_SERVICE_COMMON_OPTION_COMPANY_NAME_HELP'),
				'SETTINGS' => [
					'PLACEHOLDER' => static::getLang('TRADING_SERVICE_COMMON_OPTION_COMPANY_NAME_PLACEHOLDER'),
					'SIZE' => 40,
				],
				'SORT' => 1200,
			],
			'COMPANY_LOGO' => [
				'TYPE' => 'file',
				'NAME' => static::getLang('TRADING_SERVICE_COMMON_OPTION_COMPANY_LOGO'),
				'HELP_MESSAGE' => static::getLang('TRADING_SERVICE_COMMON_OPTION_COMPANY_LOGO_HELP'),
				'SETTINGS' => [
					'EXTENSIONS' => [ 'png' => true, 'jpg' => true, 'jpeg' => true, 'svg' => true, ],
				],
				'SORT' => 1300,
			],
			'TAX_SYSTEM' => [
				'TYPE' => 'enumeration',
				'NAME' => static::getLang('TRADING_SERVICE_COMMON_OPTION_TAX_SYSTEM'),
				'VALUES' => $taxSystem->getTypeEnum(),
				'HIDDEN' => $this->useTaxSystem() ? 'N' : 'Y',
				'SORT' => 1900,
				'SETTINGS' => [
					'STYLE' => 'max-width: 450px;'
				],
			],
		];
	}

	protected function getOrderPersonFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$personType = $environment->getPersonType();
			$personTypeDefault = $this->getPersonTypeDefaultValue($personType, $siteId);
			$user = $environment->getUserRegistry()->getAnonymousUser($this->provider->getServiceCode(), $siteId);

			$result = [
				'PERSON_TYPE' => [
					'TYPE' => 'enumeration',
					'NAME' => static::getLang('TRADING_SERVICE_COMMON_OPTION_PERSON_TYPE'),
					'GROUP' => static::getLang('TRADING_SERVICE_COMMON_GROUP_PROPERTY'),
					'MANDATORY' => 'Y',
					'VALUES' => $personType->getEnum($siteId),
					'HIDDEN' => $personTypeDefault !== null && !Market\Config::isExpertMode() ? 'Y' : 'N',
					'SETTINGS' => [
						'DEFAULT_VALUE' => $personTypeDefault,
						'STYLE' => 'max-width: 220px;',
					],
					'SORT' => 3500,
				],
				'PROFILE_ID' => [
					'TYPE' => 'buyerProfile',
					'NAME' => static::getLang('TRADING_SERVICE_COMMON_OPTION_PROFILE_ID'),
					'GROUP' => static::getLang('TRADING_SERVICE_COMMON_GROUP_PROPERTY'),
					'SETTINGS' => [
						'STYLE' => 'max-width: 220px;',
						'PERSON_TYPE_FIELD' => 'PERSON_TYPE',
						'PERSON_TYPE_DEFAULT' => $personTypeDefault,
						'USER_ID' => $user->getId(),
						'SERVICE' => $this->provider->getCode(),
					],
					'SORT' => 3510,
				],
			];
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getPersonTypeDefaultValue(TradingEntity\Reference\PersonType $personType, $siteId)
	{
		return $personType->getIndividualId($siteId);
	}

	protected function getOrderPropertyUtilFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		$orderClassName = $this->provider->getModelFactory()->getOrderClassName();
		$fields = $orderClassName::getMeaningfulFields();
		$options = [];

		foreach ($fields as $field)
		{
			$options[$field] = [
				'NAME' => $orderClassName::getMeaningfulFieldTitle($field),
				'GROUP' => static::getLang('TRADING_SERVICE_COMMON_GROUP_PROPERTY'),
			];
		}

		return $this->createPropertyFields($environment, $siteId, $options, 3700);
	}

	protected function createPropertyFields(TradingEntity\Reference\Environment $environment, $siteId, array $fields, $sort)
	{
		try
		{
			$environment->getProperty(); // check implemented

			$personType = $environment->getPersonType();
			$personTypeDefault = $this->getPersonTypeDefaultValue($personType, $siteId);
			$result = [];

			foreach ($fields as $fieldName => $field)
			{
				$defaultSettings = [
					'TYPE' => $fieldName,
					'PERSON_TYPE_FIELD' => 'PERSON_TYPE',
					'PERSON_TYPE_DEFAULT' => $personTypeDefault,
					'STYLE' => 'max-width: 220px;',
				];
				$defaultFields = [
					'TYPE' => 'orderProperty',
					'SORT' => $sort,
				];
				$propertyField = $field + $defaultFields;
				$propertyField['SETTINGS'] = isset($field['SETTINGS']) ? $field['SETTINGS'] + $defaultSettings : $defaultSettings;

				$result['PROPERTY_' . $fieldName] = $propertyField;

				++$sort;
			}
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getProductSkuMapFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$environment->getProduct(); // check product implemented

			$result = [
				'PRODUCT_SKU_FIELD' => [
					'TYPE' => 'skuField',
					'TAB' => 'STORE',
					'MULTIPLE' => 'Y',
					'NAME' => static::getLang('TRADING_SERVICE_COMMON_OPTION_PRODUCT_SKU_FIELD'),
					'INTRO' => static::getLang('TRADING_SERVICE_COMMON_OPTION_PRODUCT_SKU_FIELD_DESCRIPTION'),
					'SORT' => 1000,
					'HIDDEN' => !Market\Config::isExpertMode() ? 'Y' : 'N',
				],
			];
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getProductStoreFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$store = $environment->getStore();

			$result = [
				'PRODUCT_STORE' => [
					'TYPE' => 'enumeration',
					'TAB' => 'STORE',
					'MULTIPLE' => 'Y',
					'MANDATORY' => 'Y',
					'NAME' => static::getLang('TRADING_SERVICE_COMMON_OPTION_PRODUCT_STORE'),
					'INTRO' => static::getLang('TRADING_SERVICE_COMMON_OPTION_PRODUCT_STORE_DESCRIPTION'),
					'VALUES' => $store->getEnum($siteId),
					'SETTINGS' => [
						'DISPLAY' => 'CHECKBOX',
						'DEFAULT_VALUE' => $store->getDefaults(),
					],
					'SORT' => 1100,
				],
				'PRODUCT_STORE_TRACE' => [
					'TYPE' => 'boolean',
					'TAB' => 'STORE',
					'NAME' => static::getLang('TRADING_SERVICE_COMMON_OPTION_PRODUCT_STORE_TRACE'),
					'SORT' => 1110,
				],
			];
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getProductPriceFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$price = $environment->getPrice();
			$userGroup = $environment->getUserGroupRegistry()->getGroup($this->provider->getServiceCode(), $siteId);
			$userGroupIds = (array)$userGroup->getId();

			$result = [
				'PRODUCT_PRICE_SOURCE' => [
					'TYPE' => 'enumeration',
					'TAB' => 'STORE',
					'NAME' => static::getLang('TRADING_SERVICE_COMMON_OPTION_PRODUCT_PRICE_SOURCE'),
					'VALUES' => $price->getSourceEnum(),
					'SETTINGS' => [
						'CAPTION_NO_VALUE' => static::getLang('TRADING_SERVICE_COMMON_OPTION_PRODUCT_PRICE_SOURCE_NO_VALUE'),
					],
					'SORT' => 2000,
				],
				'PRODUCT_PRICE_TYPE' => [
					'TYPE' => 'enumeration',
					'TAB' => 'STORE',
					'MULTIPLE' => 'Y',
					'NAME' => static::getLang('TRADING_SERVICE_COMMON_OPTION_PRODUCT_PRICE_TYPE'),
					'MANDATORY' => 'Y',
					'VALUES' => $price->getTypeEnum(),
					'SETTINGS' => [
						'DISPLAY' => 'CHECKBOX',
						'DEFAULT_VALUE' => $price->getTypeDefaults($userGroupIds),
					],
					'DEPEND' => [
						'PRODUCT_PRICE_SOURCE' => [
							'RULE' => 'EMPTY',
							'VALUE' => false,
						],
					],
					'SORT' => 2100,
				],
				'PRODUCT_PRICE_DISCOUNT' => [
					'TYPE' => 'boolean',
					'TAB' => 'STORE',
					'NAME' => static::getLang('TRADING_SERVICE_COMMON_OPTION_PRODUCT_PRICE_DISCOUNT'),
					'SETTINGS' => [
						'DEFAULT_VALUE' => '1',
					],
					'DEPEND' => [
						'PRODUCT_PRICE_SOURCE' => [
							'RULE' => 'EMPTY',
							'VALUE' => false,
						],
					],
					'SORT' => 2200,
				],
			];
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getStatusInFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$serviceCode = Market\Data\TextString::toUpper($this->provider->getServiceCode());
			$serviceStatus = $this->provider->getStatus();
			$environmentStatus = $environment->getStatus();
			$environmentVariants = $environmentStatus->getVariants();
			$environmentEnum = $environmentStatus->getEnum($environmentVariants);
			$incomingRequired = $serviceStatus->getIncomingRequired();
			$incomingVariants = $serviceStatus->getIncomingVariants();
			$statusDefaults = $this->makeStatusDefaults($environmentStatus->getMeaningfulMap(), $serviceStatus->getIncomingMeaningfulMap());
			$sort = 1000;
			$result = [];

			foreach ($incomingVariants as $statusVariant)
			{
				$isRequired = in_array($statusVariant, $incomingRequired, true);
				$defaultValue = isset($statusDefaults[$statusVariant]) ? $statusDefaults[$statusVariant] : null;

				if (is_array($defaultValue))
				{
					$defaultValue = reset($defaultValue);
				}

				$result['STATUS_IN_' . $statusVariant] = [
					'TYPE' => 'enumeration',
					'TAB' => 'STATUS',
					'GROUP' => static::getLang('TRADING_SERVICE_' . $serviceCode . '_GROUP_STATUS_IN'),
					'NAME' => $serviceStatus->getTitle($statusVariant),
					'MANDATORY' => $isRequired ? 'Y' : 'N',
					'VALUES' => $environmentEnum,
					'SETTINGS' => [
						'DEFAULT_VALUE' => $defaultValue,
						'STYLE' => 'max-width: 300px;',
						'ALLOW_NO_VALUE' => $defaultValue === null || !$isRequired ? 'Y' : 'N',
					],
					'SORT' => $sort,
				];

				++$sort;
			}
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function getStatusOutFields(TradingEntity\Reference\Environment $environment, $siteId)
	{
		try
		{
			$serviceCode = Market\Data\TextString::toUpper($this->provider->getServiceCode());
			$environmentStatus = $environment->getStatus();
			$environmentStatusVariants = $environmentStatus->getVariants();
			$environmentStatusEnum = $environmentStatus->getEnum($environmentStatusVariants);
			$serviceStatus = $this->provider->getStatus();
			$serviceOutgoingVariants = $serviceStatus->getOutgoingVariants();
			$serviceOutgoingRequired = $serviceStatus->getOutgoingRequired();
			$serviceOutgoingMultiple = $serviceStatus->getOutgoingMultiple();
			$statusDefaults = $this->makeStatusDefaults($environmentStatus->getMeaningfulMap(), $serviceStatus->getOutgoingMeaningfulMap());
			$sort = 2000;
			$result = [];

			foreach ($serviceOutgoingVariants as $serviceOutgoingVariant)
			{
				$isMultiple = in_array($serviceOutgoingVariant, $serviceOutgoingMultiple, true);
				$isRequired = in_array($serviceOutgoingVariant, $serviceOutgoingRequired, true);
				$defaultValue = isset($statusDefaults[$serviceOutgoingVariant]) ? $statusDefaults[$serviceOutgoingVariant] : null;

				if ($isMultiple)
				{
					$defaultValue = (array)$defaultValue;
				}
				else if (is_array($defaultValue))
				{
					$defaultValue = reset($defaultValue);
				}

				$result['STATUS_OUT_' . $serviceOutgoingVariant] = [
					'TYPE' => 'enumeration',
					'TAB' => 'STATUS',
					'GROUP' => static::getLang('TRADING_SERVICE_' . $serviceCode . '_GROUP_STATUS_OUT'),
					'NAME' => $serviceStatus->getTitle($serviceOutgoingVariant) . ' (' . $serviceOutgoingVariant . ')',
					'MULTIPLE' => $isMultiple ? 'Y' : 'N',
					'MANDATORY' => $isRequired ? 'Y' : 'N',
					'VALUES' => $environmentStatusEnum,
					'SETTINGS' => [
						'DEFAULT_VALUE' => $defaultValue,
						'STYLE' => 'max-width: 300px;',
						'ALLOW_NO_VALUE' => $defaultValue === null || !$isRequired ? 'Y' : 'N',
					],
					'SORT' => $sort,
				];

				++$sort;
			}
		}
		catch (Market\Exceptions\NotImplemented $exception)
		{
			$result = [];
		}

		return $result;
	}

	protected function makeStatusDefaults($environmentMeaningfulMap, $serviceMeaningfulMap)
	{
		$result = [];

		foreach ($environmentMeaningfulMap as $meaningfulStatus => $environmentVariant)
		{
			if (isset($serviceMeaningfulMap[$meaningfulStatus]))
			{
				$serviceVariant = $serviceMeaningfulMap[$meaningfulStatus];

				$result[$serviceVariant] = $environmentVariant;
			}
		}

		return $result;
	}

	protected function applyFieldsOverrides(array $fields, array $overrides = null)
	{
		if ($overrides !== null)
		{
			foreach ($fields as &$field)
			{
				$field = $overrides + $field;

				if (isset($overrides['SORT']))
				{
					++$overrides['SORT'];
				}
			}
			unset($field);
		}

		return $fields;
	}
}