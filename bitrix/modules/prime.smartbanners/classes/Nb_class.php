<?
IncludeModuleLangFile(__FILE__);

class Nb_class
{
    function GetList($arSort = Array("SORT"=>"ASC"),$arFilter=Array(),$arSelect = Array())
    {
        if(is_array($arSelect))
        {
            if(empty($arSelect))
                $arSelect = array(
                    "ID",
                    "SID",
                    "NAME",
                    "URL",
                    "ACTIVE",
                    "PAUSE",
                    "SHOW_FROM",
                    "SHOW_TO",
                    "SHOW_POSITION",
                    "SHOW_OFF",
                    "SHOW_OUT_SITE",
                    "SHOW_TIME",
                    "OPEN_NEW_WINDOW"
                );
        }
        else
            $arSelect = array("ID","NAME");

        $selectFields = implode(",", $arSelect);

        if(!is_array($arSort) || empty($arSort))
            $arSort = array("SORT"=>"ASC");
        foreach($arSort as $order=>$by)
        {
            $order = $order." ".$by;
            break;
        }

        $selectFields = implode(",", $arSelect);

        if(is_array($arFilter) && count($arFilter)>0)
        {
            foreach ($arFilter as $colName => $colValue)
            {
                if(substr($colName, 0, 1) == "!")
                {
                    $eq = "<>";
                    $colName = str_replace("!", "", $colName);
                }
                else
                    $eq = "=";
                $whereFields[]= "$colName $eq '$colValue'";
            }
            if(count($whereFields)>0)
                $where = implode(" AND ", $whereFields);
        }
        $sql = "SELECT $selectFields FROM b_prime_smartbanners";
        if(strlen($where)>0)
            $sql .= " WHERE $where";
        if(strlen($order)>0)
            $sql .= " ORDER BY $order";
        global $DB;
        return $DB->Query($sql,false);

    }

    function GetByID($ID)
    {
        $ID = intval($ID);
        if($ID>0)
        {
            $sql = "
				SELECT
					ID,SID,NAME,URL,ACTIVE,
					IMAGE_ID,PAUSE,SHOW_FROM,SHOW_TO,SHOW_POSITION,SHOW_OFF,SHOW_OUT_SITE,SHOW_TIME,OPEN_NEW_WINDOW
				FROM b_prime_smartbanners WHERE ID=".$ID
            ;
            global $DB;
            return $DB->Query($sql,false);
        }
    }

    function copyRow($ID){

        $ID = intval($ID);
        if($ID>0)
        {
            $sql = "SELECT
                    SID,
                    NAME,
                    URL,
                    ACTIVE,
                    IMAGE_ID,
                    PAUSE,
                    SHOW_FROM,
                    SHOW_TO,
                    SHOW_OFF,
                    SHOW_POSITION,
                    SHOW_OUT_SITE,
                    SHOW_TIME,
                    OPEN_NEW_WINDOW
	        FROM b_prime_smartbanners WHERE ID=".$ID;
            global $DB;
            $resData = $DB->Query($sql,false);
            while($res = $resData->NavNext(true, "f_")){
                return $ID = $DB->Add("b_prime_smartbanners",$res);
            }
        }
    }

    function Add(&$arFields)
    {
        if(!$this->requireField($arFields))
            return false;

        global $DB;
        $arFields["IMAGE_ID"] = $this->SaveFile($arFields["IMAGE_ID"]);
        return $ID = $DB->Add("b_prime_smartbanners",$arFields);
    }

    function Update($ID,$arFields)
    {
        if(!$this->requireField($arFields))
            return false;

        global $DB;
        $ID = intval($ID);
        if($arFields["IMAGE_ID"]){
            $arFields["IMAGE_ID"] = $this->SaveFile($arFields["IMAGE_ID"],$ID);
        }
        $strUpdate = $DB->PrepareUpdate("b_prime_smartbanners", $arFields);
        if($strUpdate!="")
        {
            $strSql = "UPDATE b_prime_smartbanners SET ".$strUpdate." WHERE ID=".$ID;
            $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
        }
        return true;
    }

