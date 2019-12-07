<? /**
 *
 * Ўаблон карточки товара в формате Microdata
 *
 * ¬ходной массив
 * @var array
 */
?><div data-module=zverushki_microm itemscope itemtype="http://schema.org/Product" style="display:none"><?
	?><meta itemprop="name" content="<?=$result['name'];?>"><?

if ($result['description'] !== false):
		?><meta itemprop="description" content="<?=$result['description'];?>"><?
endif;

if ($result['picture'] !== false):
	?><meta itemprop="image" content="<?=$result['picture'];?>"><?
endif;

if ($result['sku']):
	?><meta itemprop="sku" content="<?=$result['sku']['name'];?>"><?
endif;

if ($result['brand']):
	?><meta itemprop="brand" content="<?=$result['brand']['name'];?>"><?
endif;

if ($result['rating'] !== false):
	?><span itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating"><?
		?><meta itemprop="ratingValue" content="<?=$result['rating']['value'];?>"><?
		?><meta itemprop="reviewCount" content="<?=$result['rating']['count'];?>"><?
	?></span><?
endif;

if ($result['offers'] !== false):
	?><meta itemprop="mpn" content="<?=$result['offers']['serialNumber'];?>"><?

	$__offers = $result['offers']['offers'];
	unset(
		$result['offers']['offers'],
		$result['offers']['serialNumber']
	);

	?><div itemprop="offers" itemscope itemtype="http://schema.org/AggregateOffer"><?

foreach ($result['offers'] as $code => $val):
		if ($code == 'url'):
            ?><a href="<?=$val;?>" itemprop="<?=$code;?>"></a><?

        else:
            ?><meta itemprop="<?=$code;?>" content="<?=$val;?>"><?
        endif;
endforeach;

	?></div><?
endif;

?></div>