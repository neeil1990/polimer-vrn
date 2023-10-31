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
    ?><span itemprop="brand" itemtype="http://schema.org/Brand" itemscope><?
        ?><meta itemprop="name" content="<?=$result['brand']['name'];?>" /><?
    ?></span><?
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

if ($result['reviews'] !== false):
	foreach ($result['reviews'] as $code => $review):
		?><div itemprop="review" itemscope itemtype="http://schema.org/Review"><?
	    	?><span itemprop="name"><?=$review['name']?></span><?
	    	?><span itemprop="author"><?=$review['author']?></span><?
	    	?><meta itemprop="datePublished" content="<?=$review['datePublished']?>"><?
	    	?><span class="message-post-date"><?=$review['message-post-date']?></span><?
		    if($review['reviewRating'] !== false && $review['reviewRating']['ratingValue'] !== false):
			    ?><div itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating"><?
				    if($review['reviewRating']['worstRating'] !== false):
				      	?><meta itemprop="worstRating" content = "<?=$review['reviewRating']['worstRating']?>"><?
				    endif;
				    ?><span itemprop="ratingValue"><?=$review['reviewRating']['Value']?></span>/<?
				    if($review['reviewRating']['worstRating'] !== false):
				      	?><span itemprop="bestRating"><?=$review['reviewRating']['bestRating']?></span><?
				    endif;
			    ?></div><?
			endif;

			?><span itemprop="itemReviewed" itemscope itemtype="http://schema.org/Thing">
				<link itemprop="additionalType" href="http://schema.org/Product"/>
				<meta itemprop="identifier" content="<?=$result['offers']['serialNumber'];?>" />
				<meta itemprop="name" content="<?=$result['name'];?>" />
			</span><?
			?><span itemprop="reviewBody"><?=$review['reviewBody']?></span><?
	  ?></div><?
	endforeach;
endif;


?></div><?
