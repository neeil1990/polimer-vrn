<?php

namespace Darneo\Ozon\Import\Product;

use Darneo\Ozon\Api\v2\Product;
use Darneo\Ozon\Import\Table\ConnectionOfferProductTable;

class Connect extends Base
{
    public static function agentStart(): string
    {
        (new self())->start();

        return '\Darneo\Ozon\Import\Product\Connect::agentStart();';
    }

    public function start(): void
    {
        $import = new Product();
        $lastId = '';
        updateValue:
        $data = $import->list($lastId);
        if ($data['result']['items']) {
            foreach ($data['result']['items'] as $item) {
                if ($row = ConnectionOfferProductTable::getById($item['offer_id'])->fetch()) {
                    if ($row['PRODUCT_OZON_ID'] !== $item['product_id']) {
                        ConnectionOfferProductTable::update(
                            $item['offer_id'],
                            [
                                'PRODUCT_OZON_ID' => $item['product_id']
                            ]
                        );
                    }
                } else {
                    ConnectionOfferProductTable::add(
                        [
                            'OFFER_ID' => $item['offer_id'],
                            'PRODUCT_OZON_ID' => $item['product_id']
                        ]
                    );
                }
            }
            $lastId = $data['result']['last_id'];
            goto updateValue;
        }
    }
}
