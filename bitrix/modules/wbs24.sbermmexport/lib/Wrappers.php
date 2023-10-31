<?php
namespace Wbs24\Sbermmexport;

use Bitrix\Main\Loader;

class Wrappers
{
    public $lastError;

	public function __construct($objects = [])
    {
		$this->getObjects($objects);
	}

    private function getObjects($objects)
    {
		try {
            if (
                !Loader::includeModule('catalog')
                || !Loader::includeModule('iblock')
            ) throw new SystemException('Necessary modules don`t loaded');

            $needObjects = [
                'CFile',
                'CCatalog',
                'CIBlock',
                'CIBlockElement',
                'CIBlockProperty',
                'StoreProductTable',
                'StoreTable',
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
