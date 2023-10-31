<?php
/**
 * @link      http://wsrubi.ru/dev/bitrixsmtp/
 * @author Sergey Blazheev <s.blazheev@gmail.com>
 * @copyright Copyright (c) 2011-2015 Altair TK. (http://www.wsrubi.ru)
 */
global	$module_id;
if(!$USER->CanDoOperation('view_event_log'))
$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));


$bStatistic = CModule::IncludeModule('statistic');
$sTableID = "tblLogList";
$oSort = new CAdminSorting($sTableID, "ID", "DESC");
$lAdmin = new CAdminList($sTableID, $oSort);

if(isset($_REQUEST["mode"]) && $_REQUEST["mode"] == "excel")
$arNavParams = false;
else
$arNavParams = array("nPageSize"=>CAdminResult::GetNavSize($sTableID));
$arFilter['MODULE_ID'] = $module_id;
/** @global string $by  */
/** @global string $order  */
$rsData = CEventLog::GetList(array("ID" => "DESC"), $arFilter, $arNavParams);
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint(GetMessage("MAIN_EVENTLOG_LIST_PAGE")));
$arHeaders = array(
array(
"id" => "ID",
"content" => GetMessage("MAIN_EVENTLOG_ID"),
"sort" => "ID",
"default" => true,
"align" => "right",
),
array(
"id" => "TIMESTAMP_X",
"content" => GetMessage("MAIN_EVENTLOG_TIMESTAMP_X"),
"sort" => "TIMESTAMP_X",
"default" => true,
"align" => "right",
),
array(
"id" => "SEVERITY",
"content" => GetMessage("MAIN_EVENTLOG_SEVERITY"),
),
array(
"id" => "AUDIT_TYPE_ID",
"content" => GetMessage("MAIN_EVENTLOG_AUDIT_TYPE_ID"),
"default" => true,
),
array(
"id" => "MODULE_ID",
"content" => GetMessage("MAIN_EVENTLOG_MODULE_ID"),
),
array(
"id" => "ITEM_ID",
"content" => GetMessage("MAIN_EVENTLOG_ITEM_ID"),
"default" => true,
),
array(
"id" => "REMOTE_ADDR",
"content" => GetMessage("MAIN_EVENTLOG_REMOTE_ADDR"),
"default" => true,
),
array(
"id" => "USER_AGENT",
"content" => GetMessage("MAIN_EVENTLOG_USER_AGENT"),
),
array(
"id" => "REQUEST_URI",
"content" => GetMessage("MAIN_EVENTLOG_REQUEST_URI"),
"default" => true,
),
array(
"id" => "SITE_ID",
"content" => GetMessage("MAIN_EVENTLOG_SITE_ID"),
),
array(
"id" => "USER_ID",
"content" => GetMessage("MAIN_EVENTLOG_USER_ID"),
"default" => true,
),
array(
"id" => "DESCRIPTION",
"content" => GetMessage("MAIN_EVENTLOG_DESCRIPTION"),
"default" => true,
),
);
if($bStatistic)
$arHeaders[] = array(
"id" => "GUEST_ID",
"content" => GetMessage("MAIN_EVENTLOG_GUEST_ID"),
);

