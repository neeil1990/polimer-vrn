<?php

namespace Yandex\Market\Export\Run\Writer;

abstract class Base
{
    const POSITION_BEFORE = 'before';
    const POSITION_PREPEND = 'prepend';
    const POSITION_APPEND = 'append';
    const POSITION_AFTER = 'after';

	protected $parameters = [];
	protected $offerParentName;

	public function __construct($parameters = [])
	{
		$this->parameters = $parameters;
	}

	public function destroy()
	{}

	abstract public function getPath();

	abstract public function refresh();

	abstract public function lock($isBlocked = false);

	abstract public function unlock();

	abstract public function move($path);

	abstract public function copy($fromPath);

	abstract public function remove();

	abstract public function getPointer();

	abstract public function setPointer($position);

	abstract public function writeRoot($element, $header = '');

	abstract public function writeTagList($elementList, $parentName, $position = null);

	abstract public function writeTag($element, $parentName, $position = null);

	abstract public function updateAttribute($tagName, $id, $attributeList, $idAttr = 'id');

	abstract public function updateTagList($tagName, $elementList, $idAttr = 'id', $isSelfClosed = null);

	abstract public function updateTag($tagName, $id, $element, $idAttr = 'id', $isSelfClosed = null);

	abstract public function searchTagList($tagName, $idList, $idAttr = 'id');

	abstract public function searchTag($tagName, $id, $idAttr = 'id');

	public function getParameter($key)
	{
		return (isset($this->parameters[$key]) ? $this->parameters[$key] : null);
	}
}