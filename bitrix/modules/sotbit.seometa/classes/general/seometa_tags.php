<?

use Bitrix\Main\EventResult;

class CSeoMetaTags extends CSeoMeta
{
	public static function Event($tag)
	{
        if ($tag === "productproperty" || $tag === "offerproperty") {
            return new EventResult(EventResult::SUCCESS,
                "\\CSeoMetaTagsProperty");
        } elseif ($tag === "price") {
            return new EventResult(EventResult::SUCCESS,
                "\\CSeoMetaTagsPrice");
        } elseif ($tag === 'first_upper') {
            return new EventResult(EventResult::SUCCESS,
                "\\first_upper");
        } elseif ($tag === 'nonfirst') {
            return new EventResult(EventResult::SUCCESS,
                "\\nonfirst");
        } elseif ($tag === 'iffilled') {
            return new EventResult(EventResult::SUCCESS,
                "\\iffilled");
        } elseif ($tag === 'prop_list') {
            return new EventResult(EventResult::SUCCESS,
                "\\prop_list");
        }
	}

	function EventHandler(Bitrix\Main\Event $event)
	{
		$arParam = $event->getParameters();
		$functionClass = $arParam[0];
		if(is_string($functionClass) && class_exists($functionClass))
			$result = new EventResult(1, $functionClass);

		return $result;
	}
}

?>