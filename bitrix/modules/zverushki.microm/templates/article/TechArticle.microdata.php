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

?><div data-module=zverushki_microm itemscope itemtype="http://schema.org/TechArticle" style="display:none"><?
	?><meta itemscope itemprop="mainEntityOfPage" itemType="https://schema.org/WebPage" itemid="<?=$domen.$Server->getRequestUri();?>"><?

	?><meta itemprop="headline" content="<?=$result['name'];?>"><?

	?><div itemprop="author" itemscope itemtype="https://schema.org/Person"><?
		?><meta itemprop="name" content="<?=$result['author']['name'];?>"><?
	?></div><?

if ($result['picture']):
	?><div itemprop="image" itemscope itemtype="https://schema.org/ImageObject"><?
		?><link itemprop="contentUrl" href="<?=$domen.$result['picture']['src'];?>"><?
		?><link itemprop="url" href="<?=$domen.$result['picture']['src'];?>"><?
		?><meta itemprop="width" content="<?=$result['picture']['width'];?>px"><?
		?><meta itemprop="height" content="<?=$result['picture']['height'];?>px"><?
	?></div><?
endif;

	?><div itemprop="publisher" itemscope itemtype="https://schema.org/Organization"><?
		?><div itemprop="logo" itemscope itemtype="https://schema.org/ImageObject"><?
			?><link itemprop="contentUrl" href="<?=$result['organization']['logo']['src'];?>"><?
			?><link itemprop="url" href="<?=$result['organization']['logo']['src'];?>"><?
			?><meta itemprop="width" content="<?=$result['organization']['logo']['width'];?>px"><?
			?><meta itemprop="height" content="<?=$result['organization']['logo']['height'];?>px"><?
		?></div><?

	if ($result['organization']['telephone']):
		?><meta itemprop="telephone" content="<?=$result['organization']['telephone'];?>"><?
	endif;

		?><div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress"><?
			?><meta itemprop="streetAddress" content="<?=$result['organization']['address']['streetAddress'];?>"><?
			?><meta itemprop="postalCode" content="<?=$result['organization']['address']['postalCode'];?>"><?
			?><meta itemprop="addressLocality" content="<?=$result['organization']['address']['addressLocality'];?>"><?
	if ($result['organization']['address']['addressRegion']):
			?><meta itemprop="addressRegion" content="<?=$result['organization']['address']['addressRegion'];?>"><?
	endif;
			?><meta itemprop="addressCountry" content="<?=$result['organization']['address']['addressCountry'];?>"><?
		?></div><?

		?><meta itemprop="name" content="<?=$result['organization']['name'];?>"><?
	?></div><?

	?><meta itemprop="datePublished" content="<?=$result['dateCreate']->format('c');?>"><?
	?><meta itemprop="dateModified" content="<?=$result['dateModify']->format('c');?>"><?


if ($result['description']):
	?><meta itemprop="description" content="<?=$result['description'];?>"><?
endif;

if ($result['articleBody']):
	?><meta itemprop="articleBody" content="<?=$result['articleBody'];?>"><?
endif;

if ($result['dependencies']):
	?><meta itemprop="dependencies" content="<?=$result['dependencies'];?>"><?
endif;

if ($result['proficiencyLevel']):
	?><meta itemprop="proficiencyLevel" content="<?=$result['proficiencyLevel'];?>"><?
endif;

?></div>