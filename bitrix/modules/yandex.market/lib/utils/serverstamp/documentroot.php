<?php

namespace Yandex\Market\Utils\ServerStamp;

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Data\TextString;
use Yandex\Market\Reference\Concerns;

class DocumentRoot implements PropertyInterface
{
	use Concerns\HasMessage;

	public function name()
	{
		return 'document_root';
	}

	public function title()
	{
		return self::getMessage('TITLE');
	}

	public function reset()
	{
		// nothing
	}

	public function collect()
	{
		$sites = $this->sites();

		if (!$this->isCliOnly() && $this->hasSiteConflict($sites)) { return null; } // nothing to collect

		$active = $this->serverRoot();
		$result = array_column($sites, 'DOC_ROOT');
		$result = array_filter($result, static function($root) { return $root !== ''; });

		if (!in_array($active, $result, true))
		{
			$result[] = $active;
		}

		return $result;
	}

	public function test($stored, $current)
	{
		$active = $this->serverRoot();

		if (!in_array($active, $stored, true))
		{
			throw new ChangedException(self::getMessage('CHANGED', [
				'#STORED#' => implode(', ', $stored),
				'#CURRENT#' => $active,
			]));
		}
	}

	protected function serverRoot()
	{
		/** @var Main\Application $application */
		$application = Main\Application::getInstance();
		$server = $application->getContext()->getServer();

		return $this->sanitizePath($server->getDocumentRoot());
	}

	protected function sites()
	{
		$result = [];

		$query = Main\SiteTable::getList([
			'filter' => [ '=ACTIVE' => 'Y' ],
			'order' => [ 'LID' => 'ASC' ],
			'select' => [ 'LID', 'DIR', 'DOC_ROOT' ],
		]);

		while ($row = $query->fetch())
		{
			$result[$row['LID']] = [
				'DIR' => $this->sanitizeDir($row['DIR']),
				'DOC_ROOT' => $this->sanitizePath($row['DOC_ROOT']),
			];
		}

		return $result;
	}

	protected function hasSiteConflict(array $sites)
	{
		$result = false;

		foreach ($sites as $siteId => $site)
		{
			foreach ($sites as $searchId => $search)
			{
				if ($siteId === $searchId) { continue; }

				if (
					$site['DIR'] === $search['DIR']
					&& (
						$site['DOC_ROOT'] === ''
						|| $site['DOC_ROOT'] === $search['DOC_ROOT']
					)
				)
				{
					$result = true;
					break;
				}
			}

			if ($result) { break; }
		}

		return $result;
	}

	protected function isCliOnly()
	{
		return Market\Utils::isCli() && Market\Utils::isAgentUseCron();
	}

	protected function sanitizeDir($dir)
	{
		$dir = $this->sanitizePath($dir);

		return '/' . trim($dir, '/');
	}

	protected function sanitizePath($path)
	{
		$path = trim($path);
		$path = (string)Main\IO\Path::normalize($path);

		return TextString::toLower($path);
	}
}