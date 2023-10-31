<?php

namespace Yandex\Market\Api\Partner\File;

use Bitrix\Main;
use Yandex\Market;

class Request extends Market\Api\Partner\Reference\Request
{
	protected $path;

	public function getPath()
	{
		if ($this->path === null)
		{
			throw new Main\SystemException('file path not set');
		}

		return $this->path;
	}

	public function setPath($path)
	{
		$uri = new Main\Web\Uri($path);
		$host = (string)$uri->getHost();
		$path = (string)$uri->getPath();
		$query = (string)$uri->getQuery();

		if ($host !== '' && $host !== $this->getHost())
		{
			throw new Main\ArgumentException($host . ' out of range');
		}

		if (!preg_match('/\.json$/', $path))
		{
			$path .= '.json';
		}

		if ($query !== '')
		{
			$path .= '?' . $query;
		}

		$this->path = $path;
	}

	public function buildResponse($data)
	{
		return new Response($data);
	}

	protected function parseHttpResponse($httpResponse, $contentType = 'application/json')
	{
		if (Market\Data\TextString::toLower($contentType) === 'application/pdf')
		{
			$result = [
				'status' => Response::STATUS_OK,
				'type' => $contentType,
				'contents' => $httpResponse,
			];
		}
		else
		{
			$result = parent::parseHttpResponse($httpResponse, $contentType);
		}

		return $result;
	}
}