<?php                                                                    
require_once ($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_admin_before.php"); 
use Sotbit\Seometa\SeometaUrlTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Type;

if (!$USER->CanDoOperation( 'sotbit.seometa' ))
{
    $APPLICATION->AuthForm( Loc::getMessage( "ACCESS_DENIED" ) );
}
Loader::includeModule( 'sotbit.seometa' );
Loader::includeModule( 'iblock' );

$chpuAll =  SeometaUrlTable::GetList(array('filter'=>array('!=PROPERTIES'=>null)));   
while($chpu = $chpuAll->fetch()){
    $cond_properties = unserialize($chpu['PROPERTIES']);   
    
    $arFilter = array(
        'ACTIVE' => 'Y',        
        'INCLUDE_SUBSECTIONS' => 'Y',        
        'IBLOCK_ID' => $chpu['iblock_id'],
        'SECTION_ID' => $chpu['section_id'], 
    ); 
    foreach($cond_properties as $code => $vals){
        if($code!='PRICE'){      
            if(intval($code))
                $pr = \CIBlockProperty::GetList(array(), array('ID'=>$code))->fetch();
            else
                $pr = \CIBlockProperty::GetList(array(), array('CODE'=>$code))->fetch();
            if($pr['PROPERTY_TYPE']!='L' && $pr['PROPERTY_TYPE']!='E')
                $arFilter['PROPERTY_'.$pr['ID']] = $vals;
            else 
                $arFilter['PROPERTY_'.$pr['ID'].'_VALUE'] = $vals;
        } else {
            foreach($vals as $price_code => $price){
                if(isset($price['FROM']) && $price['FROM']!=='')
                    $arFilter['>=CATALOG_PRICE_'.$price_code] = $price['FROM'];
                if(isset($price['TO']) && $price['TO']!=='')
                    $arFilter['<=CATALOG_PRICE_'.$price_code] = $price['TO'];  
            }
        }
    }                              
    $count = \CIBlockElement::GetList(array(),$arFilter)->SelectedRowsCount(); 
    SeometaUrlTable::Update($chpu['ID'], array('PRODUCT_COUNT'=>$count,'DATE_CHANGE'=>new Type\DateTime( date( 'Y-m-d H:i:s' ), 'Y-m-d H:i:s' )));     
    unset($chpu);
}
unset($chpuAll);
?>       
