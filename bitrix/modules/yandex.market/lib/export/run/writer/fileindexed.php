<?php

namespace Yandex\Market\Export\Run\Writer;

use Bitrix\Main;
use Yandex\Market\Export\Run\Helper\BinaryString;

class FileIndexed extends File
{
	protected $index;

	public function __construct(array $parameters = [])
	{
		parent::__construct($parameters);

		$this->index = new FileIndex\Controller($this->getParameter('setupId'));
	}

	public function commit()
	{
		$this->releaseFileResource();
		$size = $this->fileSize();

		$this->index->commit($size);
	}

	public function test()
	{
		$size = $this->fileSize();

		return $this->index->test($size);
	}

	protected function fileSize()
	{
		clearstatcache(true, $this->filePath);

		return (int)filesize($this->filePath);
	}

	public function writeRoot($element, $header = '')
	{
		$content = ($element instanceof \SimpleXMLElement ? $element->asXML() : $element);

		parent::writeRoot($element, $header);
		$this->indexRoot($content, $header);
	}

	protected function indexRoot($content, $header = '')
	{
		$order = 0;
		$rows = [];

		// header

		if ($header !== '')
		{
			$rows[] = [
				'NAME' => '#HEADER',
				'PRIMARY' => static::NULL_REFERENCE,
				'POSITION' => $order++,
				'SIZE' => BinaryString::getLength($header),
			];
		}

		// tags

		preg_match_all('#</?[\w-]+(?:\s.+?)?>#', $content, $matches);

		if (empty($matches[0])) { throw new Main\SystemException('cant parse root xml content'); }

		$offset = 0;
		$previousTag = null;

		foreach ($matches[0] as $tag)
		{
			if (preg_match('#/>$#', $tag)) { continue; } // skip self closed

			list($tagName) = $this->parseTagContentName($tag);
			$position = BinaryString::getPosition($content, $tag, $offset);

			if ($tagName === null) { throw new Main\SystemException(sprintf('cant parse root tag %s', $tag)); }
			if ($position === false) { throw new Main\SystemException(sprintf('cant find root tag %s', $tag)); }

			$tagRow = [
				'NAME' => $tagName,
				'PRIMARY' => static::NULL_REFERENCE,
				'POSITION' => $order++,
				'SIZE' => BinaryString::getLength($tag),
			];

			if ($previousTag !== null)
			{
				$previousTag['SIZE'] = $position - $offset;
				$rows[] = $previousTag;
			}

			$previousTag = $tagRow;
			$offset = $position;
		}

		if ($previousTag !== null)
		{
			$rows[] = $previousTag;
		}

		$this->index->clear();
		$this->index->insert($rows);
	}

	public function writeTagList($elementList, $parentName, $position = null)
	{
		$written = parent::writeTagList($elementList, $parentName, $position);

		if (empty($written)) { return []; }

		list($anchor, $isAfterAnchor) = $this->anchorTag($parentName, $position);
		list($anchorName) = $this->parseTagContentName($anchor);
		$anchorOrder = $this->index->order($anchorName, static::NULL_REFERENCE);

		if ($isAfterAnchor) { ++$anchorOrder; }

		$this->indexNewTags($elementList, $written, $anchorOrder);

		return $written;
	}

	protected function indexNewTags($elementList, $written, $order)
	{
		$loopOrder = $order;
		$rows = [];

		foreach ($elementList as $elementId => $element)
		{
			$content = ($element instanceof \SimpleXMLElement ? $element->asXML() : $element);
			list($name) = $this->parseTagContentName($element);
			$closeTag = '</' . $name . '>';

			$size = $written[$elementId];

			if (
				$elementId === static::NULL_REFERENCE
				&& BinaryString::getPosition($content, $closeTag) !== false
			)
			{
				$closeLength = BinaryString::getLength($closeTag);

				$rows[] = [
					'NAME' => $name,
					'PRIMARY' => $elementId,
					'POSITION' => $loopOrder++,
					'SIZE' => $size - $closeLength,
				];

				$rows[] = [
					'NAME' => '/' . $name,
					'PRIMARY' => $elementId,
					'POSITION' => $loopOrder++,
					'SIZE' => $closeLength,
				];
			}
			else
			{
				$rows[] = [
					'NAME' => $name,
					'PRIMARY' => $elementId,
					'POSITION' => $loopOrder++,
					'SIZE' => $size,
				];
			}
		}

		$this->index->adjust($order, $loopOrder - $order);
		$this->index->insert($rows);
	}

	public function updateTagList($tagName, $elementList, $idAttr = 'id', $isSelfClosed = null)
	{
		$written = parent::updateTagList($tagName, $elementList, $idAttr, $isSelfClosed);
		$written += array_fill_keys(array_keys($elementList), 0);
		$toDelete = array_filter($written, static function($size) { return $size === 0; });
		$toUpdate = array_diff_key($written, $toDelete);

		$this->index->update($tagName, $toUpdate);

		if (isset($toDelete[static::NULL_REFERENCE]))
		{
			$this->index->remove(
				[ $tagName, '/' . $tagName ],
				[ static::NULL_REFERENCE ]
			);

			unset($toDelete[static::NULL_REFERENCE]);
		}

		$this->index->remove($tagName, array_keys($toDelete));

		return $written;
	}

	protected function parseTagContentName($xmlContent)
	{
		$matched = preg_match('#^<(?<name>/?[\w-]+)(?:\s\w+="(?<primary>.+?)")?(?:\s|>|$)#', $xmlContent, $matches);

		if (!$matched) { return null; }

		return [
			$matches['name'],
			isset($matches['primary']) ? $matches['primary'] : static::NULL_REFERENCE,
		];
	}

	protected function getPositionList($searchList, $startPosition = null, $stopSearch = null)
	{
		if ($startPosition !== null || $stopSearch !== null)
		{
			return parent::getPositionList($searchList, $startPosition, $stopSearch);
		}

		$unknown = [];
		$groups = [];

		foreach ($searchList as $resultKey => $searchContent)
		{
			$matches = $this->parseTagContentName($searchContent);

			if ($matches === null)
			{
				$unknown[$resultKey] = $searchContent;
			}
			else
			{
				list($name, $primary) = $matches;

				if (!isset($groups[$name])) { $groups[$name] = []; }

				$groups[$name][$resultKey] = $primary;
			}
		}

		$result = [];

		if (!empty($unknown))
		{
			$result += parent::getPositionList($unknown, $startPosition, $stopSearch);
		}

		foreach ($groups as $name => $primaries)
		{
			$result += $this->index->search($name, $primaries);
		}

		return $result;
	}
}