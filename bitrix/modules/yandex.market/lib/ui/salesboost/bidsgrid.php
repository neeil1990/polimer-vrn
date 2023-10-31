<?php
namespace Yandex\Market\Ui\SalesBoost;

use Bitrix\Main;
use Yandex\Market\Component;
use Yandex\Market\Reference\Concerns;
use Yandex\Market\Trading;
use Yandex\Market\Ui;
use Yandex\Market\SalesBoost;

class BidsGrid extends Ui\Reference\Page
{
	use Concerns\HasMessage;

	protected $businessSelector;

	public function __construct(Main\HttpRequest $request = null)
	{
		parent::__construct($request);

		$this->businessSelector = new Ui\Trading\Molecules\BusinessSelector('yamarket_boost_bids', $request);
	}

	public function setTitle()
	{
		global $APPLICATION;

		$APPLICATION->SetTitle(self::getMessage('TITLE'));
	}

	protected function getReadRights()
	{
		return Ui\Access::RIGHTS_PROCESS_TRADING;
	}

	public function show()
	{
		try
		{
			$setup = $this->businessSelector->selected();

			$this->businessSelector->show($setup);
			$this->showGrid($setup);
		}
		catch (Main\ObjectException $exception)
		{
			$this->businessSelector->show(null, true);
			$this->showError($exception->getMessage());
		}
		catch (Main\ObjectNotFoundException $exception)
		{
			$this->businessSelector->show(null, true);
			$this->showError($exception->getMessage());
		}
	}

	protected function showGrid(Trading\Business\Model $business)
	{
		global $APPLICATION;

		$APPLICATION->IncludeComponent('yandex.market:admin.grid.list', '', [
			'GRID_ID' => 'YANDEX_MARKET_ADMIN_SALES_BOOST_BIDS',
			'PROVIDER_TYPE' => 'SalesBoostBid',
			'CONTEXT_MENU_EXCEL' => 'Y',
			'BUSINESS_ID' => $business->getId(),
			'BUSINESS_MODEL' => $business,
			'BASE_URL' => $this->componentBaseUrl($business),
			'PAGER_FIXED' => Component\SalesBoostBid\GridList::PAGE_SIZE,
			'DEFAULT_FILTER_FIELDS' => [
				'SKU',
			],
			'LIST_FIELDS' => [
				'SKU',
				'BID',
				'BID_RECOMMENDATION',
				'PRICE_RECOMMENDATION',
				'BOOST',
			],
			'DEFAULT_LIST_FIELDS' => [
				'SKU',
				'BID',
				'BID_RECOMMENDATION',
				'PRICE_RECOMMENDATION',
				'BOOST',
			],
			'CHECK_ACCESS' => !Ui\Access::isWriteAllowed(),
		]);
	}

	protected function componentBaseUrl(Trading\Business\Model $business)
	{
		global $APPLICATION;

		return $APPLICATION->GetCurPage() . '?' . http_build_query([
			'lang' => LANGUAGE_ID,
			'business' => $business->getId(),
		]);
	}

	protected function showError($message)
	{
		\CAdminMessage::ShowMessage([
			'TYPE' => 'ERROR',
			'MESSAGE' => $message,
		]);
	}
}