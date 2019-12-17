<? /**
 *
 * Ўаблон NewsArticle
 *
 * ¬ходной массив
 * @var array [itemListElement]
 */


$Server = Bitrix\Main\Application::getInstance()->getContext()
			->getServer();

$domen = 'http'.($_SERVER['HTTPS'] ? 's' : '').'://'.$Server->getServerName();

?><script type="application/ld+json">{<?
	?>"@context": "http://schema.org",<?
	?>"@type": "NewsArticle",<?
	?>"mainEntityOfPage": {<?
		?>"@type": "WebPage",<?
		?>"@id": "<?=$domen.$Server->getRequestUri();?>"<?
	?>},<?
	?>"headline": "<?=$result['name'];?>",<?

if ($result['picture']):
	?>"image": {<?
		?>"@type": "ImageObject",<?
		?>"url": "<?=$domen.$Server->getServerName().$result['picture']['src'];?>",<?
		?>"height": "<?=$result['picture']['height'];?>px",<?
		?>"width": "<?=$result['picture']['width'];?>px"<?
	?>},<?
endif;

	?>"datePublished": "<?=$result['dateCreate']->format('c');?>",<?
	?>"dateModified": "<?=$result['dateModify']->format('c');?>",<?

	?>"author": {<?
		?>"@type": "Person",<?
		?>"name": "<?=$result['author']['name'];?>"<?
	?>},<?

	?>"publisher": {<?
		?>"@type": "Organization",<?
		?>"name": "<?=$result['organization']['name'];?>",<?
		?>"logo": {<?
			?>"@type": "ImageObject"<?
			?>,"url": "<?=$result['organization']['logo']['src'];?>"<?
			/*?>,"width": <?=$result['organization']['logo']['width'];?><?
			?>,"height": <?=$result['organization']['logo']['height'];?><?*/
		?>}<?
	?>}<?

if ($result['dateline']):
	?>,"dateline": "<?=$result['dateline'];?>"<?
endif;

if ($result['description']):
	?>,"description": "<?=$result['description'];?>"<?
endif;

if ($result['articleBody']):
	?>,"articleBody": "<?=$result['articleBody'];?>"<?
endif;

if ($result['wordCount']):
	?>,"wordCount": "<?=$result['wordCount'];?>"<?
endif;

?>}</script>