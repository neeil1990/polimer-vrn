<?php

namespace Sotbit\Seometa\Orm;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;

Loc::loadMessages(__FILE__);

/**
 * Class SeometaUrlTable
 * @package Sotbit\Seometa\Orm
 */
class ParseResultTable extends \DataManagerEx_SeoMeta
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_sotbit_seometa_parse_result';
    }

    public static function getMap()
    {
        return [
            'ID' => new ORM\Fields\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
            ]),
            'FILE_ID' => new ORM\Fields\IntegerField('FILE_ID', [
                'required' => true,
                'title' => Loc::getMessage('SEO_META_RESULT_FILE_ID'),
            ]),
            'ENTITY_ROW' => new ORM\Fields\TextField('ENTITY_ROW', [
                'title' => Loc::getMessage('SEO_META_RESULT_ENTITY_ROW'),
            ]),
            'ENTITY_NAME' => new ORM\Fields\TextField('ENTITY_NAME', [
                'title' => Loc::getMessage('SEO_META_RESULT_ENTITY_NAME'),
            ]),
            'MESSAGE' => new ORM\Fields\TextField('MESSAGE', [
                'title' => Loc::getMessage('SEO_META_RESULT_MESSAGE'),
            ]),
        ];
    }
}