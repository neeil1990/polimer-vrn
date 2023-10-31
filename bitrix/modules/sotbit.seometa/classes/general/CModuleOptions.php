<?
IncludeModuleLangFile( __FILE__ );

class CModuleOptions
{
	public $arCurOptionValues = [];
	private $module_id;
	private $arTabs;
	private $arGroups;
	private $arOptions;
	private $need_access_tab;

    public function __construct(
        $module_id,
        $arTabs,
        $arGroups,
        $arOptions,
        $need_access_tab = false
    ) {
		$this->module_id = $module_id;
		$this->arTabs = $arTabs;
		$this->arGroups = $arGroups;
		$this->arOptions = $arOptions;
		$this->need_access_tab = $need_access_tab;
        if ($need_access_tab) {
            $this->arTabs[] = [
                'DIV' => 'edit_access_tab',
                'TAB' => GetMessage("sns.tools1c_access_Tab"),
                'ICON' => '',
                'TITLE' => GetMessage("sns.tools1c_access_title")
            ];
        }

        if ($_REQUEST['update'] == 'Y' && check_bitrix_sessid()) {
            $this->SaveOptions();
            if ($this->need_access_tab) {
                $this->SaveGroupRight();
            }
        }

        $this->GetCurOptionValues();
	}

    private function SaveOptions(
    ) {
		global $APPLICATION;
		$CONS_RIGHT = $APPLICATION->GetGroupRight( $this->module_id );
        if ($CONS_RIGHT <= "R") {
            echo CAdminMessage::ShowMessage(GetMessage($this->module_id . '_ERROR_RIGTH'));
            return false;
        }

		$siteId = '';
		if(!empty($_REQUEST['site'])) {
		    $siteId = $_REQUEST['site'];
        }

        foreach ($this->arOptions as $opt => $arOptParams) {
            if ($arOptParams['TYPE'] != 'CUSTOM') {
                $val = $_REQUEST[$opt];
                if ($arOptParams['TYPE'] == 'CHECKBOX' && $val != 'Y') {
                    $val = 'N';
                } elseif (is_array($val)) {
                    $val = serialize($val);
                }

                COption::SetOptionString(
                    $this->module_id,
                    $opt,
                    $val,
                    false,
                    $siteId
                );
            } else {
                $pos = mb_stripos($opt, "FORUM_TOPICS");
                if ($pos !== false) {
                    $ValArray = [];
                    if (is_array($_POST[$opt])) {
                        foreach ($_POST[$opt] as $i => $val) {
                            $ValArray[$i]['SECTION'] = $val;
                            $ValArray[$i]['TOPIC'] = $_POST[$opt . '_TOPIC'][$i];
                        }

                        if (is_array($ValArray)) {
                            COption::SetOptionString(
                                $this->module_id,
                                $opt,
                                serialize($ValArray),
                                false,
                                $siteId
                            );
                        }
                    }
                }
            }
        }

        return true;
    }

    private function SaveGroupRight(
    ) {
        CMain::DelGroupRight($this->module_id);
        $GROUP = $_REQUEST['GROUPS'];
        $RIGHT = $_REQUEST['RIGHTS'];
        foreach ($GROUP as $k => $v) {
            if ($k == 0) {
                COption::SetOptionString(
                    $this->module_id,
                    'GROUP_DEFAULT_RIGHT',
                    $RIGHT[0],
                    'Right for groups by default'
                );
            } else {
                CMain::SetGroupRight(
                    $this->module_id,
                    $GROUP[$k],
                    $RIGHT[$k]
                );
            }
        }
    }

    private function GetCurOptionValues(
    ) {
        $siteId = '';
        if (!empty($_REQUEST['site'])) {
            $siteId = $_REQUEST['site'];
        }

        foreach ($this->arOptions as $opt => $arOptParams) {
            if ($arOptParams['TYPE'] != 'CUSTOM') {
                $this->arCurOptionValues[$opt] = COption::GetOptionString(
                    $this->module_id,
                    $opt,
                    $arOptParams['DEFAULT'],
                    $siteId
                );
                if (in_array($arOptParams['TYPE'], ['MSELECT'])) {
                    $this->arCurOptionValues[$opt] = unserialize($this->arCurOptionValues[$opt]);
                }
            }
        }
	}

