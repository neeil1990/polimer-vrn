<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) { die(); }

if (!class_exists(CAdminUiListActionPanel::class)) { return; }

$actionPanelConstructor = new CAdminUiListActionPanel($adminList->table_id, $adminList->arActions, $adminList->arActionsParams);
$actionPanel = $actionPanelConstructor->getActionPanel();

if ($actionPanel)
{
	// rewrite js

	if (isset($actionPanel['GROUPS']))
	{
		foreach ($actionPanel['GROUPS'] as &$panelGroup)
		{
			if (!isset($panelGroup['ITEMS'])) { continue; }

			foreach ($panelGroup['ITEMS'] as &$groupItem)
			{
				if (!isset($groupItem['TYPE']) || $groupItem['TYPE'] !== 'DROPDOWN') { continue; }
				if (!isset($groupItem['ITEMS'])) { continue; }

				foreach ($groupItem['ITEMS'] as &$dropdownItem)
				{
					if (!isset($dropdownItem['ONCHANGE'][1]['DATA'][1])) { continue; }

					$button = &$dropdownItem['ONCHANGE'][1]['DATA'][1];

					if (isset($button['TYPE'], $button['ONCHANGE'][0]['DATA'][0]['JS']) && $button['TYPE'] === 'BUTTON')
					{
						$button['ONCHANGE'][0]['DATA'][0]['JS'] = str_replace(
							'BX.adminUiList.',
							'BX.publicUiList.',
							$button['ONCHANGE'][0]['DATA'][0]['JS']
						);
					}

					unset($button);
				}
				unset($dropdownItem);
			}
			unset($groupItem);
		}
		unset($panelGroup);
	}

	// export

	$arResult['GRID_PARAMETERS'] += [
		'ACTION_PANEL' => $actionPanel,
	];
}
else
{
	$arResult['GRID_PARAMETERS'] += [
		'SHOW_CHECK_ALL_CHECKBOXES' => false,
		'SHOW_ROW_CHECKBOXES' => false,
		'SHOW_SELECTED_COUNTER' => false,
		'SHOW_ACTION_PANEL' => false,
	];
}