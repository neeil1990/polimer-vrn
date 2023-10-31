<?php

namespace Yandex\Market\Utils\ServerStamp;

use Bitrix\Main;
use Yandex\Market\Data\TextString;
use Yandex\Market\Reference\Concerns;

class Database implements PropertyInterface
{
	use Concerns\HasMessage;

	public function name()
	{
		return 'database';
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
		$connection = Main\Application::getConnection();

		if ($connection === null) { return null; }

		$name = $connection->getDatabase();
		$host = $connection->getHost();
		$host = $this->sanitizeHost($host);

		return $name . '@' . $host;
	}

	public function test($stored, $current)
	{
		if (TextString::toLower($stored) !== TextString::toLower($current))
		{
			throw new ChangedException(self::getMessage('CHANGED', [
				'#STORED#' => $stored,
				'#CURRENT#' => $current,
			]));
		}
	}

	protected function sanitizeHost($host)
	{
		return preg_replace('/^127\.0\.0\.1(:|$)/', 'localhost$1', $host);
	}
}