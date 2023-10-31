<?php

namespace Yandex\Market\Result;

use Yandex\Market;
use Bitrix\Main;

class XmlNode extends Base
{
	const PLAIN_TAG_NAME = 'ym_plain';

	protected static $replaceIndex = 0;
	protected static $replaceMarker = 'YANDEX_MARKET_XMLNODE_REPLACE_';

	/** @var \SimpleXMLElement|null */
	protected $xmlElement;
	/** @var string|null */
	protected $xmlContents;
	protected $replaces = [];
	protected $errorTagName;
	protected $errorAttributeName;
	protected $hasPlain = false;

	public function setErrorTagName($name)
	{
		$this->errorTagName = $name;
		$this->errorAttributeName = null;
	}

	public function setErrorAttributeName($name)
	{
		$this->errorAttributeName = $name;
	}

	public function registerError($errorMessage, $errorCode = null)
	{
		if ($this->isErrorStrict)
		{
			$this->addError($this->createError($errorMessage, $errorCode));
		}
		else
		{
			$this->addWarning($this->createError($errorMessage, $errorCode));
		}
	}

	public function registerWarning($errorMessage, $errorCode = null)
	{
		$this->addWarning($this->createError($errorMessage, $errorCode));
	}

	protected function createError($errorMessage, $errorCode = null)
	{
		$result = new Market\Error\XmlNode($errorMessage, $errorCode);

		if ($this->errorTagName !== null)
		{
			$result->setTagName($this->errorTagName);
		}

		if ($this->errorAttributeName !== null)
		{
			$result->setAttributeName($this->errorAttributeName);
		}

		return $result;
	}

	public function hasPlain()
	{
		return $this->hasPlain;
	}

	public function registerPlain()
	{
		$this->hasPlain = true;
	}

	public function addReplace($text, $index = null)
	{
		if ($index === null)
		{
			$index = static::$replaceIndex++;
		}

		$this->replaces[$index] = $text;

		return static::$replaceMarker . $index;
	}

	public function getReplaces()
	{
		return $this->replaces;
	}

	/**
	 * Получить значение атрибута
	 *
	 * @param string    $tagName        Имя тега
	 * @param string    $attributeName  Имя атрибута
	 *
	 * @return mixed
	 */
	public function getTagAttribute($tagName, $attributeName)
	{
		$result = null;

		if ($this->xmlElement !== null)
		{
			$targetTag = null;

			if ($this->xmlElement->getName() === $tagName)
			{
				$targetTag = $this->xmlElement;
			}
			else if (isset($this->xmlElement->{$tagName}))
			{
				$targetTag = $this->xmlElement->{$tagName};
			}

			if ($targetTag !== null && isset($targetTag[$attributeName]))
			{
				$result = (string)$targetTag[$attributeName];
			}
		}

		return $result;
	}

	public function setXmlElement(\SimpleXMLElement $xmlElement)
	{
		$this->xmlElement = $xmlElement;
		$this->xmlContents = null; // invalidate contents
	}

	public function getXmlElement()
	{
		return $this->xmlElement;
	}

	public function invalidateXmlContents()
	{
		$this->xmlContents = null;
	}

	public function getXmlContents()
	{
		if ($this->xmlContents === null && $this->xmlElement !== null)
		{
			$contents = $this->xmlElement->asXML();

			foreach ($this->replaces as $index => $replace)
			{
				$contents = str_replace(static::$replaceMarker . $index, $replace, $contents);
			}

			if ($this->hasPlain)
			{
				$contents = str_replace(
					[ '<' . static::PLAIN_TAG_NAME . '>', '</' . static::PLAIN_TAG_NAME . '>' ],
					'',
					$contents
				);
			}

			$this->xmlContents = $contents;
		}

		return $this->xmlContents;
	}
}