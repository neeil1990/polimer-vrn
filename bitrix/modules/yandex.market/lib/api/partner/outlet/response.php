<?php

namespace Yandex\Market\Api\Partner\Outlet;

use Yandex\Market;

class Response extends Market\Api\Partner\Reference\Response
{
	use Market\Reference\Concerns\HasOnce;

	/** @return Market\Api\Model\Outlet */
	public function getOutlet()
	{
		return $this->once('loadOutlet');
	}

	protected function loadOutlet()
	{
		$data = (array)$this->getField('outlet');

		return new Market\Api\Model\Outlet($data);
	}
}