    function UpdateActive($ID,$arFields){
        global $DB;
        $ID = intval($ID);
        $strUpdate = $DB->PrepareUpdate("b_prime_smartbanners", $arFields);
        if($strUpdate!="")
        {
            $strSql = "UPDATE b_prime_smartbanners SET ".$strUpdate." WHERE ID=".$ID;
            $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
        }
        return true;
    }

    function SaveFile($arImage,$ID = 0)
    {
        if (is_array($arImage))
        {
            $arImage["MODULE_ID"] = "prime_smartbanners";

            if ($ID>0)
            {
                global $DB;

                $rsBanner= $DB->Query("SELECT IMAGE_ID FROM b_prime_smartbanners WHERE ID='$ID'", false, "<b>Error in </b><br/>File: ".__FILE__."<br/>Line: ".__LINE__."<br/>");
                if($arBanner = $rsBanner->Fetch())
                {
                    $arImage["old_file"] = $arBanner["IMAGE_ID"];
                }

            }

            if (strlen($arImage["name"])>0 || strlen($arImage["del"])>0)
            {
                $fileID = CFile::SaveFile($arImage, $arImage["MODULE_ID"]);
                if (intval($fileID)>0)
                    return intval($fileID);
                else
                    return "";
            }
            elseif($arImage["old_file"]>0)
            {
                return $arImage["old_file"];
            }
        }
        return "";
    }

    function ShowBanner(){
        if(CSite::InDir('/bitrix/'))
            return true;

        global $APPLICATION;
        $sql = "SELECT * FROM b_prime_smartbanners";
        global $DB;
        $resData = $DB->Query($sql,false);
        while($res = $resData->NavNext(true, "f_")){

            switch ($res['SHOW_POSITION']) {
                case "top":
                    $template = $res['SHOW_POSITION'];
                    break;
                case "bottom":
                    $template = $res['SHOW_POSITION'];
                    break;
                default:
                    $template = "center";
            }

            $APPLICATION->IncludeComponent(
                "prime:banner",
                $template,
                $res
            );
        }
    }

    function requireField(&$arFields){

        global $DB, $APPLICATION, $USER;
        $this->LAST_ERROR = "";

        if(is_set($arFields, "NAME") && strlen($arFields["NAME"])<=0)
            $this->LAST_ERROR .= GetMessage("BANNER_BAD_NAME")."<br>";
        if(is_set($arFields, "SID") && strlen($arFields["SID"])<=0)
            $this->LAST_ERROR .= GetMessage("BANNER_BAD_SITE")."<br>";
        $strRes = CFile::CheckImageFile($arFields["IMAGE_ID"], 0, 0, 0);
        if (strlen($strRes)>0)
            $this->LAST_ERROR .= GetMessage("BANNER_BAD_IMAGE")."<br>";

        if(strlen($this->LAST_ERROR)>0)
            return false;
        else
            return true;

    }


    function Delete($ID)
    {
        global $DB,$strError;
        $ID = intval($ID);
        if($ID>0)
        {
            $sql = "SELECT ID,IMAGE_ID FROM b_prime_smartbanners WHERE ID = '$ID'";
            $Banner = $DB->Query($sql, false, "File: ".__FILE__."<br/>Line:".__LINE__);
            if ($Banner = $Banner->Fetch())
            {
                if($Banner["IMAGE_ID"]>0)
                    CFile::Delete($Banner["IMAGE_ID"]);
                $strSql = "DELETE FROM b_prime_smartbanners WHERE ID = '{$Banner["ID"]}'";
                $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
                return true;
            }
        }
        return false;

    }

    function statUpdate($ID,$strUpdate){
        global $DB,$strError;
        $ID = intval($ID);
        if($ID>0 and $strUpdate)
        {
            $strSql = "UPDATE b_prime_smartbanners SET ".$strUpdate." = ".$strUpdate." + 1 WHERE ID = '$ID'";
            $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
            return true;
        }
    }
}
?>
