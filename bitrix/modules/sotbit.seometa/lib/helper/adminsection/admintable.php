<?php
namespace Sotbit\Seometa\Helper\AdminSection;

use CAdminForm;

class AdminTable extends CAdminForm
{
    function AddTextField($id, $label, $value = false, $arParams=array(), $required=false, $propMenu = [])
    {
        if($value === false)
            $value = htmlspecialcharsbx($this->arFieldValues[$id]);
        else
            $value = htmlspecialcharsbx(htmlspecialcharsback($value));

        $html = '<textarea name="'.$id.'"';
        if(intval($arParams["cols"]) > 0)
            $html .= ' cols="'.intval($arParams["cols"]).'"';
        if(intval($arParams["rows"]) > 0)
            $html .= ' rows="'.intval($arParams["rows"]).'"';
        if($arParams['textarea'])
            $html .= ' '.implode(' ', $arParams["textarea"]);
        $html .= '>'.$value.'</textarea>';

        if($propMenu) {
            $html .= '<td';
            if($arParams['propmenu'])
                $html .= ' ' . $arParams['propmenu'] .'>';
            $html .= $propMenu . '</td>';
        }

        $this->tabs[$this->tabIndex]["FIELDS"][$id] = array(
            "id" => $id,
            "required" => $required,
            "content" => $label,
            "html" => '<td width="40%">'.($required? '<span class="adm-required-field">'.$this->GetCustomLabelHTML($id, $label).'</span>': $this->GetCustomLabelHTML($id, $label)).'</td><td>'.$html.'</td>',
            "hidden" => '<input type="hidden" name="'.$id.'" value="'.$value.'">',
            "valign" => "top",
        );
    }

    function addEditField($id, $content, $required = false, $arParams = array(), $value = false, $PropMenu = '') {
        if($value === false)
            $value = htmlspecialcharsbx($this->arFieldValues[$id]);
        else
            $value = htmlspecialcharsbx(htmlspecialcharsback($value));

        $html = '<input type="text" name="'.$id.'" value="'.$value.'"';
        if(intval($arParams["size"]) > 0)
            $html .= ' size="'.intval($arParams["size"]).'"';
        if(intval($arParams["maxlength"]) > 0)
            $html .= ' maxlength="'.intval($arParams["maxlength"]).'"';
        if($arParams["id"])
            $html .= ' id="'.htmlspecialcharsbx($arParams["id"]).'"';
        if($arParams["readonly"])
            $html .= ' readonly';

        $html .= ' class="adm-input"';
        $html .= '>';

        $html = '<td width="40%">'.($required? '<span class="adm-required-field">'.$this->GetCustomLabelHTML($id, $content).'</span>': $this->GetCustomLabelHTML($id, $content)).'</td><td class="adm-input-wrap">'.$html.'</td>';

        if($PropMenu) {
            $html .= '<td>' . $PropMenu . '</td>';
        }

        $this->tabs[$this->tabIndex]["FIELDS"][$id] = array(
            "id" => $id,
            "required" => $required,
            "content" => $content,
            "html" => $html,
            "hidden" => '<input type="hidden" name="'.$id.'" value="'.$value.'">',
        );
    }

    function addFileField ($id, $label, $value, $arParams=array(), $required=false) {
        $html = \Bitrix\Main\UI\FileInput::createInstance(array(
            "name" => $id,
            "upload" => true,
            "allowUpload" => "I",
            "medialib" => true,
            "fileDialog" => true,
            "cloud" => true,
            "delete" => true,
            "maxCount" => 1
        ))->show($value ?: '');

        $this->tabs[$this->tabIndex]["FIELDS"][$id] = array(
            "id" => $id,
            "required" => $required,
            "content" => $label,
            "html" => '<td width="40%">'.($required? '<span class="adm-required-field">'.$this->GetCustomLabelHTML($id, $label).'</span>': $this->GetCustomLabelHTML($id, $label)).'</td><td>'.$html.'</td>',
            "valign" => "top",
            "rowClass" => "adm-detail-file-row"
        );
    }

    function fileSave() {
        $result = '';

        return $result;
    }

    function addNoteField($id, $note) {
        $html = BeginNote();
        $html .= $note;
        $html .= EndNote();

        $this->tabs[$this->tabIndex]["FIELDS"][$id] = array(
            "id" => $id,
            "html" => '<td width="40%"></td><td>'.$html.'</td>',
            "valign" => "top",
        );
    }
}