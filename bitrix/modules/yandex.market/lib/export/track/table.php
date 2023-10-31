<?php
namespace Yandex\Market\Export\Track;

use Yandex\Market\Export\Glossary;
use Yandex\Market\Reference\Storage\TableDeprecated;
use Yandex\Market\Watcher;

/** @deprecated */
class Table extends Watcher\Track\SourceTable
	implements TableDeprecated
{
	const ENTITY_TYPE_SETUP = Glossary::ENTITY_SETUP;
	const ENTITY_TYPE_PROMO = Glossary::ENTITY_PROMO;
}