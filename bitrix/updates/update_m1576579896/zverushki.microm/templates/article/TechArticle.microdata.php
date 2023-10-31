<? /**
 *
 * Шаблон TechArticle (Технические статьи) в формате даных Microdata
 *
 * Входной массив
 * @var array
 */


$Server = Bitrix\Main\Application::getInstance()->getContext()
			->getServer();

$domen = 'http'.($_SERVER['HTTPS'] ? 's' : '').'://'.$Server->getServerName();

?><div itemscope itemtype="http://schema.org/TechArticle"><?
	?><meta itemscope itemprop="mainEntityOfPage"  itemType="https://schema.org/WebPage" itemid="<?=$domen.$Server->getRequestUri();?>"/><?

	?><div itemprop="headline"><?=$result['name'];?></div><?

	?><div itemprop="author" itemscope itemtype="https://schema.org/Person"><?
		?><span itemprop="name"><?=$result['author']['name'];?></span><?
	?></div><?

if ($result['picture']):
	?><div itemprop="image" itemscope itemtype="https://schema.org/ImageObject"><?
		?><meta itemprop="url" content="<?=$domen.$Server->getServerName().$result['picture']['src'];?>"><?
		?><meta itemprop="width" content="<?=$result['picture']['width'];?>px"><?
		?><meta itemprop="height" content="<?=$result['picture']['height'];?>px"><?
	?></div><?
endif;

	?><div itemprop="publisher" itemscope itemtype="https://schema.org/Organization"><?
		?><div itemprop="logo" itemscope itemtype="https://schema.org/ImageObject"><?
			?><meta itemprop="url" content="<?=$result['organization']['logo']['src'];?>"><?
			/*?><meta itemprop="width" content="<?=$result['organization']['logo']['width'];?>"><?
			?><meta itemprop="height" content="<?=$result['organization']['logo']['height'];?>"><?*/
		?></div><?

		?><meta itemprop="name" content="<?=$result['organization']['name'];?>"><?
	?></div><?

	?><meta itemprop="datePublished" content="<?=$result['dateCreate']->format('c');?>"/><?
	?><meta itemprop="dateModified" content="<?=$result['dateModify']->format('c');?>"/><?


if ($result['description']):
	?><span itemprop="description"><?=$result['description'];?></span><?
endif;

if ($result['articleBody']):
	?><div itemprop="articleBody"><?=$result['articleBody'];?></div><?
endif;

if ($result['dependencies']):
	?><div itemprop="dependencies"><?=$result['dependencies'];?></div><?
endif;

if ($result['proficiencyLevel']):
	?><div itemprop="proficiencyLevel"><?=$result['proficiencyLevel'];?></div><?
endif;

?></div>