<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$this->setFrameMode(true);

$frame = $this->createFrame()->begin('');?>
	<?if(CModule::IncludeModule('arturgolubev.ecommerce') && $arResult["JS_CODE"]):?>
		<script><?=str_replace(array("\r\n", "\r", "\n"), '',  $arResult["JS_CODE"])?></script>
	<?endif;?>
<?$frame->end();?>