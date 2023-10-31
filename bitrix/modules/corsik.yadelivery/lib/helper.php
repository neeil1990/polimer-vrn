<?php

namespace Corsik\YaDelivery;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\Json;
use CAdminMessage;
use CControllerClient;
use COption;
use CUtil;

Loc::loadMessages(__FILE__);

class Helper
{
	public static string $module_id = 'corsik.yadelivery';
	protected static $instance = null;

	public static function showPublicErrors(): void
	{
		$errors = false;
		if (empty(Option::get('fileman', "yandex_map_api_key")))
		{
			$errors = Loc::getMessage("CORSIK_DELIVERY_SERVICE_YANDEX_MAP_KEY_ERRORS");
		}

		if (empty(Option::get(self::$module_id, "api_key_dadata")))
		{
			$errors .= '<br>' . Loc::getMessage("CORSIK_DELIVERY_SERVICE_DADATA_KEY_ERRORS");
		}

		if ($errors)
		{
			self::showArrayErrors(['DETAIL' => trim($errors, '<br>')]);
		}
	}

	public static function showArrayErrors($errors, $message = null)
	{
		$message = [
			'MESSAGE' => $message ?? Loc::getMessage("CORSIK_DELIVERY_SERVICE_DELIVERY_ERROR"),
			'TYPE' => "ERROR",
		];

		if (is_array($errors))
		{
			foreach ($errors as $error)
			{
				$message['DETAILS'] .= "$error.<br>";
			}
		}
		else
		{
			$message['DETAILS'] = $errors;
		}

		return CAdminMessage::ShowMessage($message);
	}

	public static function translit(string $str, string $lang = 'ru', array $params = []): string
	{
		$default = [
			'max_len' => 20,
			'change_case' => 'L',
			'replace_space' => "-",
			'replace_other' => "-",
			'delete_repeat_replace' => true,
		];

		return CUtil::translit(trim($str), $lang, array_merge($default, $params));
	}

	public static function isJson($string): bool
	{
		json_decode($string);

		return (json_last_error() == JSON_ERROR_NONE);
	}

