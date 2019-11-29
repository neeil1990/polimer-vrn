<?
use Bitrix\Main;

class CSeoMetaTags extends CSeoMeta
{
	public function Event($tag)
	{
		if($tag === "productproperty" || $tag === "offerproperty")
			return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, "\\CSeoMetaTagsProperty");
		elseif($tag === "price")
			return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, "\\CSeoMetaTagsPrice");
	}

	function EventHandler(Bitrix\Main\Event $event)
	{
		$arParam = $event->getParameters();
		$functionClass = $arParam[0];
		if(is_string($functionClass) && class_exists($functionClass))
			$result = new Bitrix\Main\EventResult(1, $functionClass);

		return $result;
	}
}

?>