<?
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

$arFields = \SotbitSchema::getArFields();
//-----------------------------------------------------------------------------------------------------------------------------------------------
$aTabs = array(
    array(
        "DIV" => $_POST['entity'],
        "ICON" => "main_user_edit",
    ),);
$langPrefix = 'SOTBIT_SCHEMA_RATING_';
$tabControl = new CAdminForm("tabControl", $aTabs);

$tabControl->AddSection("OG_COMMON_SETTINGS", GetMessage($langPrefix."SETTINGS"));
$tabControl->BeginCustomField("type_BLOCK", '', false);
?>
    <tr class="adm-detail-file-row">
        <td colspan="2" style="text-align: center">
            <input type="hidden" name="<?=$key ? $key."[@type]" : "@type"?>" value="Rating"/>
        </td>
    </tr>
<?
$tabControl->EndCustomField("type_BLOCK");
require (dirname(__FILE__).'/blocks/rating.php');
require (dirname(__FILE__).'/blocks/thing.php');
$tabControl->Show();
?>