	public static function getEditRuleLink(string $link, string $id): string
	{
		return "Rule.show(
            '$link&action=edit&ID=$id', 
            '" . Loc::getMessage("CORSIK_DELIVERY_SERVICE_RULE_PRICE_TITLE") . "'
          )";
	}

	public static function getDefaultRulesLink(string $link): array
	{
		return [
			[
				"TEXT" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_RULE_PRICE"),
				"ONCLICK" => 'Rule.show(
                    "' . $link . '&type_rule=price", 
                    "' . Loc::getMessage("CORSIK_DELIVERY_SERVICE_RULE_PRICE_TITLE") . '"
                 )',
			],
			[
				"TEXT" => Loc::getMessage("CORSIK_DELIVERY_SERVICE_RULE_WEIGHT"),
				"ONCLICK" => 'Rule.show(
                    "' . $link . '&type_rule=weight", 
                    "' . Loc::getMessage("CORSIK_DELIVERY_SERVICE_RULE_WEIGHT_TITLE") . '"
                )',
			],
		];
	}

	public static function array_change_key_case_recursive(array $arr, $case = CASE_LOWER): array
	{
		return array_map(function ($item) use ($case) {
			if (is_array($item))
				$item = self::array_change_key_case_recursive($item, $case);

			return $item;
		}, array_change_key_case($arr, $case));
	}

	public static function resetOptions(array $arOptions, array $arIgnore, bool $optionMode = false): void
	{
		if ($optionMode)
		{
			$arOptions = array_map(fn($option) => $option[1] ? $option[0] : null, $arOptions);
		}

		foreach (array_diff($arOptions, $arIgnore) as $optionKey)
		{
			if (strlen($optionKey) > 1 || is_numeric($optionKey))
			{
				Option::delete(self::$module_id, ['name' => $optionKey]);
			}
		}
	}

	/**
	 * Json 1251 fixed
	 */
	public static function JsonDecode(string $json): array
	{
		$convertJson = $json;
		if (!Application::getInstance()->isUtfMode())
		{
			$convertJson = Encoding::convertEncoding($json, SITE_CHARSET, 'UTF-8');
		}

		return Json::decode($convertJson);
	}

	/**
	 * @return Helper
	 */
	public static function getInstance()
	{
		if (!isset(static::$instance))
		{
			static::$instance = new static();
		}

		return static::$instance;
	}

	public function filtrationWarehouses($delivery, $warehouses): array
	{
		return array_filter($warehouses, function ($warehouse) use ($delivery) {
			return (
				($warehouse['FROM'] === $delivery['warehouse'] || $warehouse['FROM'] === 'all')
				&& ($warehouse['TO'] === $delivery['stop'] || $warehouse['TO'] === 'all')
			);
		});
	}

	public function filtrationPolygons($delivery, $polygons): array
	{
		return array_filter($polygons, function ($polygon) use ($delivery) {
			return $polygon['POLYGON'] === $delivery['start'] || $polygon['POLYGON'] === 'all';
		});
	}

	public function filtrationRules($order, $data): array
	{
		$filteredRulesByOrder = array_filter($data, function ($rule) use ($order) {
			if ($rule['RULE'] === 'no')
			{
				return true;
			}

			$ruleData = $this->getDataById($rule['RULE'], 'rules');
			$rules = Helper::JsonDecode($ruleData['RULE']);

			return $this->checkByRange($order[$ruleData['TYPE']], $rules);
		});

		if (empty($filteredRulesByOrder))
		{
			return [
				['PRICE' => 0],
			];
		}

		$rules = [
			'CONFIGURED' => [],
			'COMMON' => [],
		];

		foreach ($filteredRulesByOrder as $filteredRule)
		{
			if ($filteredRule['FROM'] !== 'all' || $filteredRule['TO'] !== 'all' || $filteredRule['RULE'] !== 'no')
			{
				$rules['CONFIGURED'][] = $filteredRule;
			}
			else
			{
				$rules['COMMON'][] = $filteredRule;
			}
		}

		return count($rules['CONFIGURED']) > 0 ? $rules['CONFIGURED'] : $rules['COMMON'];
	}

	/**
	 * ѕолучаем активную запись из таблицы
	 */
	public function getDataById($id, $table, $select = ['*'], $filterBy = 'ID'): array
	{
		$query = ["select" => $select, "filter" => ["ACTIVE" => "Y", $filterBy => $id]];
		$data = $this->getQueryObject($table, $query, 'getList')->fetchRaw();

		return $data ?: [];
	}

	public function getQueryObject($type, $query, $request)
	{
		return call_user_func_array([__NAMESPACE__ . "\\Table\\" . ucfirst($type) . "Table", $request], [$query]);
	}

	public function checkByRange(int $value, array $restrictionParams): bool
	{
		if (empty($restrictionParams))
		{
			return true;
		}

		if ($value < 0)
		{
			return true;
		}

		$value = floatval($value);

		if (
			floatval($restrictionParams["MIN"]) > 0
			&& $value < floatval($restrictionParams["MIN"])
		)
		{
			$result = false;
		}
		else if (
			floatval($restrictionParams["MAX"]) > 0
			&& $value > floatval($restrictionParams["MAX"])
		)
		{
			$result = false;
		}
		else
		{
			$result = true;
		}

		return $result;
	}

	public function getMaxPrice($data): array
	{
		return array_reduce($data, function ($a, $b) {
			return $a['PRICE'] > $b['PRICE'] ? $a : $b;
		}, ['PRICE' => 0]);
	}

	public function __AdmSettingsSaveOptions(string $module_id, ?array $arOptions): void
	{
		foreach ($arOptions as $arOption)
		{
			if (is_array($arOption))
			{
				$this->__AdmSettingsSaveOption($module_id, $arOption);
			}
		}
	}

	public function __AdmSettingsSaveOption(string $module_id, array $arOption): bool
	{

		if (isset($arOption["note"]))
		{
			return false;
		}

		if ($arOption[3][0] == "statictext")
		{
			return false;
		}

		$arControllerOption = CControllerClient::GetInstalledOptions($module_id);

		if (isset($arControllerOption[$arOption[0]]))
		{
			return false;
		}

		$name = $arOption[0];

		if (
			!isset($_REQUEST[$name]) &&
			$arOption[3][0] <> 'checkbox' &&
			$arOption[3][0] <> "multiselectbox"
		)
		{
			return false;
		}

		$val = $_REQUEST[$name];

		if ($arOption[3][0] == "checkbox" && $val != "Y")
		{
			$val = "N";
		}

		if ($arOption[3][0] == "multiselectbox" && is_array($val))
		{
			$val = implode(",", $val);
		}

		COption::SetOptionString($module_id, $name, $val, $arOption[1]);

		return false;
	}

	public function __AdmSettingsDrawRow(string $module_id, $Option): void
	{
		$arControllerOption = CControllerClient::GetInstalledOptions($module_id);
		$optionName = $Option[0];
		if ($Option === null)
		{
			return;
		}

		if (!is_array($Option))
		{
			?>
			<tr class="heading">
				<td colspan="2"><?= $Option ?></td>
			</tr>
			<?
		}
		else if (isset($Option["note"]))
		{
			?>
			<tr data-name="<?= $optionName ?>">
				<td colspan="2" align="center">
					<?= BeginNote('align="center"'); ?>
					<?= $Option["note"] ?>
					<?
					echo EndNote(); ?>
				</td>
			</tr>
			<?
		}
		else
		{
			if ($optionName != "")
			{
				$val = COption::GetOptionString($module_id, $optionName, $Option[2]);
			}
			else
			{
				$val = $Option[2];
			} ?>
			<tr data-name="<?= $optionName ?>">
				<?
				$this->renderLabel($Option);
				$this->renderInput($Option, $arControllerOption, $optionName, $val);
				?>
			</tr>
			<?
		}
	}

	public function renderLabel(array $Option): void
	{
		$type = $Option[3];
		$optionName = $Option[0];
		$sup_text = array_key_exists(5, $Option) ? $Option[5] : '';
		?>
		<? if ($type[0] != 'hidden') { ?>
		<td
			<?php
			if ($type[0] == "multiselectbox" || $type[0] == "textarea" || $type[0] == "statictext" || $type[0] == "statichtml")
				echo ' class="adm-detail-valign-top"' ?> width="50%">
			<?php
			if (!!$type[2] && $type[0] !== "checkbox")
				echo ShowJSHint($type[2]);
			if ($type[0] == "checkbox")
			{
				if (!!$type[3])
					echo ShowJSHint($type[3]);
				echo "<label for='" . htmlspecialcharsbx($optionName) . "'>" . $Option[1] . "</label>";
			}
			else
			{
				echo $Option[1];
			}
			if (strlen($sup_text) > 0)
			{
				?><span class="required"><sup><?= $sup_text ?></sup></span><?
			}
			?>
			<a name="opt_<?= htmlspecialcharsbx($optionName) ?>"></a>
		</td>
		<?
	}
	}

	public function renderInput($Option, $arControllerOption, $fieldName, $val)
	{
		$type = $Option[3];
		$optionName = $Option[0];
		$disabled = array_key_exists(4, $Option) && $Option[4] == 'Y' ? ' disabled' : '';
		?>
		<td width="50%"><?
		if ($type[0] == "checkbox")
		{
			?><input type="checkbox" <?
			if (isset($arControllerOption[$optionName]))
				echo ' disabled title="' . GetMessage("MAIN_ADMIN_SET_CONTROLLER_ALT") . '"'; ?>
			id="<?
			echo htmlspecialcharsbx($optionName) ?>"
			name="<?= htmlspecialcharsbx($fieldName) ?>" value="Y"<?
			if ($val == "Y")
				echo " checked"; ?><?= $disabled ?><?
			if ($type[2] <> '')
				echo " " . $type[2] ?>><?
		}
		else if ($type[0] == "text" || $type[0] == "password" || $type[0] == "hidden")
		{
			?><input type="<?
			echo $type[0] ?>"<?
			if (isset($arControllerOption[$optionName]))
				echo ' disabled title="' . GetMessage("MAIN_ADMIN_SET_CONTROLLER_ALT") . '"'; ?>
			size="<?
			echo $type[1] ?>" maxlength="255" value="<?
		echo htmlspecialcharsbx($val) ?>" name="<?= htmlspecialcharsbx($fieldName) ?>"<?= $disabled ?><?= ($type[0] == "password" || $type["noautocomplete"] ? ' autocomplete="new-password"' : '') ?>><?

		}
		else if ($type[0] == "selectbox")
		{
			$arr = $type[1];
			if (!is_array($arr))
			{
				$arr = [];
			}
			?>
		<select name="<?= htmlspecialcharsbx($fieldName) ?>" <?
		if (isset($arControllerOption[$optionName]))
		{
			echo ' disabled title="' . GetMessage("MAIN_ADMIN_SET_CONTROLLER_ALT") . '"';
		} ?>
			<?= $disabled ?>>
			<?
			foreach ($arr as $key => $v)
			{
				$arKey = explode('_', $key);
				if ($arKey[0] === 'GROUP' && $arKey[1] === 'START')
				{
					?><optgroup label="<?= $v ?>"><?
				}
				else if ($arKey[0] !== 'GROUP' && $arKey[1] !== 'END')
				{
					?>
					<option value="<? echo $key ?>"
						<?
						if ($val == $key)
						{
							echo " selected";
						} ?>
					>
						<? echo htmlspecialcharsbx($v) ?>
					</option>
					<?
				}
				else if ($arKey[0] === 'GROUP' && $arKey[1] === 'END')
				{
					?></optgroup><?
				}
			}
			?></select><?
		}
		else if ($type[0] == "multiselectbox")
		{
			$arr = $type[1];
			if (!is_array($arr))
			{
				$arr = [];
			}
			$arr_val = explode(",", $val);
			?><select size="5" <?
			if (isset($arControllerOption[$optionName]))
			{
				echo ' disabled title="' . GetMessage("MAIN_ADMIN_SET_CONTROLLER_ALT") . '"';
			} ?>
			multiple name="<?= htmlspecialcharsbx($fieldName) ?>[]"<?= $disabled ?>><?
			foreach ($arr as $key => $v)
			{
				?>
				<option value="<?
				echo $key ?>"<?
				if (in_array($key, $arr_val))
				{
					echo " selected";
				} ?>><?
				echo htmlspecialcharsbx($v) ?></option><?
			}
			?></select><?
		}
		else if ($type[0] == "textarea")
		{
			?><textarea<?
			if (isset($arControllerOption[$optionName]))
			{
				echo ' disabled title="' . GetMessage("MAIN_ADMIN_SET_CONTROLLER_ALT") . '"';
			} ?> rows="<?
		echo $type[1] ?>" cols="<?
		echo $type[2] ?>" name="<?= htmlspecialcharsbx($fieldName) ?>"<?= $disabled ?>><?
			echo htmlspecialcharsbx($val) ?></textarea><?
		}
		else if ($type[0] == "statictext")
		{
			echo htmlspecialcharsbx($val);
		}
		else if ($type[0] == "statichtml")
		{
			echo $val;
		}
		?>
		</td><?
	}
}
