<? /**
 *
 * Ўаблон карточки товара в формате Microdata
 *
 * ¬ходной массив
 * @var array
 */
?><div itemscope itemtype="http://schema.org/Product"><?
	?><span itemprop="name"><?=$result['name'];?></span><?

if ($result['description'] !== false):
		?><span itemprop="description"><?=$result['description'];?></span><?
endif;

if ($result['picture'] !== false):
	?><img src="<?=$result['picture'];?>" itemprop="image"><?
endif;

if ($result['brand']):
	?><span itemprop="brand"><?=$result['brand']['name'];?></span><?
endif;

if ($result['rating'] !== false):
	?><span itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating"><?
		?><span itemprop="ratingValue"><?=$result['rating']['value'];?></span><?
		?><span itemprop="reviewCount"><?=$result['rating']['count'];?></span><?
	?></span><?
endif;

if ($result['offers'] !== false):
	?><span itemprop="mpn"><?=$result['offers']['serialNumber'];?></span><?

	$__offers = $result['offers']['offers'];
	unset(
		$result['offers']['offers'],
		$result['offers']['serialNumber']
	);

	?><div itemprop="offers" itemscope itemtype="http://schema.org/Offer"><?

foreach ($result['offers'] as $code => $val):
		?><meta itemprop="<?=$code;?>" content="<?=$val;?>"><?
endforeach;

	?></div><?
endif;

?></div>