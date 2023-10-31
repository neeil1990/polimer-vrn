<?php

namespace Yandex\Market\Export\Run\Writer;

use Bitrix\Main;
use Yandex\Market;

Main\Localization\Loc::loadMessages(__FILE__);

class File extends Base
{
	const BUFFER_LENGTH = 8192;
	const NULL_REFERENCE = 'null_reference';

	protected $filePath;
	protected $fileResource;
	protected $tempResource;
	protected $bufferLength;

	public function __construct(array $parameters = [])
	{
		parent::__construct($parameters);

		$this->filePath = $this->getParameter('filePath');
	}

	public function destroy()
	{
		$this->releaseFileResource();
		$this->releaseTempResource();
	}

	public function getPath()
	{
		return $this->filePath;
	}

	public function refresh()
	{
		$this->releaseFileResource();
	}

	public function lock($isBlocked = false)
	{
		$file = $this->getFileResource();
		$mode = null;

		if ($isBlocked)
		{
			$mode = LOCK_EX;
		}
		else
		{
			$mode = LOCK_EX | LOCK_NB;
		}

		return flock($file, $mode);
	}

	public function unlock()
	{
		$file = $this->getFileResource();

		return flock($file, LOCK_UN);
	}

	public function move($filePath)
	{
		$this->releaseFileResource();

		if (file_exists($filePath)) { unlink($filePath); }

		rename($this->filePath, $filePath);

		$this->filePath = $filePath;
	}

	public function copy($fromPath)
	{
		$this->releaseFileResource();

		if (file_exists($this->filePath)) { unlink($this->filePath); }

		copy($fromPath, $this->filePath);
	}

	public function remove()
	{
		$this->releaseFileResource();

		if (file_exists($this->filePath)) { unlink($this->filePath); }
	}

	public function getPointer()
	{
		$resource = $this->getFileResource();

		return ftell($resource);
	}

	public function setPointer($position)
	{
		$resource = $this->getFileResource();

		fseek($resource, $position);
	}

	public function writeRoot($element, $header = '')
	{
		$resource = $this->getFileResource();
		$contents = $header;
		$contents .= ($element instanceof \SimpleXMLElement ? $element->asXML() : $element);

		ftruncate($resource, 0);
		fseek($resource, 0);

		$this->fileWrite($resource, $contents);
	}

	public function writeTagList($elementList, $parentName, $position = null)
	{
		$anchorPosition = $this->anchorPosition($parentName, $position);

		if ($anchorPosition === null) { return []; }

		$contents = '';
		$result = [];

		foreach ($elementList as $elementId => $element)
		{
			$elementContent = $element instanceof \SimpleXMLElement ? $element->asXML() : $element;

			$result[$elementId] = Market\Export\Run\Helper\BinaryString::getLength($elementContent);
			$contents .= $elementContent;
		}

		$this->writeSplice($anchorPosition, $anchorPosition, $contents);

		return $result;
	}

	protected function anchorPosition($parentName, $position = null)
	{
		list($searchName, $isAfterSearch) = $this->anchorTag($parentName, $position);
		$result = $this->getPosition($searchName);

		if ($result === null) { return null; }

		if ($isAfterSearch)
		{
			$result += Market\Export\Run\Helper\BinaryString::getLength($searchName);
		}

		return $result;
	}

	protected function anchorTag($parentName, $position = null)
	{
		switch ($position)
		{
			case static::POSITION_AFTER:
				$searchName = '</' . $parentName . '>';
				$isAfterSearch = true;
			break;

			case static::POSITION_BEFORE:
				$searchName = '<' . $parentName . '>';
				$isAfterSearch = false;
			break;

			case static::POSITION_PREPEND:
				$searchName = '<' . $parentName . '>';
				$isAfterSearch = true;
			break;

			case static::POSITION_APPEND:
			default:
				$searchName = '</' . $parentName . '>';
				$isAfterSearch = false;
			break;
		}

		return [$searchName, $isAfterSearch];
	}

