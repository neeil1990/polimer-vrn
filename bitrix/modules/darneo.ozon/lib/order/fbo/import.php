<?php

namespace Darneo\Ozon\Order\Fbo;

use Darneo\Ozon\Api\v2\Posting;
use Darneo\Ozon\Main\Helper\Date as HelperDate;
use Darneo\Ozon\Order\Table\FboListTable;

class Import
{
    private array $errors = [];

    public function initData(int $page = 1, int $limit = 1000, array $filter = []): bool
    {
        $import = new Posting();
        $offset = $page > 1 ? $page * $limit : 0;
        $data = $import->fboList($limit, $offset, $filter);
        if ($data['result']) {
            foreach ($data['result'] as $item) {
                $dateCreate = $item['created_at'] ? HelperDate::getFromImport($item['created_at']) : '';
                $dateUpdate = $item['in_process_at'] ? HelperDate::getFromImport($item['in_process_at']) : '';
                $fields = [
                    'ID' => $item['posting_number'],
                    'ORDER_ID' => $item['order_id'],
                    'ORDER_NUMBER' => $item['order_number'],
                    'POSTING_NUMBER' => $item['posting_number'],
                    'STATUS' => $item['status'],
                    'CANCEL_REASON_ID' => $item['cancel_reason_id'],
                    'PRODUCTS' => $item['products'],
                    'ANALYTICS' => $item['analytics_data'],
                    'FINANCIAL' => $item['financial_data'],
                ];
                if ($dateCreate) {
                    $fields['DATE_CREATED'] = $dateCreate;
                }
                if ($dateUpdate) {
                    $fields['DATE_UPDATE'] = $dateUpdate;
                }

                if ($rowId = FboListTable::getById($item['posting_number'])->fetch()['ID']) {
                    unset($fields['ID']);
                    $result = FboListTable::update($rowId, $fields);
                } else {
                    $result = FboListTable::add($fields);
                }

                if (!$result->isSuccess()) {
                    $this->errors[] = $result->getErrorMessages();
                }
            }
            $this->errors = array_merge(...$this->errors);
            return true;
        }

        if ($data['message']) {
            $this->errors[] = $data['message'];
        }

        return false;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getDataCount(): int
    {
        return FboListTable::getCount();
    }
}
