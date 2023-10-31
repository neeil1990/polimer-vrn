<?php

namespace Yandex\Market\Type;

use Yandex\Market;
use Yandex\Market\Export\Xml;
use Yandex\Market\Reference\Concerns;

/** @noinspection PhpUnused */
class EnumType extends AbstractType
{
	use Concerns\HasMessage;

	protected $synonymCache = [];

	public function validate($value, array $context = [], Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		if (!is_string($value))
		{
			if ($nodeResult !== null)
			{
				$nodeResult->registerError(self::getMessage('ERROR_TYPE', [
					'#TYPE#' => gettype($value),
				]), 'TYPE');
			}

			return false;
		}

		$listing = $this->listing($node);
		$sanitized = $this->sanitize($listing, $value);

		if ($sanitized === null)
		{
			if ($nodeResult !== null)
			{
				$nodeResult->registerError(self::getMessage('ERROR_INVALID', [
					'#VALUE#' => $value,
				]), 'INVALID');
			}

			return false;
		}

		if (in_array($value, $this->skip($node), true))
		{
			if ($nodeResult !== null) { $nodeResult->invalidate(); }

			return false;
		}

		return true;
	}

	public function format($value, array $context = [], Xml\Reference\Node $node = null, Market\Result\XmlNode $nodeResult = null)
	{
		if (!is_string($value)) { return null; }

		$listing = $this->listing($node);

		return $this->sanitize($listing, $value);
	}

	/**
	 * @param Xml\Reference\Node|null $node
	 *
	 * @return Xml\Listing\Listing
	 */
	protected function listing(Xml\Reference\Node $node = null)
	{
		Market\Reference\Assert::notNull($node, 'node');

		$listing = $node->getParameter('value_listing');

		Market\Reference\Assert::notNull($listing, 'value_listing');
		Market\Reference\Assert::typeOf($listing, Xml\Listing\Listing::class, 'value_listing');

		return $listing;
	}

	/**
	 * @param Xml\Reference\Node|null $node
	 *
	 * @return array
	 */
	protected function skip(Xml\Reference\Node $node = null)
	{
		Market\Reference\Assert::notNull($node, 'node');

		$skip = $node->getParameter('value_skip');

		return is_array($skip) ? $skip : [];
	}

	/**
	 * @param Xml\Listing\Listing $listing
	 * @param string $value
	 *
	 * @return string|null
	 */
	protected function sanitize(Xml\Listing\Listing $listing, $value)
	{
		if ($this->matchBuiltIn($listing, $value)) { return $value; }

		$migrated = $this->searchMigrated($listing, $value);

		if ($migrated !== null) { return $migrated; }

		return $this->searchSynonym($listing, $value);
	}

	protected function matchBuiltIn(Xml\Listing\Listing $listing, $value)
	{
		return in_array($value, $listing->values(), true);
	}

	protected function searchMigrated(Xml\Listing\Listing $listing, $value)
	{
		if (!($listing instanceof Xml\Listing\ListingWithMigration)) { return null; }

		return $listing->migrate($value);
	}

	protected function searchSynonym(Xml\Listing\Listing $listing, $value)
	{
		$result = null;

		foreach ($listing->values() as $variant)
		{
			if ($this->matchSynonym($listing, $variant, $value))
			{
				$result = $variant;
				break;
			}
		}

		return $result;
	}

	protected function matchSynonym(Xml\Listing\Listing $listing, $variant, $value)
	{
		$synonyms = $this->listingSynonyms($listing, $variant);
		$value = Market\Data\TextString::toLower($value);

		if (in_array($value, $synonyms, true)) { return true; }

		$result = false;

		foreach ($synonyms as $synonym)
		{
			if (Market\Data\TextString::getPosition($value, $synonym) !== false)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	protected function listingSynonyms(Xml\Listing\Listing $listing, $variant)
	{
		$cacheKey = get_class($listing) . ':' . $variant;

		if (!isset($this->synonymCache[$cacheKey]))
		{
			$synonyms = $listing->synonyms($variant);

			if (empty($synonyms)) { $synonyms = [ $listing->display($variant) ]; }

			$this->synonymCache[$cacheKey] = array_map(
				static function($synonym) { return Market\Data\TextString::toLower($synonym); },
				$synonyms
			);
		}

		return $this->synonymCache[$cacheKey];
	}
}