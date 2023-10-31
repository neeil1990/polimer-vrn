<?php

namespace Darneo\Ozon\Install;

use Darneo\Ozon\Main\Table\SettingsCronTable;

class SettingsCron
{
    private array $defaultValue;

    public function __construct()
    {
        $this->defaultValue = [
            [
                'CODE' => 'IMPORT_ANALYTIC',
                'SORT' => 1,
                'VALUE' => true
            ],
            [
                'CODE' => 'IMPORT_CATALOG',
                'SORT' => 2,
                'VALUE' => true
            ],
            [
                'CODE' => 'IMPORT_CORE',
                'SORT' => 3,
                'VALUE' => false
            ],
            [
                'CODE' => 'EXPORT_CATALOG',
                'SORT' => 4,
                'VALUE' => true
            ],
            [
                'CODE' => 'EXPORT_PRICE',
                'SORT' => 5,
                'VALUE' => true
            ],
            [
                'CODE' => 'EXPORT_STOCK',
                'SORT' => 6,
                'VALUE' => true
            ],

        ];
    }

    public function update(): void
    {
        foreach ($this->defaultValue as $value) {
            if ($row = SettingsCronTable::getById($value['CODE'])->fetch()) {
                unset($value['CODE'], $value['VALUE']);
                SettingsCronTable::update($row['CODE'], $value);
            } else {
                SettingsCronTable::add($value);
            }
        }
    }

    public function setValue(): void
    {
        if (SettingsCronTable::getCount() === 0) {
            foreach ($this->defaultValue as $value) {
                SettingsCronTable::add($value);
            }
        }
    }
}
