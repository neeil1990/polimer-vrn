<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) { die(); }

use Bitrix\Main;
use Yandex\Market;
use Yandex\Market\Ui\UserField\Helper;

if (empty($arResult['CONTEXT_MENU'])) { return; }

$context = new CAdminContextMenu($arResult['CONTEXT_MENU']);
$buttons = [];

foreach ($context->items as $contextItem)
{
	if (!empty($contextItem['HTML']) || !empty($contextItem['MENU']) || $contextItem['ICON'] === 'btn_list') { continue; }

	$iconClass = $context->GetClassByID($contextItem['ICON']);
	$linkQueryString = parse_url($contextItem['LINK'], PHP_URL_QUERY);
	parse_str($linkQueryString, $linkQuery);

	$attributes = array_filter([
		'class' => 'yamarket-btn yamarket-btn-additional adm-btn ' . $iconClass,
		'href' => $contextItem['LINK'],
		'title' => $item['TITLE'],
		'onclick' => $contextItem['ONCLICK'],
	]);

	if (isset($linkQuery['postAction']))
	{
		$attributes['data-post-action'] = $linkQuery['postAction'];
	}

	$buttons[] = sprintf('<a %s>%s</a>', Helper\Attributes::stringify($attributes), $contextItem['TEXT']);
}

$buttonsHtml = implode('', $buttons);
?>
<script>
	(function() {
		const popup = top.BX.WindowManager.Get();
		const buttons = <?= Market\Utils::jsonEncode($buttonsHtml, JSON_UNESCAPED_UNICODE) ?>;
		const fragment = document.createElement('div');

		if (!popup) { return; }

		fragment.innerHTML = buttons;
		popup.SetButtons(fragment.children); // hack for prevent append via buttons.innerHTML += html, removes event listeners
	})();
</script>