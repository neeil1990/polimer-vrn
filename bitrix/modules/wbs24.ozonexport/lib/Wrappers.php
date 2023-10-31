<?php
namespace Wbs24\Ozonexport;

use Bitrix\Main\Loader;

class Wrappers
{
    public $lastError;

	function __construct($objects = [])
    {
		$this->getObjects($objects);
	}

    private function getObjects($objects)
    {
		try {
            if (!Loader::includeModule('catalog')
                || !Loader::includeModule('iblock')
            ) throw new SystemException('Necessary modules don`t loaded');

            $needObjects = [
                // для ozon_run.php и Filter.php
                'CCatalogSku',
                'CIBlock',
                //'CIBlockProperty',
                //'SiteTable',
                //'Option',
                //'PropertyTable',
                //'CCatalog',
                //'CIBlockSection',
                //'GroupAccessTable',
                //'VatTable',
                //'CurrencyManager',
                //'Calculation',
                //'CIBlockPriceTools',
                //'SectionElementTable',
                'CIBlockElement',
                //'CCatalogDiscount',
                //'CCatalogProduct',
                //'PriceTable',
                //'DiscountManager',

                // для ExtendWarehouse.php
                'StoreProductTable',
                'StoreTable',

                // для Admin.php
                'CIBlockProperty',
                'CCatalog',
                'CCatalogGroup',

                // для Update.php
                'CUpdateClientPartner',
            ];

			foreach ($needObjects as $obj) {
				if (!empty($objects[$obj])) {
					$this->{$obj} = $objects[$obj];
				} else {
					$className = __NAMESPACE__."\\".$obj;
					$this->{$obj} = new $className();
				}
			}
		} catch (SystemException $exception) {
			$this->exceptionHandler($exception);
		}
    }

	private function exceptionHandler($exception) {
		$this->lastError = $exception->getMessage();
	}
}
