<?
use Bitrix\Main;

class CSeoMetaTagsPrice extends CSeoMetaTags
{
	public function calculate(array $parameters)
	{
		$return = array();
		$Property = $parameters[0];
		$PriceType = $parameters[1];

		if($Property == "MIN")
		{
			if(isset(parent::$FilterResult['ITEMS'][$PriceType]['VALUES']['MIN']['VALUE']))
				$return[] = parent::$FilterResult['ITEMS'][$PriceType]['VALUES']['MIN']['VALUE'];
		}
		elseif($Property == "MAX")
		{
			if(isset(parent::$FilterResult['ITEMS'][$PriceType]['VALUES']['MAX']['VALUE']))
				$return[] = parent::$FilterResult['ITEMS'][$PriceType]['VALUES']['MAX']['VALUE'];
		}
		elseif($Property == "MIN_FILTER")
		{
			if(isset(parent::$FilterResult['ITEMS'][$PriceType]['VALUES']['MIN']['HTML_VALUE']))
				$return[] = parent::$FilterResult['ITEMS'][$PriceType]['VALUES']['MIN']['HTML_VALUE'];
			else
				$return[] = parent::$FilterResult['ITEMS'][$PriceType]['VALUES']['MIN']['VALUE'];
		}
		elseif($Property == "MAX_FILTER")
		{
			if(isset(parent::$FilterResult['ITEMS'][$PriceType]['VALUES']['MAX']['HTML_VALUE']))
				$return[] = parent::$FilterResult['ITEMS'][$PriceType]['VALUES']['MAX']['HTML_VALUE'];
			else
				$return[] = parent::$FilterResult['ITEMS'][$PriceType]['VALUES']['MAX']['VALUE'];
		}

		return $return;
	}
}
?>