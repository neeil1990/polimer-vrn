<?php

namespace Darneo\Ozon\Order\Fbs;

use Darneo\Ozon\Api\v3\Posting;
use Darneo\Ozon\Main\Helper\Date as HelperDate;
use Darneo\Ozon\Order\Table\FbsListTable;

class Import
{
    private array $errors = [];

    public function initData(int $page = 1, int $limit = 1000, array $filter = []): bool
    {
        $this->importWarehouse();
        $import = new Posting();
        $offset = $page > 1 ? $page * $limit : 0;
        $data = $import->fbsList($limit, $offset, $filter);

        if ($data['result']) {
            foreach ($data['result']['postings'] as $item) {
                $dateCreate = $item['in_process_at'] ? HelperDate::getFromImport($item['in_process_at']) : '';
                $dateShipment = $item['shipment_date'] ? HelperDate::getFromImport($item['shipment_date']) : '';
                $dateDelivery = $item['delivering_date'] ? HelperDate::getFromImport($item['delivering_date']) : '';
                $fields = [
                    'ID' => $item['posting_number'],
                    'POSTING_NUMBER' => $item['posting_number'],
                    'ORDER_ID' => $item['order_id'],
                    'ORDER_NUMBER' => $item['order_number'],
                    'STATUS' => $item['status'],
                    'DELIVERY_METHOD' => $item['delivery_method'],
                    'WAREHOUSE_ID' => $item['delivery_method']['warehouse_id'],
                    'TRACKING_NUMBER' => $item['tracking_number'],
                    'TPL_INTEGRATION_TYPE' => $item['tpl_integration_type'],
                    'CANCELLATION' => $item['cancellation'],
                    'CUSTOMER' => $item['customer'],
                    'PRODUCTS' => $item['products'],
                    'ADDRESSEE' => $item['addressee'],
                    'BARCODES' => $item['barcodes'],
                    'ANALYTICS_DATA' => $item['analytics_data'],
                    'FINANCIAL_DATA' => $item['financial_data'],
                    'IS_EXPRESS' => $item['is_express'],
                    'REQUIREMENTS' => $item['requirements'],
                ];
                if ($dateCreate) {
                    $fields['IN_PROCESS_AT'] = $dateCreate;
                }
                if ($dateShipment) {
                    $fields['SHIPMENT_DATE'] = $dateShipment;
                }
                if ($dateDelivery) {
                    $fields['DELIVERY_DATE'] = $dateDelivery;
                }

                if ($rowId = FbsListTable::getById($item['posting_number'])->fetch()['ID']) {
                    unset($fields['ID']);
                    $result = FbsListTable::update($rowId, $fields);
                } else {
                    $result = FbsListTable::add($fields);
                }

                if (!$result->isSuccess()) {
                    $this->errors[] = $result->getErrorMessages();
                }
            }
            $this->errors = array_merge(...$this->errors);
            return $data['result']['has_next'];
        }

        if ($data['message']) {
            $this->errors[] = $data['message'];
        }

        return false;
    }

    private function importWarehouse(): void
    {
        (new \Darneo\Ozon\Import\Warehouse\Import())->initData();
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getDataCount(): int
    {
        return FbsListTable::getCount();
    }
}
