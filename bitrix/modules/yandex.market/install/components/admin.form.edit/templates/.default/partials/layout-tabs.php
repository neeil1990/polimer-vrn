<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) { die(); }

use Yandex\Market;
use Bitrix\Main\Localization\Loc;

/** @var $component \Yandex\Market\Components\AdminFormEdit */

if (!empty($arResult['CONTEXT_MENU']))
{
	$context = new CAdminContextMenu($arResult['CONTEXT_MENU']);
	$context->Show();
}

if ($component->hasErrors())
{
	$component->showErrors();
}

$langNotifyUnsaved = [
	'MESSAGE' => Loc::getMessage('YANDEX_MARKET_T_ADMIN_FORM_EDIT_NOTIFY_UNSAVED_MESSAGE')
];

$tabControl = new \CAdminTabControl($arParams['FORM_ID'], $arResult['TABS'], false, true);

include __DIR__ . '/check-javascript.php';

$formActionUri = !empty($arParams['FORM_ACTION_URI'])
	? $arParams['FORM_ACTION_URI']
	: htmlspecialcharsbx($APPLICATION->GetCurPageParam());

?>
<form
	class="yamarket-form js-plugin"
	method="POST"
	action="<?= $formActionUri; ?>"
	enctype="multipart/form-data"
	data-plugin="Ui.Form.NotifyUnsaved"
	<?= $arResult['HAS_REQUEST'] ? 'data-changed="true"' : ''; ?>
>
	<?php
	if ($arParams['FORM_BEHAVIOR'] === 'steps')
	{
		?>
		<input type="hidden" name="STEP" value="<?= $arResult['STEP']; ?>" />
		<?php
	}

	$tabControl->Begin();

	if ($arParams['USE_METRIKA'] === 'Y')
	{
		Market\Metrika::load();
	}

	Market\Ui\Library::load('jquery');

	Market\Ui\Assets::loadPlugin('admin', 'css');
	Market\Ui\Assets::loadPlugin('base', 'css');

	Market\Ui\Assets::loadPluginCore();
	Market\Ui\Assets::loadFieldsCore();
	Market\Ui\Assets::loadPlugin('Ui.Form.NotifyUnsaved');

	echo bitrix_sessid_post();

	foreach ($arResult['TABS'] as $tab)
	{
		$tabControl->BeginNextTab([ 'showTitle' => false ]);

		$isActiveTab = ($arParams['FORM_BEHAVIOR'] !== 'steps' || $tab['STEP'] === $arResult['STEP']);
		$tabLayout = $tab['LAYOUT'] ?: 'default';
		$fields = $tab['FIELDS'];

		if ($isActiveTab && isset($tab['DATA']['METRIKA_GOAL']))
		{
			Market\Metrika::reachGoal($tab['DATA']['METRIKA_GOAL']);
		}

		include __DIR__ . '/hidden.php';
		include __DIR__ . '/tab-' . $tabLayout . '.php';
	}

	$tabControl->Buttons();

	include __DIR__ . '/buttons.php';

	$tabControl->End();
	?>
</form>
<script>
	(function() {
		var utils = BX.namespace('YandexMarket.Utils');

		utils.registerLang(<?= Market\Utils::jsonEncode($langNotifyUnsaved, JSON_UNESCAPED_UNICODE); ?>, 'YANDEX_MARKET_FORM_NOTIFY_UNSAVED_');
	})();
</script>
<?php
if ($arParams['FORM_BEHAVIOR'] === 'steps')
{
	?>
	<script>
		<?php
		foreach ($arResult['TABS'] as $tab)
		{
			if ($tab['STEP'] !== $arResult['STEP'])
			{
				?>
				<?= $arParams['FORM_ID']; ?>.DisableTab('<?= $tab['DIV']; ?>');
				<?php
			}
		}
		?>
	</script>
	<?php
}
