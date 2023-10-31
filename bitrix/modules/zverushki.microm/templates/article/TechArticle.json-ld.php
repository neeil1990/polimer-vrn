<? /**
 *
 * Шаблон TechArticle (Технические статьи)
 *
 * Входной массив
 * @var array [itemListElement]
 */


$Server = Bitrix\Main\Application::getInstance()->getContext()
			->getServer();

$domen = 'http'.($_SERVER['HTTPS'] ? 's' : '').'://'.$Server->getServerName();

?><script type="application/ld+json" data-skip-moving="true">{<?
	?>"@context": "http://schema.org",<?
	?>"@type": "TechArticle",<?
	?>"mainEntityOfPage": {<?
		?>"@type": "WebPage",<?
		?>"@id": "<?=$domen.$Server->getRequestUri();?>"<?
	?>},<?
	?>"headline": "<?=$result['name'];?>",<?

if ($result['picture']):
	?>"image": {<?
		?>"@type": "ImageObject",<?
		?>"url": "<?=$domen.$result['picture']['src'];?>",<?
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

if ($result['description']):
	?>,"description": "<?=$result['description'];?>"<?
endif;

if ($result['articleBody']):
	?>,"articleBody": "<?=$result['articleBody'];?>"<?
endif;

if ($result['dependencies']):
	?>,"dependencies": "<?=$result['dependencies'];?>"<?
endif;

if ($result['proficiencyLevel']):
	?>,"proficiencyLevel": "<?=$result['proficiencyLevel'];?>"<?
endif;

?>}</script>