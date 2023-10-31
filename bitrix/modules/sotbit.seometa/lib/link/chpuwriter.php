<?php
namespace Sotbit\Seometa\Link;

use Bitrix\Main\Type\DateTime;
use Sotbit\Seometa\Orm\SeometaUrlTable;

class ChpuWriter extends
    AbstractWriter
{
    private static $Writer = false;

    private function __construct(
        $id,
        $isProgress = false
    ) {
        $this->id = $id;
        if (!$isProgress) {
            SeometaUrlTable::deleteByOptions($id);
        }
    }

    public static function GetInstance(
        $Id,
        $isProgress = false
    ) {
        if (self::$Writer === false) {
            self::$Writer = new ChpuWriter($Id, $isProgress);
        }

        return self::$Writer;
    }

    public static function getWriterForAutogenerator(
        $id
    ) {
        self::$Writer = new ChpuWriter($id);
        return self::$Writer;
    }

    public function AddRow(
        array $arFields
    ) {
        $this->data[] = $arFields;
    }

    public function Write(
        array $arFields
    ) {
        $rsSites = \CSite::GetById($arFields['site_id']);
        $arSite = $rsSites->Fetch();
        $arSiteDir = substr($arSite['DIR'], 0, -1);

        $chpu['CONDITION_ID'] = $this->id;
        $chpu['REAL_URL'] = $arSiteDir . $arFields['real_url'];
        $chpu['ACTIVE'] = ($arFields['active'] == 'Y' ? $arFields['active'] : 'N');
        $chpu['NAME'] = $arFields['name'];
        $chpu['NEW_URL'] = $arSiteDir . $arFields['new_url'];
        $chpu['CATEGORY_ID'] = 0;
        $chpu['DATE_CHANGE'] = new DateTime( date( 'Y-m-d H:i:s' ), 'Y-m-d H:i:s' );
        $chpu['iblock_id'] = $arFields['iblock_id'];
        $chpu['section_id'] = $arFields['section_id'];
        $chpu['PROPERTIES'] = serialize( $arFields['properties'] );
        $chpu['PRODUCT_COUNT'] = $arFields['product_count'];
        $chpu['SITE_ID'] = $arFields['site_id'];

        $result = SeometaUrlTable::add($chpu);

        if ($result && $result->isSuccess()) {
            $result = $result->getId();
            $this->data[$result] = $chpu;

            return true;
        }

        return false;
    }

    public function getData(
    ) {
        return $this->data;
    }
}