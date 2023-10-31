<?php
namespace Yandex\Market\Export\Collection\Strategy;

use Yandex\Market\Export\Collection\Data;

interface Strategy
{
	public function getTitle();

	public function getFields();

	public function setValues(array $values);

	/** @return Data\FeedCollection[] */
	public function getFeedCollections();
}