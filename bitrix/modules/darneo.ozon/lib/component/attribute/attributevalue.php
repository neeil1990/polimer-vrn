<?php

namespace Darneo\Ozon\Component\Attribute;

use Darneo\Ozon\Import\Table\ConnectionPropValueTable;

class AttributeValue extends Base
{
    private int $categoryId;
    private int $propertyId;

    public function __construct(int $categoryId, int $propertyId)
    {
        $this->categoryId = $categoryId;
        $this->propertyId = $propertyId;
        ConnectionPropValueTable::setTablePrefix($categoryId);
    }

    public function get(): array
    {
        $list = $this->getList();
        $isPageStop = $this->page * $this->limit >= $this->totalCount;

        return [
            'LIST' => $list,
            'PAGE' => $this->page,
            'FINAL_PAGE' => $isPageStop,
            'FILTER' => [
                'SEARCH' => $this->filterSearch
            ]
        ];
    }

    private function getList(): array
    {
        $rows = [];

        $parameters = [
            'filter' => [
                'PROPERTY_ID' => $this->propertyId
            ],
            'select' => [
                'VALUE_ID',
                'PROPERTY_ID',
                'VALUE_NAME' => 'VALUE.VALUE',
                'VALUE_INFO' => 'VALUE.INFO',
                'VALUE_PICTURE' => 'VALUE.PICTURE',
            ],
            'order' => [
                'VALUE_NAME' => 'ASC'
            ],
            'limit' => $this->page * $this->limit,
        ];

        if ($this->filterSearch) {
            $parameters['filter']['%VALUE.VALUE'] = $this->filterSearch;
        }

        $result = ConnectionPropValueTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = [
                'ID' => $row['VALUE_ID'],
                'PROPERTY_ID' => $row['PROPERTY_ID'],
                'NAME' => trim($row['VALUE_NAME']),
                'INFO' => mb_strtolower(trim($row['VALUE_INFO'])),
                'PICTURE' => $row['VALUE_PICTURE'],
            ];
        }

        $this->initCountAll($parameters['filter']);

        return $rows;
    }

    private function initCountAll(array $filter): void
    {
        $this->setTotalCount(ConnectionPropValueTable::getCount($filter));
    }
}
