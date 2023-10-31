<?php
namespace Yandex\Market\Export;

use Yandex\Market\Glossary as GlobalGlossary;

class Glossary
{
	const SERVICE_SELF = GlobalGlossary::SERVICE_EXPORT;

	const ENTITY_SETUP = GlobalGlossary::ENTITY_SETUP;
	const ENTITY_OFFER = GlobalGlossary::ENTITY_OFFER;
	const ENTITY_PROMO = 'promo';
	const ENTITY_GIFT = 'gift';
	const ENTITY_COLLECTION = 'collection';
	const ENTITY_CURRENCY = 'currency';
	const ENTITY_CATEGORY = 'category';
}