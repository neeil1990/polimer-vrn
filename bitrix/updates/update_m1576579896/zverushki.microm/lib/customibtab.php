<?
namespace Zverushki\Microm;

use Bitrix\Main\Config\Option,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Application,
	Bitrix\Main\Page\Asset,
	Bitrix\Main\Loader,
	Zverushki\Microm\MicromTable;

Loc::loadMessages(__FILE__);

/**
* class Data
*
* @package Zverushki\Microm\Customibtab
*/
class Customibtab
{
	private static $__instance = null;

	private $__siteId = false;
	private $iblockId = false;
	private $request = false;
	private $option = array();
	private $_option = array();
	private $_arField = array();
	private $_arProp = array();
	private $break = false;
	private $prefix = 'microm';

	private static $module_id = 'zverushki.microm';

	function __construct () {
		if ($_SERVER['PHP_SELF'] != '/bitrix/admin/iblock_edit.php')
			return !($this->break = true);

		$this->request = \Bitrix\Main\HttpApplication::GetInstance()->GetContext()->GetRequest();
		$this->iblockId = $this->request->get("ID");

		if(empty($this->iblockId))
			return !($this->break = true);
		if(Option::get(self::$module_id, "microm_view_article", "N") != "Y")
			return !($this->break = true);

		// Loader::IncludeModule("catalog");
		Loader::IncludeModule(self::$module_id);

		if($GLOBALS["APPLICATION"]->GetGroupRight(self::$module_id) < "R")
			return !($this->break = true);

		if(Loader::IncludeModule("catalog")){
			if(self::isCatlog($this->iblockId))
				return !($this->break = true);
		}

		$this->option = array(
			"form" => array(
				"dateline" => array(
					"type" => "list",
					"name" => Loc::getMessage("MICROM_ARTICLE_IB_NAME_DATELINE"),
					"description" => Loc::getMessage("MICROM_ARTICLE_IB_NAME_DATELINE_DESC"),
					"values" => $this->getPropertyList(),
					"request" => false,
					"default" => ""
				),
				"author" => array(
						"type" => "list",
						"name" => Loc::getMessage("MICROM_ARTICLE_IB_NAME_AUTHOR"),
						"description" => Loc::getMessage("MICROM_ARTICLE_IB_NAME_AUTHOR_DESC"),
						"values" => $this->getPropertyList(),
						"request" => false,
						"default" => ""
					),
				"dependencies" => array(
						"type" => "list",
						"name" => Loc::getMessage("MICROM_ARTICLE_IB_NAME_DEPENDENCIES"),
						"description" => Loc::getMessage("MICROM_ARTICLE_IB_NAME_DEPENDENCIES_DESC"),
						"values" => $this->getPropertyList(),
						"request" => false,
						"default" => ""
					),
				"proficiencyLevel" => array(
						"type" => "list",
						"name" => Loc::getMessage("MICROM_ARTICLE_IB_NAME_PROFICIENCYLV"),
						"description" => Loc::getMessage("MICROM_ARTICLE_IB_NAME_PROFICIENCYLV_DESC"),
						"values" => $this->getPropertyList(),
						"request" => false,
						"default" => ""
					)
			),
			"type" => array(
				"Article" => array(
						"name" => Loc::getMessage("MICROM_ARTICLE_TYPE_NAME_ARTICLE"),
						"rel" => array("author"),
						"form" => array(
								"author" => array(
									"type" => "list",
									"name" => Loc::getMessage("MICROM_ARTICLE_IB_NAME_AUTHOR"),
									"description" => Loc::getMessage("MICROM_ARTICLE_IB_NAME_AUTHOR_DESC"),
									"values" => $this->getPropertyList(),
									"request" => false,
									"default" => ""
								)
							),
						"description" => ""
					),
				"NewsArticle" => array(
						"name" => Loc::getMessage("MICROM_ARTICLE_TYPE_NAME_NARTICLE"),
						"rel" => array("author", "dateline"),
						"form" => array(
								"author" => array(
									"type" => "list",
									"name" => Loc::getMessage("MICROM_ARTICLE_IB_NAME_AUTHOR"),
									"description" => Loc::getMessage("MICROM_ARTICLE_IB_NAME_AUTHOR_DESC"),
									"request" => false,
									"values" => $this->getPropertyList(),
									"default" => ""
								)
							),
						"description" => ""
					),
				"TechArticle" => array(
						"name" => Loc::getMessage("MICROM_ARTICLE_TYPE_NAME_TARTICLE"),
						"rel" => array("author", "dependencies", "proficiencyLevel"),
						"form" => array(
								"author" => array(
									"type" => "list",
									"name" => Loc::getMessage("MICROM_ARTICLE_IB_NAME_AUTHOR"),
									"description" => Loc::getMessage("MICROM_ARTICLE_IB_NAME_AUTHOR_DESC"),
									"request" => false,
									"values" => $this->getPropertyList(),
									"default" => ""
								)
							),
						"description" => ""
					),
				"BlogPosting" => array(
						"name" => Loc::getMessage("MICROM_ARTICLE_TYPE_NAME_BLOGPOSTING"),
						"rel" => array("author"),
						"form" => array(
								"author" => array(
									"type" => "list",
									"name" => Loc::getMessage("MICROM_ARTICLE_IB_NAME_AUTHOR"),
									"description" => Loc::getMessage("MICROM_ARTICLE_IB_NAME_AUTHOR_DESC"),
									"request" => false,
									"values" => $this->getPropertyList(),
									"default" => ""
								)
							),
						"description" => ""
					)
				)
			);

		$this->_option = array();
		foreach ($this->option["type"] as $key => $rel) {
			$this->_option[$key] = $rel["rel"];
		}
		\CJSCore::Init(array('jquery'));
		Asset::getInstance()->addString('<style>
							.grouppropcube{
							    background-color: #fff;
							    border: 1px solid #c4ced2;
							    margin: 0 5%;
							    padding: 15px;
							}
							.request-red{
								margin-left: 1px;
								color: red;
							}
							.grouped_block{display:none;}
							.grouped_block.active{display:table-row;}
						</style>', true);

		Asset::getInstance()->addString('<script>
				window.microm_option = '.json_encode($this->_option).';
				window.microm_prefix = "'.$this->prefix.'_";
				$(document).ready(function() {
					$("#microm").on("change", ".typeselect", function(){
						__val = $("option:selected", $(this)).val();

						$("#microm .grouped_block.active").removeClass("active");
						$("#microm #hl_group_"+__val).addClass("active");

						where = window.microm_option[__val];
						$("[class^="+window.microm_prefix+"]").each(function(index, el) {
							_class = $(this).attr("class");
							__class = _class.substr(window.microm_prefix.length);

							_is = false;
							for(var i=0; i<where.length; i++){
							    if(__class == where[i]){
							    	_is = true;
							    	break;
							    }
							}
							if(_is)
								$(this).attr("disabled", false);
							else
								$(this).attr("disabled", true);
						});
					});
				});
			</script>', true);
	} // end __construct()

	public static function getInstance ($options = array()) {
		if (static::$__instance === null)
			static::$__instance = new self($options);

		return static::$__instance;
	} // end function getInstance()

	/**
	 * Событие добавления вкладки для настроек инфоблока
	 * @param \CAdminTabControl $TabControl [description]
	 */
	public static function addTab(\CAdminTabControl $TabControl) {
		$_obj = static::getInstance();

		if($_obj->break)return true;

    	$TabControl->tabs[] = array(
			"DIV" => "microm-article",
			"TAB" => Loc::getMessage("MICROM_ARTICLE_TAB_NAME"),
			"ICON" => "",
			"TITLE" => Loc::getMessage("MICROM_ARTICLE_TAB_TITLE"),
			'CONTENT' => $_obj->getTable()
		);
	} // end function addTab()

	private function getTable(){
		$this->getData();

		$table = '<tr>
			<td width="50%" class="adm-detail-content-cell-l">
				<label for="'.$this->prefix.'[article_ib_active]">'.Loc::getMessage("MICROM_ARTICLE_IB_SHOW").'</label>
			</td>
			<td width="50%" class="adm-detail-content-cell-r">
				<input'.(in_array($this->iblockId, $this->_arField["article_ib_active"]) ? ' checked="checked"' : '').' type="checkbox" name="'.$this->prefix.'[article_ib_active]" id="'.$this->prefix.'[article_ib_active]" class="adm-designed-checkbox" value="'.$this->iblockId.'"><label class="adm-designed-checkbox-label" for="'.$this->prefix.'[article_ib_active]" title=""></label>
			</td>
		</tr>';
		$table .= $this->getBlock();

		return $table;
	} // end function getTable()

	private function getBlock(){
		$this->getData();
		$_table = '<tr>
				<td width="50%" class="adm-detail-content-cell-l">
					<label for="'.$this->prefix.'[subschema][type]">'.Loc::getMessage("MICROM_ARTICLE_IB_TYPE").'</label>
				</td>
				<td width="50%" class="adm-detail-content-cell-r">
					<select name="'.$this->prefix.'[subschema][type]" class="typeselect">';
					foreach ($this->option["type"] as $key => $val)
						$_table .= '<option value="'.$key.'"'.($this->_arField["type"] == $key ? ' selected="selected"' : '').'>'.$val["name"].'</option>';
			$_table .= '</select>
				</td>
			</tr>';
			$keys = array_keys($this->option["type"]);
			$_table .= '<tr><td width="100%" colspan="2"><div class="grouppropcube"><table width="100%">';
				foreach ($this->option["type"] as $key => $val):
					if(empty($val["description"]))continue;
					$_table .= '<tr id="hl_group_'.$key.'" class="grouped_block'.(((empty($this->_arField["type"]) && $key == $keys[0]) || $this->_arField["type"] == $key)? ' active' : '').'">
						<td width="100%" colspan="2" align="center"><div class="adm-info-message-wrap"><div class="adm-info-message">'.$val["description"].'</div></div>
					</td></tr>';
				endforeach;
			$_table .= $this->getField((empty($this->_arField["type"]) ? $keys[0] : $this->_arField["type"]), $this->option["form"]).'</table></div></td></tr>';

		return $_table;
	} // end function getBlock()

	private function getField($type, $val){
		$this->getData();

		$_table = '';
		$keys = array_keys($this->option["type"]);

		foreach ($val as $key => $field) {
			$field["value"] = $this->getFieldVal($key);
			switch ($field["type"]) {
				case 'text':
					$_table .= '<tr>
						<td width="50%" class="adm-detail-content-cell-l">';
							if($field["description"]):
								$_table .= '<span id="hint_'.$key.'"></span>
								<script type="text/javascript">	BX.hint_replace(BX(\'hint_'.$key.'\'), \''.$field["description"].'\');</script>&nbsp;';
							endif;
							$_table .= '<label for="'.$this->prefix.'[subschema][values]['.$key.']">'.$field["name"].''.($field["request"] ? '<span class="request-red">*</span>' : '').':</label>
						</td>
						<td width="50%" class="adm-detail-content-cell-r">
							<input type="text" value="'.$field["value"].'" name="'.$this->prefix.'[subschema][values]['.$key.']" class="'.$this->prefix.'_'.$key.'" size="25"'.(in_array($key, $this->_option[$type]) ? '' : ' disabled="disabled"').'/>
						</td>
					</tr>';
					break;
				case 'list':
					$_table .= '<tr>
						<td width="50%" class="adm-detail-content-cell-l">';
							if($field["description"]):
								$_table .= '<span id="hint_'.$key.'"></span>
								<script type="text/javascript">	BX.hint_replace(BX(\'hint_'.$key.'\'), \''.$field["description"].'\');</script>&nbsp;';
							endif;
							$_table .= '<label for="'.$this->prefix.'[subschema][values]['.$key.']">'.$field["name"].''.($field["request"] ? '<span class="request-red">*</span>' : '').':</label>
						</td>
						<td width="50%" class="adm-detail-content-cell-r">
							<select name="'.$this->prefix.'[subschema][values]['.$key.']" class="'.$this->prefix.'_'.$key.'"'.(
								(
									(empty($this->_arField["type"]) && in_array($key, $this->_option[$keys[0]])) ||
									(!empty($this->_arField["type"]) && in_array($key, $this->_option[$type]))
								) ? '' : ' disabled="disabled"').'>';
							if(empty($field["request"]))
								$_table .= '<option value="">'.Loc::getMessage("MICROM_ARTICLE_IB_SELECT").'</option>';
							foreach ($field["values"] as $k => $v)
								$_table .= '<option value="'.$k.'"'.($this->_arField[$key] == $k ? ' selected="selected"' : '').'>'.$v.'</option>';
						$_table .= '</select>
						</td>
					</tr>';
					break;
			}
		}
		return $_table;
	} // end function getField()

	private function getFieldVal($code){
		if($this->request->isPost()){
			$form = $this->request->getPost($this->prefix);
			return $form["subschema"]["values"][$code];
		}elseif ($this->_arField["type"] == $type){
			return $this->_arField[$code];
		}

		return $this->option["form"][$code]["default"];
	}
	/**
	 * Возращает принадлежность к каталогу
	 * @param  int     $id ИД инфоблока
	 * @return boolean	   если удачно true
	 */
	public static function isCatlog($id){
		$mxResult = \CCatalogSKU::GetInfoByProductIBlock($id);
		if (is_array($mxResult))
			return true;
		else
			return is_array(\CCatalogSKU::GetInfoByOfferIBlock($id));

	    return false;
	} // end function isCatlog()

	private function getPropertyList(){
		if(!empty($this->_arProp))return $this->_arProp;

		$this->_arProp = array();
		$properties = \CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID" => $this->iblockId));
		while ($prop = $properties->GetNext())
		{
		  	$this->_arProp[(empty($prop["CODE"]) ? $prop["CODE"] : $prop["ID"])] = $prop["NAME"];
		}
		return $this->_arProp;
	}
	/**
	 * сохраняет значение всех полей артикуля для инфоблока
	 * @param  array  &$arFields Ссылка на список полей настроек инфоблока
	 * @return [type]            если удачно true, иначе вывод ошибки
	 */
	public static function saveSettingArticle(&$arFields){
		$_obj = static::getInstance();
		if($_obj->break)return true;

		$errors = array();
		if($_obj->request->isPost()):
			$_post = $_obj->request->getPost($_obj->prefix);

			$_active = array();
			$active = Option::get(self::$module_id, "article_ib_active");
			if(!empty($active)){
				$_active = unserialize($active);
			}
			if(!isset($_post["article_ib_active"]) && ($key = array_search($_obj->iblockId, $_active)) !== false)
				unset($_active[$key]);
			elseif(!empty($_post["article_ib_active"]) && !in_array($_post["article_ib_active"], $_active))
				$_active[] = $_post["article_ib_active"];

			Option::set(self::$module_id, "article_ib_active", serialize($_active));

			if(!empty($_post["subschema"]) && !empty($_post["subschema"]["values"])):
				$saveArr = $_post["subschema"]["values"];

				foreach ($_obj->option["form"] as $key => $field) {
					if($field['request']){
						if(empty($saveArr[$key]))
							$errors[] = Loc::getMessage("MICROM_ARTICLE_IB_ERROR_".$key);
					}
				}
				if(empty($errors)):
					$SITE_ID = false;
					$rsSites = \CSite::GetList($by="sort", $order="desc", Array("DEFAULT" => "Y"));
					if ($arSite = $rsSites->Fetch())
						$SITE_ID = $arSite["ID"];

					$saveArr["type"] = $_post["subschema"]["type"];
					$arField = array(
						"SITE_ID" => $SITE_ID,
						"CODE" => "article_ib_".$_obj->iblockId,
						"VALUE" => $saveArr
					);
					$result = MicromTable::getList(array(
							"select"	=> array("ID"),
							"filter"	=> array("SITE_ID" => $SITE_ID, "CODE" => "article_ib_".$_obj->iblockId),
							"limit"		=> array()
						)
					);
					if($arr = $result->fetch())
						$result = MicromTable::Update($arr["ID"], $arField);
					else
						$result = MicromTable::Add($arField);

					if(!$result->isSuccess()){
						$error = $result->getErrorMessages();
						$strWarning = "";
						foreach ($error as $er) {
							if(is_array($er)){
								$strWarning .= implode("\n",$er);
							}else{
								$strWarning .= trim($er);
							}
						}
						$errors[] = $strWarning;
					}
				endif;
			endif;
		endif;

		if(!empty($error)){
        	$GLOBALS["APPLICATION"]->throwException(implode("\n", $error));
        	return false;
		}

        return true;
	}
	/**
	 * Возращает значение всех сохраненных полей артикуля для инфоблока
	 * @return array $_arr Массив всех полей
	 */
	public function getData(){
		if(!empty($this->_arField))return $this->_arField;

		$SITE_ID = false;
		$rsSites = \CSite::GetList($by="sort", $order="desc", Array("DEFAULT" => "Y"));
		if ($arSite = $rsSites->Fetch())
			$SITE_ID = $arSite["ID"];

		$_arr = array();
		$_arr["article_ib_active"] = array();

		$active = Option::get(self::$module_id, "article_ib_active");
		if(!empty($active))
			$_arr["article_ib_active"] = unserialize($active);

		$result = MicromTable::getList(array(
				"select"	=> array("ID", "VALUE"),
				"filter"	=> array("SITE_ID" => $SITE_ID, "CODE" => "article_ib_".$this->iblockId),
				"limit"		=> array()
			)
		);
		if($saveVal = $result->fetch())
			$_arr = array_merge($_arr, $saveVal["VALUE"]);

		$this->_arField = $_arr;
		return $_arr;
	}

}