<?if(!check_bitrix_sessid()) return;?>

<?global $APPLICATION;
$APPLICATION->SetTitle(GetMessage("ARTURGOLUBEV_WILDBERRIES_UNINSTALL_SUCCESS", array("#MODULE_NAME#" => GetMessage("arturgolubev.wildberries_MODULE_NAME"))));
?>

<?echo CAdminMessage::ShowNote(GetMessage("ARTURGOLUBEV_WILDBERRIES_INSTALL_SUCCESS", array("#MOD_NAME#"=>GetMessage("arturgolubev.wildberries_MODULE_NAME"))));?>

<h3><?=GetMessage("ARTURGOLUBEV_WILDBERRIES_WHAT_DO");?></h3>

<div><?=GetMessage("ARTURGOLUBEV_WILDBERRIES_WHAT_DO_TEXT_UN");?></div>