    public function ShowHTML(
    ) {
		global $APPLICATION;
		$arP = [];
        foreach ($this->arGroups as $group_id => $group_params) {
            $arP[$group_params['TAB']][$group_id] = [];
        }

        if (is_array($this->arOptions)) {
            foreach ($this->arOptions as $option => $arOptParams) {
				$val = $this->arCurOptionValues[$option];
                if (empty($arOptParams['SORT'])) {
                    $arOptParams['SORT'] = 0;
                }

				$label = !empty($arOptParams['TITLE']) ? $arOptParams['TITLE'] : '';
				$opt = htmlspecialchars( $option );
				$label .= ':';
                switch ($arOptParams['TYPE']) {
					case 'CHECKBOX' :
						$input = '<input type="checkbox" name="'.$opt.'" id="'.$opt.'" value="Y"'.($val=='Y' ? ' checked' : '').' '.($arOptParams['REFRESH']=='Y' ? 'onclick="document.forms[\''.$this->module_id.'\'].submit();"' : '').' />';
						break;
					case 'TEXT' :
                        if (empty($arOptParams['COLS'])) {
                            $arOptParams['COLS'] = 25;
                        }

                        if (empty($arOptParams['ROWS'])) {
                            $arOptParams['ROWS'] = 5;
                        }

						$input = '<textarea cols="'.$arOptParams['COLS'].'" rows="'.$arOptParams['ROWS'].'" name="'.$opt.'">'.htmlspecialchars( $val ).'</textarea>';
                        if ($arOptParams['REFRESH'] == 'Y') {
                            $input .= '<input type="submit" name="refresh" value="OK" />';
                        }

						break;
					case 'SELECT' :
						$input = SelectBoxFromArray(
						    $opt,
                            $arOptParams['VALUES'],
                            $val,
                            '',
                            '',
                            $arOptParams['REFRESH'] == 'Y' ? true : false,
                            $arOptParams['REFRESH'] == 'Y' ? $this->module_id : ''
                        );
                        if ($arOptParams['REFRESH'] == 'Y') {
                            $input .= '<input type="submit" name="refresh" value="OK" />';
                        }

						break;
					case 'MSELECT' :
						$selHTML = '';
                        if ($arOptParams['WIDTH']) {
                            $selHTML = 'style="width: ' . $arOptParams['WIDTH'] . 'px"';
                        }

                        if (empty($arOptParams['VALUES'])) {
                            $arOptParams['VALUES'] = [];
                        }

                        $input = SelectBoxMFromArray(
                            $opt . '[]',
                            $arOptParams['VALUES'],
                            $val,
                            '',
                            false,
                            $arOptParams['SIZE'],
                            $selHTML
                        );
                        if ($arOptParams['REFRESH'] == 'Y') {
                            $input .= '<input type="submit" name="refresh" value="OK" />';
                        }
						break;
					case 'COLORPICKER' :
                        if (empty($arOptParams['FIELD_SIZE'])) {
                            $arOptParams['FIELD_SIZE'] = 25;
                        }

                        ob_start();
						echo '<input id="__CP_PARAM_'.$opt.'" name="'.$opt.'" size="'.$arOptParams['FIELD_SIZE'].'" value="'.htmlspecialchars( $val ).'" type="text" style="float: left;" '.($arOptParams['FIELD_READONLY']=='Y' ? 'readonly' : '').' />
                                <script>
                                    function onSelect_'.$opt.'(color, objColorPicker)
                                    {
                                        var oInput = BX("__CP_PARAM_'.$opt.'");
                                        oInput.value = color;
                                    }
                                </script>';
                        $APPLICATION->IncludeComponent(
                            'bitrix:main.colorpicker',
                            '',
                            [
                                'SHOW_BUTTON' => 'Y',
                                'ID' => $opt,
                                'NAME' => GetMessage("sns.tools1c_choice_color"),
                                'ONSELECT' => 'onSelect_' . $opt
                            ],
                            false
                        );
                        $input = ob_get_clean();
                        if ($arOptParams['REFRESH'] == 'Y') {
                            $input .= '<input type="submit" name="refresh" value="OK" />';
                        }

						break;
					case 'FILE' :
                        if (empty($arOptParams['FIELD_SIZE'])) {
                            $arOptParams['FIELD_SIZE'] = 25;
                        }
                        if (!isset($arOptParams['BUTTON_TEXT'])) {
                            $arOptParams['BUTTON_TEXT'] = '...';
                        }

                        CAdminFileDialog::ShowScript([
                            'event' => 'BX_FD_' . $opt,
                            'arResultDest' => [
                                'FUNCTION_NAME' => 'BX_FD_ONRESULT_' . $opt
                            ],
                            'arPath' => [],
                            'select' => 'F',
                            'operation' => 'O',
                            'showUploadTab' => true,
                            'showAddToMenuTab' => false,
                            'fileFilter' => '',
                            'allowAllFiles' => true,
                            'SaveConfig' => true
                        ]);
						$input = '<input id="__FD_PARAM_'.$opt.'" name="'.$opt.'" size="'.$arOptParams['FIELD_SIZE'].'" value="'.htmlspecialchars( $val ).'" type="text" style="float: left;" '.($arOptParams['FIELD_READONLY']=='Y' ? 'readonly' : '').' />
                                    <input value="'.$arOptParams['BUTTON_TEXT'].'" type="button" onclick="window.BX_FD_'.$opt.'();" />
                                    <script>
                                        setTimeout(function(){
                                            if (BX("bx_fd_input_'.mb_strtolower( $opt ).'"))
                                                BX("bx_fd_input_'.mb_strtolower( $opt ).'").onclick = window.BX_FD_'.$opt.';
                                        }, 200);
                                        window.BX_FD_ONRESULT_'.$opt.' = function(filename, filepath)
                                        {
                                            var oInput = BX("__FD_PARAM_'.$opt.'");
                                            if (typeof filename == "object")
                                                oInput.value = filename.src;
                                            else
                                                oInput.value = (filepath + "/" + filename).replace(/\/\//ig, \'/\');
                                        }
                                    </script>';
                        if ($arOptParams['REFRESH'] == 'Y') {
                            $input .= '<input type="submit" name="refresh" value="OK" />';
                        }

						break;
                    case 'CALENDAR':
                        $input = CAdminCalendar::CalendarDate($option, $val, 19, true);
                        break;
					case 'CUSTOM' :
						$input = $arOptParams['VALUE'];
						break;
					default :
                        if (empty($arOptParams['SIZE'])) {
                            $arOptParams['SIZE'] = 25;
                        }

                        if (empty($arOptParams['MAXLENGTH'])) {
                            $arOptParams['MAXLENGTH'] = 255;
                        }

                        if (empty($arOptParams['MULTI'])) {
                            $arOptParams['MULTI'] = 'N';
                        }

                        if ($arOptParams['MULTI'] == 'N') {
                            $input = '<input type="' . ($arOptParams['TYPE'] == 'INT' ? 'number' : 'text') . '" size="' . $arOptParams['SIZE'] . '" maxlength="' . $arOptParams['MAXLENGTH'] . '" value="' . htmlspecialchars($val) . '" name="' . htmlspecialchars($option) . '" />';
                        } else {
                            $massive = !empty($val) ? unserialize($val) : [];
							$input = "<script type=\"text/javascript\">
                                        function ".htmlspecialchars( $option )."(){
                                        var div = document.createElement(\"div\");
div.innerHTML = \"<input type='".($arOptParams['TYPE']=='INT' ? 'number' : 'text')."' size='".$arOptParams['SIZE']."' maxlength='".$arOptParams['MAXLENGTH']."' name='".htmlspecialchars( $option )."[]' />\";
document.getElementById('".htmlspecialchars( $option )."').appendChild(div);
                                                }
                                        </script>

                                        <span name='".htmlspecialchars( $option )."' id='".htmlspecialchars( $option )."'>";
                            if (!empty($massive[0])) {
                                foreach ($massive as $element) {
                                    if (!empty($element)) {
                                        $input .= "<input type='" . ($arOptParams['TYPE'] == 'INT' ? 'number' : 'text') . "' size='" . $arOptParams['SIZE'] . "' maxlength='" . $arOptParams['MAXLENGTH'] . "' name='" . htmlspecialchars($option) . "[]' value='" . $element . "' /><br />";
                                    }
                                }
                            } else {
                                $input .= "<input type='" . ($arOptParams['TYPE'] == 'INT' ? 'number' : 'text') . "' size='" . $arOptParams['SIZE'] . "' maxlength='" . $arOptParams['MAXLENGTH'] . "' name='" . htmlspecialchars($option) . "[]' value='' /><br />";
                            }

							$input .= "</span>
                                        			<input type='button' value='+' onclick=\"".htmlspecialchars( $option )."()\">";
						}

                        if ($arOptParams['REFRESH'] == 'Y') {
                            $input .= '<input type="submit" name="refresh" value="OK" />';
                        }

						break;
				}

				$notes = '';
                if (!empty($arOptParams['NOTES'])) {
                    $notes = '<tr><td align="center" colspan="2">
                                        <div align="center" class="adm-info-message-wrap">
                                            <div class="adm-info-message">
                                                ' . $arOptParams['NOTES'] . '
                                            </div>
                                        </div>
									</td></tr>';
                }

                if (!empty($arOptParams['NOTES_ENUM'])) {
                    $notes = '<tr><td align="center" colspan="2"><div align="center" class="adm-info-message-wrap"><div align="left" class="adm-info-message">' . $arOptParams['NOTES_ENUM'] . '</div></div></td></tr>';
                }

                $arP[$this->arGroups[$arOptParams['GROUP']]['TAB']][$arOptParams['GROUP']]['OPTIONS'][] =
                    $label != ''
                    ? '<tr><td width="50%">' . $label . '</td><td width="50%">' . $input . '</td></tr>' . $notes . ' '
                    : '<tr><td colspan="2" >' . $input . '</td></tr>' . $notes . ' ';
                $arP[$this->arGroups[$arOptParams['GROUP']]['TAB']][$arOptParams['GROUP']]['OPTIONS_SORT'][] = $arOptParams['SORT'];
			}

            $tabControl = new CAdminTabControl('tabControl', $this->arTabs);
            $tabControl->Begin();
			echo '<form name="'.$this->module_id.'" method="POST" action="'.$APPLICATION->GetCurPage().'?mid='.$this->module_id.'&lang='.LANGUAGE_ID.'&site='.$_GET['site'].'" enctype="multipart/form-data">'.bitrix_sessid_post();
            foreach ($arP as $groups) {
                $tabControl->BeginNextTab();
                foreach ($groups as $group_id => $group) {
                    if (!empty($group['OPTIONS_SORT'])) {
						echo '<tr class="heading"><td colspan="2">'.$this->arGroups[$group_id]['TITLE'].'</td></tr>';
						array_multisort( $group['OPTIONS_SORT'], $group['OPTIONS'] );
                        foreach ($group['OPTIONS'] as $opt) {
                            echo $opt;
                        }
					}
				}
			}

            if ($this->need_access_tab) {
				$tabControl->BeginNextTab();
				$module_id = $this->module_id;
				require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
			}

			$tabControl->Buttons();
			echo '<input type="hidden" value="'.$_REQUEST["tabControl_active_tab"].'" name="tabControl_active_tab" id="tabControl_active_tab">
            <input type="hidden" name="update" value="Y" />
                    <input type="submit" class="adm-btn-save" name="save" value="'.GetMessage( "MAIN_SAVE" ).'" />
                    </form>';
			bitrix_sessid_post();
			$tabControl->End();
		}
	}
}
?>