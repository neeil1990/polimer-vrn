<?php

namespace Yandex\Market\Data\Trading;

class MeaningfulStatus
{
	const CREATED = 'CREATED';
	const PROCESSING = 'PROCESSING';
	const CANCELED = 'CANCELED';
	const ALLOW_DELIVERY = 'ALLOW_DELIVERY';
	const SUBSIDY = 'SUBSIDY';
	const PAYED = 'PAYED';
	const DEDUCTED = 'DEDUCTED';
	const FINISHED = 'FINISHED';
}