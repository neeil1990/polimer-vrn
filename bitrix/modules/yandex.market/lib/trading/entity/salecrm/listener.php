<?php

namespace Yandex\Market\Trading\Entity\SaleCrm;

use Bitrix\Main;
use Yandex\Market\Trading\Entity as TradingEntity;

class Listener extends TradingEntity\Sale\Listener
{
	protected static function isAdminPage($path)
	{
		if (
			preg_match('#/crm\.(order|deal)\..+?/#', $path) // is components namespace crm.order or crm.deal
			&& preg_match('#ajax\.php$#', $path) // ajax page
		)
		{
			$result = true;
		}
		else
		{
			$result = parent::isAdminPage($path);
		}

		return $result;
	}

	protected static function isAdminController(Main\Request $request)
	{
		return (
			$request->getRequestedPage() === '/bitrix/services/main/ajax.php'
			&& is_string($request->get('action'))
			&& preg_match('#^crm\.(order|deal|api)\.#', $request->get('action'))
		);
	}

	public function bind()
	{
		$this->unbindParent();
		parent::bind();
	}

	protected function unbindParent()
	{
		$parent = new TradingEntity\Sale\Listener($this->environment);
		$parent->unbind();
	}
}