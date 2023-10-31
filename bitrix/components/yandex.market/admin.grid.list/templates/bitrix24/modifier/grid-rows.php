<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) { die(); }

$gridRows = [];

/** @var \CAdminUiListRow $row */
foreach ($adminList->aRows as $row)
{
	$gridRow = [
		'id' => $row->id,
		'actions' => $row->getPreparedActions(),
		'data' => $row->arRes,
		'editable' => !$row->bReadOnly,
	];

	if ($row->link)
	{
		$gridRow['default_action'] = [
			'href' => htmlspecialcharsback($row->link),
		];

		if ($row->title)
		{
			$gridRow['default_action']['title'] = $row->title;
		}
	}
	else
	{
		$defaultActions = array_filter($gridRow['actions'], function ($action) { return $action['DEFAULT'] === true; });
		$defaultAction = reset($defaultActions);

		if (isset($defaultAction['ONCLICK']))
		{
			$gridRow['default_action'] = [
				'onclick' => $defaultAction['ONCLICK'],
			];
		}
		else
		{
			$gridRow['default_action'] = [
				'onclick' => '',
			];
		}
	}

	foreach ($adminList->aHeaders as $columnId => $title)
	{
		$field = $row->aFields[$columnId];
		$value = $row->arRes[$columnId];

		if (!is_array($value)) { $value = trim($value); }

		$viewType = isset($field['view']['type']) ? $field['view']['type'] : null;

		if ($viewType === 'checkbox')
		{
			$value = ($value === 'Y' ? GetMessage('admin_lib_list_yes') : GetMessage('admin_lib_list_no'));
			$value = htmlspecialcharsex($value);
		}
		else if ($viewType === 'select')
		{
			if (isset($field['edit']['values'][$value]))
			{
				$value = $field['edit']['values'][$value];
			}

			$value = htmlspecialcharsex($value);
		}
		else if ($viewType === 'file')
		{
			$value = $value
				? CFileInput::Show('fileInput_'.$value, $value, $field['view']['showInfo'], $field['view']['inputs'])
				: '';
		}
		else if ($viewType === 'html')
 		{
			$fieldValue = trim($field['view']['value']);

			if ($fieldValue !== '')
			{
				$value = $fieldValue;
			}
			else if (is_array($value))
			{
				$value = '';
			}
		}
		else if ($viewType !== null)
		{
			$value = htmlspecialcharsex($value);
		}
		else
		{
			$value = htmlspecialcharsbx($value);
		}

		$gridRow['columns'][$columnId] = $value;
	}

	$gridRows[] = $gridRow;
}

$arResult['GRID_PARAMETERS'] += [
	'ROWS' => $gridRows,
];