	public function writeTag($element, $parentName, $position = null)
	{
		$writeResultList = $this->writeTagList([ static::NULL_REFERENCE => $element ], $parentName, $position);

		return reset($writeResultList);
	}

	public function writeParent($elementName, $parentName, $position = null)
	{
		$tag = '<' . $elementName . '></' . $elementName . '>';

		return $this->writeTag($tag, $parentName, $position);
	}

	public function updateAttributeList($tagName, $elementAttributeList, $idAttr = 'id')
	{
		$searchList = [];

		foreach ($elementAttributeList as $id => $attributeList)
		{
			if ($idAttr)
			{
				$searchList[$id] = '<' . $tagName . ' ' . $idAttr . '="' . $id . '"';
			}
			else
			{
				$searchList[$id] = '<' . $tagName;
			}
		}

		if (!empty($searchList))
		{
			$positionList = $this->getPositionList($searchList);

			asort($positionList, SORT_NUMERIC);

			foreach ($positionList as $elementId => &$tagOpenPosition)
			{
				$tagEndPosition = $this->getPosition('>', $tagOpenPosition + 1, '<'); // stop next open tag

				if ($tagEndPosition !== null)
				{
					$originalTag = $this->read($tagOpenPosition, $tagEndPosition);
					$newTag = $originalTag;
					$attributeList = $elementAttributeList[$elementId];
					$newAttributes = '';

					foreach ($attributeList as $attributeName => $attributeValue)
					{
						$attributeString = ' ' . $attributeName . '="' . $attributeValue . '"';

						if (preg_match('/ ' . $attributeName . '=".*?"/', $newTag, $matches))
						{
							$newTag = str_replace($matches[0], $attributeString, $newTag);
						}
						else
						{
							$newAttributes .= $attributeString;
						}
					}

					if ($newAttributes !== '')
					{
						if (Market\Data\TextString::getSubstring($newTag, -2) === ' /') // is self closed
						{
							$newTag .= Market\Data\TextString::getSubstring($newTag, 0, -2) . $newAttributes . ' /';
						}
						else
						{
							$newTag .= $newAttributes;
						}
					}

					if ($originalTag !== $newTag)
					{
						$this->writeSplice($tagOpenPosition, $tagEndPosition, $newTag);
					}
				}
			}
		}
	}

	public function updateAttribute($tagName, $id, $attributeList, $idAttr = 'id')
	{
		$this->updateAttributeList($tagName, [ $id => $attributeList ], $idAttr);
	}

