<?php
namespace Yandex\Market\Data\Xml;

class TagString
{
	public static function sliceByTag($tagString, $tagName)
	{
		$open = sprintf('<%s', $tagName);
		$close = sprintf('</%s>', $tagName);
		$offset = 0;
		$tagPartials = [];
		$collectionPartials = [];

		while ($openPosition = mb_strpos($tagString, $open, $offset))
		{
			$closePosition = mb_strpos($tagString, $close, $openPosition);

			if ($closePosition === false) { break; }

			$closePosition += mb_strlen($close);

			if ($openPosition > $offset)
			{
				$tagPartials[] = mb_substr($tagString, $offset, $openPosition - $offset);
			}

			$collectionPartials[] = mb_substr($tagString, $openPosition, $closePosition - $openPosition);

			$offset = $closePosition;
		}

		if ($offset > 0) // found
		{
			$tagPartials[] = mb_substr($tagString, $offset);
		}

		return [ $tagPartials, $collectionPartials ];
	}

	public static function injectAfter($tagString, $anchorName, $content)
	{
		return static::injectTag($tagString, sprintf('</%s>', $anchorName), $content, true);
	}

	public static function injectAppend($tagString, $anchorName, $content)
	{
		return static::injectTag($tagString, sprintf('</%s>', $anchorName), $content);
	}

	protected static function injectTag($tagString, $anchor, $content, $after = false)
	{
		$position = mb_strpos($tagString, $anchor);

		if ($position === false) { return null; }

		if ($after)
		{
			$position += mb_strlen($anchor);
		}

		return (
			mb_substr($tagString, 0, $position)
			. $content
			. mb_substr($tagString, $position)
		);
	}
}
