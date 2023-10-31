<?
namespace Sotbit\Seometa\Link;

use Bitrix\Iblock\Template\Engine;
use Bitrix\Iblock\Template\Entity\Section;
use Sotbit\Seometa\SeoMetaMorphy;

class TagWriter extends AbstractWriter
{
    private static $Writer = false;
    private $WorkingConditions;

    private $countTagWrite;
    private $countTagWrited = 0;

    private function __construct(
        $WorkingConditions,
        $countTagsForWrite
    ) {
        $this->WorkingConditions = $WorkingConditions;
        $this->countTagWrite = $countTagsForWrite;
    }

    public static function getInstance(
        $WorkingConditions,
        $countTagsForWrite
    ) {
        if (self::$Writer === false) {
            self::$Writer = new TagWriter($WorkingConditions, $countTagsForWrite);
        }

        return self::$Writer;
    }

    public function AddRow(
        array $arFields
    ) {}

    public function Write(
        array $arFields
    ) {
        $morphyObject = SeoMetaMorphy::morphyLibInit();
        $sku = new Section($arFields['section_id']);
        \CSeoMetaTagsProperty::$params = $arFields['properties'];
        $conditionTag = $this->arCondition['TAG'];
        if ($arFields['strict_relinking'] != 'Y') {
            $Title = Engine::process($sku,SeoMetaMorphy::prepareForMorphy($this->arCondition['TAG']));
        } elseif (in_array($this->arCondition['ID'], $this->WorkingConditions) && $conditionTag) {
            $Title = Engine::process($sku, SeoMetaMorphy::prepareForMorphy($conditionTag));
        }
        $rsSites = \CSite::GetById($arFields['site_id']);
        $arSite = $rsSites->Fetch();
        $arSiteDir = substr($arSite['DIR'], 0, -1);
        if(!empty($Title)) {
            $Title = SeoMetaMorphy::convertMorphy($Title, $morphyObject);
            $this->data[] = [
                'URL' => trim($arSiteDir . $arFields['real_url']),
                'REAL_URL' => trim($arSiteDir . $arFields['real_url']),
                'SORT' => '100',
                'TITLE' => trim($Title),
                'PRODUCT_COUNT' => $arFields['product_count'],
                'SITE_ID'=> $arFields['site_id']
            ];
            return true;
        }
        return false;
    }

    public function getData(

    ) {
        return $this->data;
    }
}