	public function updateTagList($tagName, $elementList, $idAttr = 'id', $isSelfClosed = null)
	{
		$searchList = [];
		$result = [];

		foreach ($elementList as $id => $element)
		{
			if ($id === static::NULL_REFERENCE)
			{
				$searchList[$id] = '<' . $tagName . '>';
			}
			else
			{
				$searchList[$id] = '<' . $tagName . ' ' . $idAttr . '="' . $id . '"';
			}
		}

		if (!empty($searchList))
		{
			$positionList = $this->getPositionList($searchList);
			$positionMap = array_flip($positionList);
			$selfClose = '/>';
			$selfCloseLength = Market\Export\Run\Helper\BinaryString::getLength($selfClose);
			$tagClose = '</' . $tagName . '>';
			$tagCloseLength = Market\Export\Run\Helper\BinaryString::getLength($tagClose);
			$waitMergePositions = [];
			$waitMergeContents = [];

			asort($positionList, SORT_NUMERIC);

			$positionElements = array_keys($positionList);

			foreach ($positionElements as $elementId)
			{
				$position = $positionList[$elementId];
				$closePosition = null;
				$selfClosePosition = ($elementId !== static::NULL_REFERENCE && $isSelfClosed !== false ? $this->getPosition($selfClose, $position + 1, '<') : null); // stop next open tag

				if ($selfClosePosition !== null)
				{
					$closePosition = $selfClosePosition + $selfCloseLength;
				}
				else if ($isSelfClosed !== true)
				{
					$tagClosePosition = $this->getPosition($tagClose, $position + 1, '<' . $tagName . ' '); // stop on next tag same type

					if ($tagClosePosition !== null)
					{
						$closePosition = $tagClosePosition + $tagCloseLength;
					}
				}

				if ($closePosition === null) { continue; }

				$element = $elementList[$elementId];
				$elementContent = $element instanceof \SimpleXMLElement ? $element->asXML() : $element;
				$elementLength = Market\Export\Run\Helper\BinaryString::getLength($elementContent);
				$result[$elementId] = $elementLength;

				if (isset($positionMap[$closePosition]))
				{
					$mergeElementId = $positionMap[$closePosition];
					$mergePosition = $position;
					$mergeContents = $elementContent;

					if (isset($waitMergePositions[$elementId]))
					{
						$mergePosition = $waitMergePositions[$elementId];
						$mergeContents = $waitMergeContents[$elementId] . $mergeContents;

						unset($waitMergePositions[$elementId], $waitMergeContents[$elementId]);
					}

					$waitMergePositions[$mergeElementId] = $mergePosition;
					$waitMergeContents[$mergeElementId] = $mergeContents;
				}
				else
				{
					$newContents = $elementContent;

					if (isset($waitMergePositions[$elementId]))
					{
						$position = $waitMergePositions[$elementId];
						$newContents = $waitMergeContents[$elementId] . $newContents;

						unset($waitMergePositions[$elementId], $waitMergeContents[$elementId]);
					}

					$diffLength = $this->writeSplice($position, $closePosition, $newContents);

					if ($diffLength !== 0)
					{
						foreach ($positionList as $nextElementId => $nextPosition)
						{
							if ($nextPosition > $position)
							{
								unset($positionMap[$nextPosition]);

								$newPosition = $nextPosition + $diffLength;

								$positionList[$nextElementId] = $newPosition;
								$positionMap[$newPosition] = $nextElementId;
							}
						}
					}
				}
			}
		}

		return $result;
	}

	public function updateTag($tagName, $id, $element, $idAttr = 'id', $isSelfClosed = null)
	{
		if ($id === null)
		{
			$updateList = [ static::NULL_REFERENCE => $element ];
		}
		else
		{
			$updateList = [ $id => $element ];
		}

		$this->updateTagList($tagName, $updateList, $idAttr, $isSelfClosed);
	}

	public function searchTagList($tagName, $idList, $idAttr = 'id')
	{
		$searchList = [];
		$result = [];

		foreach ($idList as $id)
		{
			$searchList[$id] = '<' . $tagName . ' ' . $idAttr . '="' . $id . '"';
		}

		if (!empty($searchList))
		{
			$positionList = $this->getPositionList($searchList);
			$selfClose = '/>';
			$selfCloseLength = Market\Export\Run\Helper\BinaryString::getLength($selfClose);
			$tagClose = '</' . $tagName . '>';
			$tagCloseLength = Market\Export\Run\Helper\BinaryString::getLength($tagClose);

			asort($positionList, SORT_NUMERIC);

			foreach ($positionList as $id => $position)
			{
				$closePosition = null;
				$selfClosePosition = $this->getPosition($selfClose, $position + 1, '<'); // stop next open tag

				if ($selfClosePosition !== null)
				{
					$closePosition = $selfClosePosition + $selfCloseLength;
				}
				else
				{
					$tagClosePosition = $this->getPosition($tagClose, $position + 1, '<' . $tagName . ' '); // stop on next tag same type

					if ($tagClosePosition !== null)
					{
						$closePosition = $tagClosePosition + $tagCloseLength;
					}
				}

				if ($closePosition !== null)
				{
					$result[$id] = $this->read($position, $closePosition);
				}
			}
		}

		return $result;
	}

