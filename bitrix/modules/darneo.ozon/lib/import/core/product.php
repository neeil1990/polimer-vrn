<?

namespace Darneo\Ozon\Import\Core;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Darneo\Ozon\Import\Table\ConnectionOfferProductTable;
use Darneo\Ozon\Import\Table\ProductListTable;

class Product extends Base
{
    public function start(): void
    {
        $this->importConnectionOfferProduct();
        $productIds = $this->getProductIds();
        foreach ($productIds as $productId) {
            $import = new \Darneo\Ozon\Api\v2\Product();
            $data = $import->info($productId);
            if (!$data['result']) {
                $this->errors[] = Loc::getMessage(
                    'DARNEO_OZON_IMPORT_CORE_PRODUCT_ERROR_IMPORT',
                    [
                        '#PRODUCT_ID#' => $productId,
                        '#ANSWER#' => Json::encode($data),
                    ]
                );
                continue;
            }
            $dataResult = $data['result'];
            $result = ProductListTable::add(
                [
                    'ID' => $dataResult['id'],
                    'OFFER_ID' => $dataResult['offer_id'],
                    'NAME' => $dataResult['name'],
                    'STATUS_CODE' => $dataResult['status']['state'],
                    'STATUS_NAME' => $dataResult['status']['state_name'],
                    'CATEGORY_ID' => $dataResult['category_id'],
                    'IS_ERROR' => !empty($dataResult['status']['item_errors']),
                    'JSON' => $data['result'],
                ]
            );
            if (!$result->isSuccess()) {
                $this->errors[] = array_merge($this->errors, $result->getErrorMessages());
            }
        }
        $this->importStocks($productIds);
    }

    private function importConnectionOfferProduct(): void
    {
        $import = new \Darneo\Ozon\Api\v2\Product();
        $lastId = '';
        updateValue:
        $data = $import->list($lastId);
        if ($data['result']['items']) {
            foreach ($data['result']['items'] as $item) {
                if (ConnectionOfferProductTable::getById($item['offer_id'])->fetch()) {
                    ConnectionOfferProductTable::update(
                        $item['offer_id'],
                        [
                            'PRODUCT_ID' => $item['product_id']
                        ]
                    );
                } else {
                    ConnectionOfferProductTable::add(
                        [
                            'OFFER_ID' => $item['offer_id'],
                            'PRODUCT_ID' => $item['product_id']
                        ]
                    );
                }
            }
            $lastId = $data['result']['last_id'];
            goto updateValue;
        }
    }

    private function getProductIds(): array
    {
        $rows = [];
        $parameters = [
            'select' => ['PRODUCT_OZON_ID'],
        ];
        $result = ConnectionOfferProductTable::getList($parameters);
        while ($row = $result->fetch()) {
            $rows[] = $row['PRODUCT_OZON_ID'];
        }

        return $rows;
    }

    private function importStocks(array $productIds): void
    {
        $import = new \Darneo\Ozon\Api\v3\Product();
        $lastId = '';
        updateValue:
        $data = $import->infoStocks($productIds, $lastId);
        if ($data['result']['items']) {
            foreach ($data['result']['items'] as $item) {
                foreach ($item['stocks'] as $stock) {
                    switch ($stock['type']) {
                        case 'fbs':
                            ProductListTable::update(
                                $item['product_id'],
                                [
                                    'STOCK_FBS' => $stock['present'] ?: 0,
                                    'STOCK_FBS_RESERVED' => $stock['reserved'] ?: 0,
                                ]
                            );
                            break;
                        case 'fbo':
                            ProductListTable::update(
                                $item['product_id'],
                                [
                                    'STOCK_FBO' => $stock['present'] ?: 0,
                                    'STOCK_FBO_RESERVED' => $stock['reserved'] ?: 0,
                                ]
                            );
                            break;
                    }
                }
            }
            $lastId = $data['result']['last_id'];
            goto updateValue;
        }
    }
}
