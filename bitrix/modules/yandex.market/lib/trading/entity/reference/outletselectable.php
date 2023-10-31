<?php

namespace Yandex\Market\Trading\Entity\Reference;

interface OutletSelectable
{
	/**
	 * @param Order $order
	 * @param int $deliveryId
	 * @param string $code
	 */
	public function selectOutlet(Order $order, $deliveryId, $code);
}