	public function searchTag($tagName, $id, $idAttr = 'id')
	{
		$listResult = $this->searchTagList($tagName, [ $id ], $idAttr);

		return isset($listResult[$id]) ? $listResult[$id] : null;
	}

	protected function writeSplice($startPosition, $finishPosition, $contents = '')
	{
		$resource = $this->getFileResource();
		$tempResource = null;
		$contentsLength = Market\Export\Run\Helper\BinaryString::getLength($contents);
		$diffLength  = $contentsLength - ($finishPosition - $startPosition);

		if ($diffLength !== 0) // copy contents after finish to temp
		{
			$tempResource = $this->getTempResource();

			$this->streamCopy($resource, $tempResource, null, $finishPosition);
		}

		if ($diffLength < 0) // hanging end
		{
			ftruncate($resource, $startPosition);
		}

		fseek($resource, $startPosition);

		if ($contentsLength > 0) // write contents
		{
			$this->fileWrite($resource, $contents, $contentsLength);
		}

		if ($diffLength !== 0) // return contents after finish to initial resource
		{
			fseek($tempResource, 0);

			$this->streamCopy($tempResource, $resource);

			fseek($resource, $finishPosition); // restore resource position
		}

		return $diffLength;
	}

	protected function read($startPosition, $finishPosition)
	{
		$resource = $this->getFileResource();

		fseek($resource, $startPosition);

		return fread($resource, $finishPosition - $startPosition);
	}

	protected function getPosition($search, $startPosition = null, $stopSearch = null)
	{
		$searchList = [ 0 => $search ];
		$positionList = $this->getPositionList($searchList, $startPosition, $stopSearch);

		return isset($positionList[0]) ? $positionList[0] : null;
	}

	protected function getPositionList($searchList, $startPosition = null, $stopSearch = null)
	{
		$resource = $this->getFileResource();
		$isSupportReturnToStart = false;
		$bufferLength = $this->getBufferLength();

		if (!isset($startPosition))
		{
			$isSupportReturnToStart = true;
			$startPosition = ftell($resource);
		}
		else
		{
			fseek($resource, $startPosition);
		}

		$currentPosition = $startPosition;
		$bufferPosition = $currentPosition;
		$buffer = '';
		$isEndOfFileReached = false;
		$searchCount = count($searchList);
		$foundCount = 0;
		$isAllFound = false;
		$result = [];

		do
		{
			$iterationBuffer = fread($resource, $bufferLength);
			$buffer .= $iterationBuffer;

			foreach ($searchList as $searchKey => $searchVariant)
			{
				if (!isset($result[$searchKey]))
				{
					$variantPosition = Market\Export\Run\Helper\BinaryString::getPosition($buffer, $searchVariant);

					if ($variantPosition !== false)
					{
						$result[$searchKey] = $bufferPosition + $variantPosition;
						$foundCount++;

						$isAllFound = ($searchCount === $foundCount);
					}
				}
			}

			if ($stopSearch !== null)
			{
				$stopPosition = Market\Export\Run\Helper\BinaryString::getPosition($buffer, $stopSearch);

				if ($stopPosition !== false)
				{
					$stopPosition += $bufferPosition;

					foreach ($result as $searchKey => $position)
					{
						if ($position > $stopPosition)
						{
							unset($result[$searchKey]);
						}
					}

					break;
				}
			}

			if ($isAllFound)
			{
				break;
			}

			$buffer = $iterationBuffer;
			$bufferPosition = $currentPosition;
			$currentPosition += $bufferLength;

			if (!$isEndOfFileReached && feof($resource))
			{
				if ($isSupportReturnToStart)
				{
					$isEndOfFileReached = true;
					$bufferPosition = 0;
					$currentPosition = 0;
					$buffer = '';

					fseek($resource, 0);
				}
				else
				{
					break;
				}
			}
		}
		while (!$isEndOfFileReached || $currentPosition < $startPosition);

		return $result;
	}

