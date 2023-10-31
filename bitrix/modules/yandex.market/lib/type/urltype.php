<?php

namespace Yandex\Market\Type;

use Bitrix\Main;
use Yandex\Market;

class UrlType extends AbstractType
{
	protected $idnCache = [];

	public function format($value, array $context = [], Market\Export\Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		$parsedUrl = $this->parseUrl($value);

		if ($parsedUrl !== null)
		{
			if ($parsedUrl['DOMAIN'] === '') // no domain
			{
				$parsedUrl['DOMAIN'] = $context['DOMAIN_URL'];
			}
			else if (Market\Data\TextString::getPosition($parsedUrl['DOMAIN'], '//') === 0) // without protocol
			{
				$parsedUrl['DOMAIN'] =
					($context['HTTPS'] ? 'https:' : 'http:')
					. $parsedUrl['DOMAIN'];
			}

			if ($parsedUrl['PATH'] !== '' && Market\Data\TextString::getPosition($parsedUrl['PATH'], '/') !== 0) // no start slash for path
			{
				$parsedUrl['PATH'] = '/' . $parsedUrl['PATH'];
			}

			$result =
				$this->idnDomain($parsedUrl['DOMAIN'])
				. $this->encodeUrlPath($parsedUrl['PATH'])
				. $parsedUrl['QUERY'];
		}
		else
		{
			$result = $value;
		}

		$result = str_replace('&', '&amp;', $result); // escape xml entities

		return $result;
	}

	protected function idnDomain($domain)
	{
		if (isset($this->idnCache[$domain]))
		{
			$result = $this->idnCache[$domain];
		}
		else
		{
			$errorList = [];
			$idnDomain = \CBXPunycode::ToASCII($domain, $errorList);
			$result = ($idnDomain !== false ? $idnDomain : $domain);

			$this->idnCache[$domain] = $result;
		}

		return $result;
	}

	protected function encodeUrlPath($path)
	{
		$result = $path;

		if (preg_match('#[^A-Za-z0-9-_.~/?=&]#', $path)) // has invalid chars
		{
			$charset = $this->getCharset();
			$parts = preg_split("#(://|:\\d+/|/|\\?|=|&)#", $path, -1, PREG_SPLIT_DELIM_CAPTURE);
			$result = '';

			foreach ($parts as $partIndex => $part)
			{
				if ($partIndex % 2 === 0)
				{
					if (preg_match('/%[0-9A-F]{2}/', $part)) // has encoded chars
					{
						$part = rawurldecode($part);
					}

					if ($charset !== false)
					{
						$part = Main\Text\Encoding::convertEncoding($part, LANG_CHARSET, $charset);
					}

					$part = rawurlencode($part);
				}

				$result .= $part;
			}
		}

		return $result;
	}

	protected function parseUrl($url)
	{
		$result = null;

		if (preg_match('#^((?:[A-Za-z]+?:)?//[^/?\#]+)?([^?\#]*)(.*)?$#', $url, $matches))
		{
			$result = [
				'DOMAIN' => (string)$matches[1],
				'PATH' => (string)$matches[2],
				'QUERY' => (string)$matches[3],
			];
		}

		return $result;
	}

	protected function getCharset()
	{
		$result = false;

		if (!Main\Application::isUtfMode())
		{
			$result = 'UTF-8';
		}

		return $result;
	}
}