$lAdmin->AddHeaders($arHeaders);
$arUsersCache = array();
$arGroupsCache = array();
$arForumCache = array("FORUM" => array(), "TOPIC" => array(), "MESSAGE" => array());
$a_ID = $a_AUDIT_TYPE_ID = $a_GUEST_ID = $a_USER_ID = $a_ITEM_ID = $a_REQUEST_URI = $a_DESCRIPTION = $a_REMOTE_ADDR = '';
while($db_res = $rsData->NavNext(true, "a_"))
{
$row =& $lAdmin->AddRow($a_ID, $db_res);
$row->AddViewField("AUDIT_TYPE_ID", (isset($arAuditTypes)&&is_array($arAuditTypes)&&array_key_exists($a_AUDIT_TYPE_ID, $arAuditTypes))? preg_replace("/^\\[.*?\\]\\s+/", "", $arAuditTypes[$a_AUDIT_TYPE_ID]): $a_AUDIT_TYPE_ID);
if($bStatistic && strlen($a_GUEST_ID))
{
$row->AddViewField("GUEST_ID", '<a href="/bitrix/admin/hit_list.php?lang='.LANGUAGE_ID.'&amp;set_filter=Y&amp;find_guest_id='.$a_GUEST_ID.'&amp;find_guest_id_exact_match=Y">'.$a_GUEST_ID.'</a>');
}
if($a_USER_ID)
{
if(!array_key_exists($a_USER_ID, $arUsersCache))
{
$rsUser = CUser::GetByID($a_USER_ID);
if($arUser = $rsUser->GetNext())
{
$arUser["FULL_NAME"] = $arUser["NAME"].(strlen($arUser["NAME"])<=0 || strlen($arUser["LAST_NAME"])<=0?"":" ").$arUser["LAST_NAME"];
}
$arUsersCache[$a_USER_ID] = $arUser;
}
if($arUsersCache[$a_USER_ID])
$row->AddViewField("USER_ID", '[<a href="user_edit.php?lang='.LANG.'&ID='.$a_USER_ID.'">'.$a_USER_ID.'</a>] '.$arUsersCache[$a_USER_ID]["FULL_NAME"]);
}
if($a_ITEM_ID)
{
switch($a_AUDIT_TYPE_ID)
{
case "USER_AUTHORIZE":
case "USER_LOGOUT":
case "USER_REGISTER":
case "USER_INFO":
case "USER_PASSWORD_CHANGED":
case "USER_DELETE":
case "USER_GROUP_CHANGED":
if(!array_key_exists($a_ITEM_ID, $arUsersCache))
{
$rsUser = CUser::GetByID($a_ITEM_ID);
if($arUser = $rsUser->GetNext())
{
$arUser["FULL_NAME"] = $arUser["NAME"].(strlen($arUser["NAME"])<=0 || strlen($arUser["LAST_NAME"])<=0?"":" ").$arUser["LAST_NAME"];
}
$arUsersCache[$a_ITEM_ID] = $arUser;
}
if($arUsersCache[$a_ITEM_ID])
$row->AddViewField("ITEM_ID", '[<a href="user_edit.php?lang='.LANG.'&amp;ID='.$a_ITEM_ID.'">'.$a_ITEM_ID.'</a>] '.$arUsersCache[$a_ITEM_ID]["FULL_NAME"]);
break;
case "GROUP_POLICY_CHANGED":
case "MODULE_RIGHTS_CHANGED":
if(!array_key_exists($a_ITEM_ID, $arGroupsCache))
{
$rsGroup = CGroup::GetByID($a_ITEM_ID);
if($arGroup = $rsGroup->GetNext())
$arGroupsCache[$a_ITEM_ID] = $arGroup["NAME"];
else
$arGroupsCache[$a_ITEM_ID] = "";
}
$row->AddViewField("ITEM_ID", '[<a href="group_edit.php?lang='.LANG.'&amp;ID='.$a_ITEM_ID.'">'.$a_ITEM_ID.'</a>] '.$arGroupsCache[$a_ITEM_ID]);
break;
case "TASK_CHANGED":
$rsTask = CTask::GetByID($a_ITEM_ID);
if($arTask = $rsTask->GetNext())
$row->AddViewField("ITEM_ID", '[<a href="task_edit.php?lang='.LANG.'&amp;ID='.$a_ITEM_ID.'">'.$a_ITEM_ID.'</a>] '.$arTask["NAME"]);
break;
case "FORUM_MESSAGE_APPROVE":
case "FORUM_MESSAGE_UNAPPROVE":
case "FORUM_MESSAGE_MOVE":
case "FORUM_MESSAGE_EDIT":
if (intval($a_ITEM_ID) <= 0):
continue;
elseif (!array_key_exists($a_ITEM_ID, $arForumCache["MESSAGE"])):
CModule::IncludeModule("forum");
$res = CForumMessage::GetByID($a_ITEM_ID);
$res["MESSAGE_ID"] = $res["ID"];
$arForumCache["MESSAGE"][$a_ITEM_ID] = $res;
else:
$res = $arForumCache["MESSAGE"][$a_ITEM_ID];
endif;
if (!array_key_exists($res["FORUM_ID"], $arForumCache["FORUM"])):
$arForumCache["FORUM"][$res["FORUM_ID"]] = CForumNew::GetByID($res["FORUM_ID"]);
if ($arForumCache["FORUM"][$res["FORUM_ID"]]):
$arSitesPath = CForumNew::GetSites($res["FORUM_ID"]);
$arForumCache["FORUM"][$res["FORUM_ID"]]["PATH"] = array_shift($arSitesPath);
endif;
endif;
if ($arForumCache["FORUM"][$res["FORUM_ID"]]["PATH"]):
$sPath = CForumNew::PreparePath2Message($arForumCache["FORUM"][$res["FORUM_ID"]]["PATH"], $res);
$row->AddViewField("ITEM_ID", '[<a href="'.$sPath.'">'.$a_ITEM_ID.'</a>] '.GetMessage("MAIN_EVENTLOG_FORUM_MESSAGE"));
else:
$row->AddViewField("ITEM_ID", '['.$a_ITEM_ID.'] '.GetMessage("MAIN_EVENTLOG_FORUM_MESSAGE"));
endif;
break;
case "FORUM_TOPIC_APPROVE":
case "FORUM_TOPIC_UNAPPROVE":
case "FORUM_TOPIC_STICK":
case "FORUM_TOPIC_UNSTICK":
case "FORUM_TOPIC_OPEN":
case "FORUM_TOPIC_CLOSE":
case "FORUM_TOPIC_MOVE":
case "FORUM_TOPIC_EDIT":
if (intval($a_ITEM_ID) <= 0):
continue;
elseif (!array_key_exists($a_ITEM_ID, $arForumCache["TOPIC"])):
CModule::IncludeModule("forum");
$res = CForumTopic::GetByID($a_ITEM_ID);
$res["MESSAGE_ID"] = $res["LAST_MESSAGE_ID"];
$res["TOPIC_ID"] = $res["ID"];
$arForumCache["TOPIC"][$a_ITEM_ID] = $res;
else:
$res = $arForumCache["TOPIC"][$a_ITEM_ID];
endif;
if (!array_key_exists($res["FORUM_ID"], $arForumCache["FORUM"])):
$arForumCache["FORUM"][$res["FORUM_ID"]] = CForumNew::GetByID($res["FORUM_ID"]);
if ($arForumCache["FORUM"][$res["FORUM_ID"]]):
$arSitesPath = CForumNew::GetSites($res["FORUM_ID"]);
$arForumCache["FORUM"][$res["FORUM_ID"]]["PATH"] = array_shift($arSitesPath);
endif;
endif;
if ($arForumCache["FORUM"][$res["FORUM_ID"]]["PATH"]):
$sPath = CForumNew::PreparePath2Message($arForumCache["FORUM"][$res["FORUM_ID"]]["PATH"], $res);
$row->AddViewField("ITEM_ID", '[<a href="'.$sPath.'">'.$a_ITEM_ID.'</a>] '.GetMessage("MAIN_EVENTLOG_FORUM_TOPIC"));
else:
$row->AddViewField("ITEM_ID", '['.$a_ITEM_ID.'] '.GetMessage("MAIN_EVENTLOG_FORUM_TOPIC"));
endif;
break;
case "FORUM_MESSAGE_DELETE":
$row->AddViewField("ITEM_ID", '['.$a_ITEM_ID.'] '.GetMessage("MAIN_EVENTLOG_FORUM_MESSAGE"));
break;
case "FORUM_TOPIC_DELETE":
$row->AddViewField("ITEM_ID", '['.$a_ITEM_ID.'] '.GetMessage("MAIN_EVENTLOG_FORUM_TOPIC"));
break;
case "IBLOCK_SECTION_ADD":
case "IBLOCK_SECTION_EDIT":
case "IBLOCK_SECTION_DELETE":
case "IBLOCK_ELEMENT_ADD":
case "IBLOCK_ELEMENT_EDIT":
case "IBLOCK_ELEMENT_DELETE":
case "IBLOCK_ADD":
case "IBLOCK_EDIT":
case "IBLOCK_DELETE":
$elementLink = CIBlock::GetAdminElementListLink($a_ITEM_ID, array('filter_section'=>-1));
parse_str($elementLink);
if (empty($type))
{
$a_ITEM_ID = GetMessage("MAIN_EVENTLOG_IBLOCK_DELETE");
}
else
{
if(CModule::IncludeModule('iblock'))
$a_ITEM_ID = '<a href="'.htmlspecialcharsbx($elementLink).'">'.$a_ITEM_ID.'</a>';
}

$row->AddViewField("ITEM_ID", '['.$a_ITEM_ID.'] '.GetMessage("MAIN_EVENTLOG_IBLOCK"));
break;
}
}
if(strlen($a_REQUEST_URI))
{
$row->AddViewField("REQUEST_URI", htmlspecialcharsbx($a_REQUEST_URI));
}
if(strlen($a_DESCRIPTION))
{
if(strncmp("==", $a_DESCRIPTION, 2)===0)
$DESCRIPTION = htmlspecialcharsbx(base64_decode(substr($a_DESCRIPTION, 2)));
else
$DESCRIPTION = $a_DESCRIPTION;
//htmlspecialcharsback for <br> <BR> <br/>
$DESCRIPTION = preg_replace("#(&lt;)(\\s*br\\s*/{0,1})(&gt;)#is", "<\\2>", $DESCRIPTION);
$row->AddViewField("DESCRIPTION", $DESCRIPTION);
}
if($bStatistic && $a_REMOTE_ADDR)
{
$arr = explode(".", $a_REMOTE_ADDR);
if(count($arr)==4)
{
$row->AddViewField("REMOTE_ADDR", $a_REMOTE_ADDR.'<br><a href="stoplist_edit.php?lang='.LANGUAGE_ID.'&amp;net1='.intval($arr[0]).'&amp;net2='.intval($arr[1]).'&amp;net3='.intval($arr[2]).'&amp;net4='.intval($arr[3]).'">['.GetMessage("MAIN_EVENTLOG_STOP_LIST").']<a>');
        }
        }
        }

        $aContext = array();
        $lAdmin->AddAdminContextMenu($aContext);
        $lAdmin->CheckListMode();

        $lAdmin->DisplayList();
?>