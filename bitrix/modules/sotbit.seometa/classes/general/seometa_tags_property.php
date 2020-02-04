<?
use Sotbit\Seometa\ConditionTable;
use Bitrix\Main\Loader;

Loader::includeModule('iblock');

class CSeoMetaTagsProperty extends CSeoMetaTags
{
	public static $params = array();

    /**
     * function collect values of property that in furute won't find in database
     * @param string $propertyCode
     * @param array $array - there are necessary fields of property
     * */
    private function addFilterValue($propertyCode, $array)
    {
        if(is_array(self::$FilterResult['ITEMS']))
        {
            foreach (self::$FilterResult['ITEMS'] as $idProrepty => &$Property)
                if ($Property['CODE'] == $propertyCode)
                    if (!isset($Property['VALUES'][$array['VALUE']]))
                        $Property['VALUES'][$array['VALUE']] = $array;
        }
        else
        {
            self::$FilterResult['ITEMS'][$propertyCode]['VALUES'][$array['VALUE']] = $array;
        }
    }

    /**
     * find value of property from FilterResult by field with name CONTROL_NAME_SEF, if we don't find value, do query to database and save in FilterResult
     * @param string $propertyCode
     * @param string $sefCode
     * @return string
     * */
    private function getPropertyValueBySef($propertyCode, $sefCode)
    {
		if(empty($sefCode))
			return '';

        if(is_array(self::$FilterResult['ITEMS']))
            foreach(self::$FilterResult['ITEMS'] as $idProrepty => $Property)
                if($Property['CODE'] == $propertyCode)
                    foreach($Property['VALUES'] as $idVal => $Value)
                        if($Value['CONTROL_NAME_SEF'] == $sefCode)
                        {
                            return $Value['VALUE'];
                        }

        /*$properties = CIBlockElement::GetList(
            array(
                "SORT" => "ASC",
                //'PROPERTY_CODE' => $propertyCode,
                "ACTIVE" => "Y"
            ),
            array(
                //"PROPERTY_CODE" => $propertyCode
                "!PROPERTY_$propertyCode" => false
            ),
            false,
            false,
            array("ID", "IBLOCK_ID", "NAME", "CODE", "PROPERTY_ID", "XML_ID", "PROPERTY_$propertyCode")
        );

        while ($prop_fields = $properties->GetNext())
        {
            if(\CUtil::translit($prop_fields["PROPERTY_".$propertyCode."_VALUE"], 'ru', array("replace_space" => "-", "replace_other" => "-")) == $sefCode)
            {
                self::addFilterValue($propertyCode, array(
                    'VALUE' => $prop_fields["PROPERTY_".$propertyCode."_VALUE"],
                    'CONTROL_NAME_SEF' => strtolower(\CUtil::translit($prop_fields["PROPERTY_".$propertyCode."_VALUE"], 'ru', array("replace_space" => "-", "replace_other" => "-")))
                ));
                return $prop_fields["PROPERTY_".$propertyCode."_VALUE"];
            }
        }*/

		return $sefCode;
    }

	public function calculate(array $parameters)
	{
		$return = array();
		$Property = $parameters;
        $codes = array();

        if(empty(parent::$FilterResult['ITEMS']))
        {
            foreach($Property as $prop)
                if(isset(self::$params[$prop]))
                {
                    if(is_array(self::$params[$prop]))
                        foreach(self::$params[$prop] as $pr)
                            $return[] = self::getPropertyValueBySef($prop, $pr);
                    else
                        $return[] = self::getPropertyValueBySef($prop, self::$params[$prop]);
                }

            return $return;
        }

		if(!empty(self::$params))
		{
            foreach(self::$params as $code => $values)
            {
                if(in_array($code, $Property))
                {
                    foreach(parent::$FilterResult['ITEMS'] as $key => $elements)
                    {
                        if($elements['CODE'] == $code)
                        {
                            foreach ($values as $sef_name)
                            {
                                foreach($elements['VALUES'] as $key_val => $value)
                                {
									if(
									    strcmp(\CUtil::translit($value['VALUE'], 'ru', array("replace_space" => "-", "replace_other" => "-")), $sef_name) == 0 ||
                                        strcmp($value['VALUE_ID'], $sef_name) == 0 ||
                                        $key_val == $sef_name
                                    )
									{
										/*if($elements['USER_TYPE'] == 'directory' && $elements['PROPERTY_TYPE'] == 'S')
											$return[] = $value['VALUE_LIST'];
										else*/
											$return[] = $value['VALUE'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
		}
        elseif(is_array($Property))
        {
            foreach(parent::$FilterResult['ITEMS'] as $key => $elements)
            {
                foreach($Property as $prop)
                {
                    if($prop == $elements['CODE'] && !isset($codes[$elements['CODE']]))
                    {
                        $codes[$elements['CODE']] = "Y";
                        foreach($elements['VALUES'] as $key_element => $element)
                        {
                            if($element['CHECKED'] == 1)
                            {
                                if($elements['PROPERTY_TYPE'] == 'S' && $elements['USER_TYPE'] == 'directory' && $element['VALUE']) //hak for HL because isset LIST_TYPE = ID
                                {
                                    $return[] = $element['VALUE'];
                                }
                                else
                                {
                                    if(isset($element['LIST_VALUE_NAME']))
                                    {
                                        $return[] = $element['LIST_VALUE_NAME'];
                                    }
                                    elseif(isset($element['LIST_VALUE']))
                                    {
                                        $return[] = $element['LIST_VALUE'];
                                    }
                                    else
                                    {
                                        $return[] = $element['VALUE'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
		else
		{
			foreach(parent::$FilterResult['ITEMS'] as $key => $elements)
			{
				if( $Property == $elements['CODE'] && !isset( $codes[$elements['CODE']] ) )
				{
					$codes[$elements['CODE']] = "Y";
					foreach( $elements['VALUES'] as $key_element => $element )
					{
						if($element['CHECKED'] == 1)
						{
							if( isset( $element['LIST_VALUE'] ) )
							{
								$return[] = $element['LIST_VALUE'];
							}
							else
							{
								$return[] = $element['VALUE'];
							}
						}
					}
				}
			}
		}

		if(empty($return))
		  	foreach($Property as $prop)
				if(isset(self::$params[$prop]))
				{
					if(is_array(self::$params[$prop]))
						foreach(self::$params[$prop] as $pr)
							$return[] = self::getPropertyValueBySef($prop, $pr);
					else
						$return[] = self::getPropertyValueBySef($prop, self::$params[$prop]);
				}

        if(empty($return))
            foreach (self::$params as $item) {
                if (isset($item['FROM']))
                    $return[] = $item['FROM'];
                if(isset($item['TO']) && $item['FROM'] != $item['TO'])
                    $return[] = $item['TO'];
            }

		return $return;
	}
}

?>