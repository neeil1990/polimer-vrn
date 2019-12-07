<?
namespace Sotbit\Schemaorg\Helper;

use Sotbit\Schemaorg\SchemaPageMetaTable;
use Sotbit\Schemaorg\SchemaCategoryTable;

class SchemaMetaAction {
    public static function simplePageAction ($ID, $action) {
        switch($action) {
            case 'delete' :
                SchemaPageMetaTable::delete($ID);
                break;
            case 'active':
                SchemaPageMetaTable::update($ID, array('ACTIVE' => 'Y'));
                break;
            case 'deactive':
                SchemaPageMetaTable::update($ID, array('ACTIVE' => 'N'));
                break;
        }
    }

    public static function groupAction ($idList, $action) {

        if($action != 'edit' && $action != 'delete')
            $idList = array_map(function($v){
                return preg_replace('/[a-zAA-Z]+/ui', '', $v);
            }, $idList);

        switch($action) {
            case 'delete' :
                foreach($idList as $id)  {
                    if(strpos($id, "E") !== false) {
                        $id = str_replace(array('E', 'S'), array('',''), $id);
                        $entity = '\Sotbit\Schemaorg\SchemaPageMetaTable';
                        $entity::delete($id);
                    }else {
                        $id = str_replace(array('E', 'S'), array('',''), $id);
                        $entity = '\Sotbit\Schemaorg\SchemaCategoryTable';
                        $entity::deleteEntities($id);
                    }
                }
                break;
            case 'active':
                foreach($idList as $id)
                    SchemaPageMetaTable::update($id, array('ACTIVE' => 'Y'));
                break;
            case 'deactive':
                foreach($idList as $id)
                    SchemaPageMetaTable::update($id, array('ACTIVE' => 'N'));
                break;
            case 'edit':
                foreach($idList as $id => $arFields) {
                    unset($arFields['DATE_CREATE']);
                    unset($arFields['TIMESTAMP_X']);

                    if(strpos($id, "E") !== false) {
                        $entity = '\Sotbit\Schemaorg\SchemaPageMetaTable';
                    }else {
                        $entity = '\Sotbit\Schemaorg\SchemaCategoryTable';
                    }

                    $id = str_replace(array('E', 'S'), array('',''), $id);

                    $entity::update($id, $arFields);
                }
                break;
        }
    }


    public static function simpleCategoryAction ($ID, $action)
    {
        switch ($action) {
            case 'delete' :
                SchemaCategoryTable::deleteEntities($ID);
                break;
        }
    }
}
?>