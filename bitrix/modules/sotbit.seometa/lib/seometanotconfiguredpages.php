<?php

namespace Sotbit\Seometa;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


class SeometaNotConfiguredPagesTable extends
    \DataManagerEx_SeoMeta
{
    private static array $metaInfoFields = [
        'META_ELEMENT_TITLE' => '',
        'META_ELEMENT_KEYWORDS' => '',
        'META_ELEMENT_DESCRIPTION' => '',
        'META_ELEMENT_PAGE_TITLE' => '',
        'META_ELEMENT_BREADCRUMB_TITLE' => '',
    ];

    private static array $mainFields = [
        'MAIN_TYPE_OF_INFOBLOCK' => '',
        'MAIN_INFOBLOCK' => '',
        'MAIN_SECTIONS' => ''
    ];

    public static function getTableName(
    ) {
        return 'b_sotbit_seometa_not_configured_pages';
    }

    public static function getMap(
    ) {
        return [
            new Entity\IntegerField('ID',
                [
                    'primary' => true,
                    'autocomplete' => true
                ]),
            new Entity\StringField('SITE_ID',
                [
                    'required' => true,
                    'title' => Loc::getMessage('SEOMETA_NOT_CONFIGURED_PAGES_SITE_ID')
                ]),
            new Entity\BooleanField('ACTIVE',
                [
                    'values' => [
                        'N',
                        'Y'
                    ],
                    'default_value' => 'N',
                    'title' => Loc::getMessage('SEOMETA_NOT_CONFIGURED_PAGES_ACTIVE')
                ]),
            new Entity\TextField('BEHAVIOR_FILTERED_PAGES',
                [
                    'required' => true,
                    'title' => Loc::getMessage('SEO_META_NOT_CONFIGURED_BEHAVIOR_FILTERED_PAGES'),
                    'default_value' => 'no_index'
                ]),
            new Entity\TextField('BEHAVIOR_PAGINATION_PAGES',
                [
                    'required' => true,
                    'title' => Loc::getMessage('SEO_META_NOT_CONFIGURED_BEHAVIOR_PAGINATION_PAGES'),
                    'default_value' => 'no_index'
                ]),
            new Entity\TextField('METAINFO_SETTINGS',
                [
                    'title' => Loc::getMessage('SEOMETA_NOT_CONFIGURED_PAGES_METAINFO_SETTINGS')
                ]),
            new Entity\TextField('MAIN_SETTINGS',
                [
                    'title' => Loc::getMessage('SEOMETA_NOT_CONFIGURED_PAGES_MAIN_SETTINGS')
                ])
        ];
    }

    public static function getBySiteID(
        string $SITE_ID
    ) {
        $result = self::getList([
            'filter' => [
                'SITE_ID' => $SITE_ID
            ]
        ])->fetch();

        if ($result['METAINFO_SETTINGS']) {
            $result = array_merge($result, (unserialize($result['METAINFO_SETTINGS']) ?: []));
        }

        if ($result['MAIN_SETTINGS']) {
            $result = array_merge($result, (unserialize($result['MAIN_SETTINGS']) ?: []));
        }

        unset($result['METAINFO_SETTINGS']);
        unset($result['MAIN_SETTINGS']);

        return $result;
    }

    public static function getDefaultParams(
    ): array {
        $result = [];

        foreach (self::getMap() as $field) {
            if($field->getColumnName() == 'METAINFO_SETTINGS') {
                $result = array_merge($result, self::$metaInfoFields);
            } elseif ($field->getColumnName() == 'MAIN_SETTINGS') {
                $result = array_merge($result, self::$mainFields);
            } else {
                $result[$field->getColumnName()] = $field->getDefaultValue();
            }
        }

        return $result;
    }
}