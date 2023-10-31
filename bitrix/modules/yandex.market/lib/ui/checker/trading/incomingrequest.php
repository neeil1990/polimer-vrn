<?php

namespace Yandex\Market\Ui\Checker\Trading;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Ui\Checker;
use Yandex\Market\Trading\Setup as TradingSetup;
use Yandex\Market\Ui\Trading as UiTrading;

class IncomingRequest extends Checker\Reference\AbstractTest
{
	protected $siteHosts = [];

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	public function test()
	{
		$result = new Market\Result\Base();
		$collection = TradingSetup\Collection::loadByFilter([
			'filter' => [ '=ACTIVE' => TradingSetup\Table::BOOLEAN_Y ]
		]);

		/** @var TradingSetup\Model $setup */
		foreach ($collection as $setup)
		{
			if (!$this->canTest($setup)) { continue; }

			$helloTest = new UiTrading\HelloTest([
				'url' => $this->getIncomingUrl($setup),
				'site' => $setup->getSiteId(),
			]);

			$helloResult = $helloTest->run();

			if (!$helloResult->isSuccess())
			{
				$helloErrors = $helloResult->getErrors();
				$error = $this->makeError($setup, $helloTest, reset($helloErrors));

				$result->addError($error);
			}
		}

		return $result;
	}

	protected function canTest(TradingSetup\Model $setup)
	{
		$siteId = $setup->getSiteId();
		$host = (string)$this->getSiteHost($siteId);
		$request = Main\Context::getCurrent()->getRequest();

		if ($host !== '')
		{
			$result = true;
		}
		else if ($request instanceof Main\HttpRequest)
		{
			$requestHost = (string)$request->getHttpHost();

			$result = (
				$requestHost !== ''
				&& Market\Data\TextString::toLower($requestHost) !== 'localhost'
				&& filter_var($requestHost, FILTER_VALIDATE_DOMAIN)
			);
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	protected function getIncomingUrl(TradingSetup\Model $setup)
	{
		$serviceCode = $setup->getService()->getCode();
		$siteId = $setup->getSiteId();
		$urlId = $setup->getUrlId();
		$publicPath = $setup->getEnvironment()->getRoute()->getPublicPath($serviceCode, $urlId);

		return Market\Utils\Url::absolutizePath($publicPath, array_filter([
			'protocol' => 'https',
			'host' => $this->getSiteHost($siteId),
		]));
	}

	protected function getSiteHost($siteId)
	{
		if (!array_key_exists($siteId, $this->siteHosts))
		{
			$this->siteHosts[$siteId] = $this->loadSiteHost($siteId);
		}

		return $this->siteHosts[$siteId];
	}

	protected function loadSiteHost($siteId)
	{
		return Market\Data\SiteDomain::getHost($siteId);
	}

	protected function makeError(TradingSetup\Model $setup, UiTrading\HelloTest $helloTest, Market\Error\Base $error)
	{
		$message = sprintf(
			'%s: %s',
			$setup->getField('NAME'),
			Market\Data\TextString::lcfirst($error->getMessage())
		);
		$description = $helloTest->getErrorDescription($error);

		$result = new Checker\Reference\Error($message);
		$result->setDescription($description);

		return $result;
	}

	protected function getLangPrefix()
	{
		return 'CHECKER_TEST_TRADING_INCOMING_REQUEST';
	}
}