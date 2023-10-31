<?php

namespace Yandex\Market\Export\Xml\Listing;

interface Listing
{
	/** @return array */
	public function values();

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public function display($value);

	/**
	 * @param string $value
	 *
	 * @return string[]
	 */
	public function synonyms($value);
}