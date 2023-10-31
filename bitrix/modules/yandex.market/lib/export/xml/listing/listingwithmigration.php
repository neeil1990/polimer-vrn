<?php

namespace Yandex\Market\Export\Xml\Listing;

interface ListingWithMigration
{
	/**
	 * @param string $value
	 *
	 * @return string|null
	 */
	public function migrate($value);
}