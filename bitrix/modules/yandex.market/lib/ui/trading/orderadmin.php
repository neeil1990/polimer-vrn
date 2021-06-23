<?php

namespace Yandex\Market\Ui\Trading;

use Yandex\Market;
use Bitrix\Main;

class OrderAdmin extends Market\Ui\Reference\Page
{
	use Market\Reference\Concerns\HasLang;
	use Market\Ui\Trading\Concerns\HasHandleMigration;

	protected static function includeMessages()
	{
		Main\Localization\Loc::loadMessages(__FILE__);
	}

	protected function getReadRights()
	{
		return Market\Ui\Access::RIGHTS_PROCESS_TRADING;
	}

	public function show()
	{
		$setupCollection = $this->getSetupCollection();
		$adminUrls = $this->getAdminUrlList($setupCollection);
		$mergedUrls = $this->mergeUrlList($adminUrls);

		if (count($mergedUrls) === 1)
		{
			$url = reset($mergedUrls);

			$this->redirectUrl($url);
		}
		else
		{
			$this->showUrlList($setupCollection, $adminUrls);
		}
	}

	public function handleException(\Exception $exception)
	{
		$isHandled = (
			$this->handleMigration($exception)
			|| $this->handleDeprecated($exception)
		);

		if (!$isHandled)
		{
			\CAdminMessage::ShowMessage([
				'TYPE' => 'ERROR',
				'MESSAGE' => $exception->getMessage(),
			]);
		}
	}

	protected function redirectUrl($url)
	{
		LocalRedirect($url);
		die();
	}

	protected function showUrlList(Market\Trading\Setup\Collection $collection, array $urls)
	{
		echo '<ul>';

		foreach ($urls as $setupId => $url)
		{
			$setup = $collection->getItemById($setupId);

			if ($setup === null)
			{
				throw new Main\SystemException(sprintf('cant find setup with id %s', $setupId));
			}

			echo sprintf(
				'<li><a href="%s">%s</a></li>',
				htmlspecialcharsbx($url),
				$setup->getService()->getInfo()->getTitle()
			);
		}

		echo '</ul>';
	}

	protected function getServiceCode()
	{
		$result = (string)$this->request->get('service');

		if ($result === '')
		{
			$message = static::getLang('UI_TRADING_ORDER_ADMIN_SERVICE_CODE_NOT_SET');
			throw new Main\ArgumentException($message, 'service');
		}

		if (!Market\Trading\Service\Manager::isExists($result))
		{
			$message = static::getLang('UI_TRADING_ORDER_ADMIN_SERVICE_CODE_INVALID', [ '#SERVICE#' => $result ]);
			throw new Main\SystemException($message);
		}

		return $result;
	}

	protected function getSetupCollection()
	{
		$collection = Market\Trading\Setup\Collection::loadByFilter([
			'filter' => [
				'=TRADING_SERVICE' => $this->getServiceCode(),
				'=ACTIVE' => Market\Trading\Setup\Table::BOOLEAN_Y,
			],
		]);

		if (count($collection) === 0)
		{
			$message = static::getLang('UI_TRADING_ORDER_ADMIN_SETUP_NOT_FOUND');
			throw new Main\ObjectNotFoundException($message);
		}

		return $collection;
	}

	protected function getAdminUrlList(Market\Trading\Setup\Collection $collection)
	{
		$result = [];

		/** @var Market\Trading\Setup\Model $setup */
		foreach ($collection as $setup)
		{
			$platform = $setup->getPlatform();
			$orderRegistry = $setup->getEnvironment()->getOrderRegistry();
			$setupId = $setup->getId();

			$result[$setupId] = $orderRegistry->getAdminListUrl($platform);
		}

		return $result;
	}

	protected function mergeUrlList(array $urls)
	{
		$pageGroups = $this->groupUrlListByPage($urls);
		$result = [];

		foreach ($pageGroups as $page => $pageUrls)
		{
			$result[] = $page . '?' . $this->mergeUrlListQuery($pageUrls);
		}

		return $result;
	}

	protected function groupUrlListByPage(array $urls)
	{
		$result = [];

		foreach ($urls as $urlIndex => $url)
		{
			$queryPosition = Market\Data\TextString::getPosition($url, '?');
			$page = ($queryPosition !== false)
				? Market\Data\TextString::getSubstring($url, 0, $queryPosition)
				: $url;

			if (!isset($result[$page])) { $result[$page] = []; }

			$result[$page][] = $url;
		}

		return $result;
	}

	protected function mergeUrlListQuery(array $urls)
	{
		$page = null;
		$query = [];

		foreach ($urls as $url)
		{
			$urlQueryString = parse_url($url, PHP_URL_QUERY);
			parse_str($urlQueryString, $urlQuery);

			if (empty($urlQuery)) { continue; }

			foreach ($urlQuery as $key => $value)
			{
				if (!isset($query[$key]))
				{
					$query[$key] = $value;
				}
				else if ($query[$key] === $value)
				{
					// nothing
				}
				else
				{
					if (!is_array($query[$key]))
					{
						$query[$key] = (array)$query[$key];
					}

					if (!in_array($value, $query[$key], true))
					{
						$query[$key][] = $value;
					}
				}
			}
		}

		return http_build_query($query);
	}
}