	/**
	 * @return resource
	 */
	protected function getFileResource()
	{
		if (!isset($this->fileResource))
		{
			CheckDirPath($this->filePath);

			if (!file_exists($this->filePath))
			{
				touch($this->filePath);
				chmod($this->filePath, BX_FILE_PERMISSIONS);
			}
			else if (!is_writable($this->filePath))
			{
				chmod($this->filePath, BX_FILE_PERMISSIONS);
			}

			$this->fileResource = fopen($this->filePath, 'rb+');

			if ($this->fileResource === false)
			{
				throw new Main\SystemException(Market\Config::getLang('EXPORT_RUN_WRITER_FILE_CANT_OPEN_FILE'));
			}
		}

		return $this->fileResource;
	}

	protected function releaseFileResource()
	{
		if (isset($this->fileResource))
		{
			fflush($this->fileResource);
			fclose($this->fileResource);

			$this->fileResource = null;
		}
	}

	protected function releaseTempResource()
	{
		if (isset($this->tempResource))
		{
			fclose($this->tempResource);

			$this->tempResource = null;
		}
	}

	protected function getTempResource()
	{
		if (isset($this->tempResource))
		{
			ftruncate($this->tempResource, 0);
			fseek($this->tempResource, 0);
		}
		else
		{
			$path = $this->getTempPath();
			$this->tempResource = fopen($path, 'rb+');

			if ($this->tempResource === false)
			{
				throw new Main\SystemException(Market\Config::getLang('EXPORT_RUN_WRITER_FILE_CANT_OPEN_TEMP'));
			}
		}

		return $this->tempResource;
	}

	protected function getTempPath()
	{
		$useMemory = (Market\Config::getOption('export_writer_memory', 'N') === 'Y');

		return $useMemory ? 'php://memory' : 'php://temp';
	}

	protected function getBufferLength()
	{
		if ($this->bufferLength === null)
		{
			$this->bufferLength = (int)Market\Config::getOption('export_run_writer_file_buffer_length');

			if ($this->bufferLength <= 0)
			{
				$this->bufferLength = static::BUFFER_LENGTH;
			}
		}

		return $this->bufferLength;
	}

	protected function fileWrite($resource, $contents, $totalLength = null)
	{
		if ($totalLength === null)
		{
			$totalLength = Market\Export\Run\Helper\BinaryString::getLength($contents);
		}

		$failCount = 0;
		$limitFail = 3;
		$readyLength = 0;

		do
		{
			$loopContents = $contents;

			if ($readyLength > 0)
			{
				$loopContents = Market\Export\Run\Helper\BinaryString::getSubstring($contents, $readyLength, $totalLength);
			}

			$loopLength = fwrite($resource, $loopContents);

			if ($loopLength === false)
			{
				throw new Main\SystemException(Market\Config::getLang('EXPORT_RUN_WRITER_FILE_CANT_WRITE_FILE'));
			}

			if ($loopLength <= 0)
			{
				$failCount++;

				if ($failCount >= $limitFail)
				{
					throw new Main\SystemException(Market\Config::getLang('EXPORT_RUN_WRITER_FILE_CANT_WRITE_FILE'));
				}
			}
			else
			{
				$readyLength += $loopLength;
			}
		}
		while ($readyLength < $totalLength);
	}

	protected function streamCopy($fromResource, $toResource, $maxLength = null, $offset = null)
	{
		if ($offset !== null)
		{
			if ($maxLength === null) { $maxLength = -1; }

			$copyResult = stream_copy_to_stream($fromResource, $toResource, $maxLength, $offset);
		}
		else if ($maxLength !== null)
		{
			$copyResult = stream_copy_to_stream($fromResource, $toResource, $maxLength);
		}
		else
		{
			$copyResult = stream_copy_to_stream($fromResource, $toResource);
		}

		if ($copyResult === false)
		{
			throw new Main\SystemException(Market\Config::getLang('EXPORT_RUN_WRITER_STREAM_COPY_FAILED'));
		}
	}
}