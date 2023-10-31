<?php
namespace Yandex\Market\Component\Molecules;

use Bitrix\Main;

class ApiPaging
{
	private $gridId;

	public function __construct($gridId)
	{
		$this->gridId = $gridId;
	}

	public function getParameters(array $queryParameters, $pageSize = null)
	{
		$result = [];

		if (isset($queryParameters['limit'], $queryParameters['offset']))
		{
			if ($pageSize === null) { $pageSize = $queryParameters['limit']; }

			$result['page'] = floor($queryParameters['offset'] / $pageSize) + 1;
			$result['limit'] = $pageSize;

			if ($result['page'] > 1)
			{
				$result['pageToken'] = $this->getPageToken($result, $result['page']);
			}
		}

		return $result;
	}

	public function setPageToken($filter, $page, $token)
	{
		$sessionKey = $this->getPageTokenSessionKey();
		$queryKey = $this->makeFilterKey($filter) . '-' . $page;

		if (!isset($_SESSION[$sessionKey]))
		{
			$_SESSION[$sessionKey] = [];
		}

		$_SESSION[$sessionKey][$queryKey] = (string)$token;
	}

	public function getPageToken($filter, $page)
	{
		$sessionKey = $this->getPageTokenSessionKey();
		$queryKey = $this->makeFilterKey($filter) . '-' . $page;

		if (!isset($_SESSION[$sessionKey][$queryKey]))
		{
			throw new Main\ArgumentException('missing session pageToken', 'pageToken');
		}

		return (string)$_SESSION[$sessionKey][$queryKey];
	}

	protected function makeFilterKey($filter)
	{
		if (!is_array($filter) || empty($filter)) { return '0'; }

		$parts = [];
		$filter = array_diff_key($filter, [
			'page' => true,
			'pageToken' => true,
		]);

		foreach ($filter as $key => $value)
		{
			if (is_array($value))
			{
				$parts[$key] = implode(',', $value);
			}
			else if ($value instanceof Main\Type\DateTime)
			{
				$parts[$key] = $value->format('Y-m-d');
			}
			else
			{
				$parts[$key] = (string)$value;
			}
		}

		return implode('|', $parts);
	}

	protected function getPageTokenSessionKey()
	{
		return $this->gridId . '_PAGE_TOKEN';
	}
}