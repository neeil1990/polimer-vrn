<? /**
 *
 * Шаблон Адреса и организации формата Microdata
 *
 * Входной массив
 * @var array
 */

?><div data-module=zverushki_microm itemscope itemtype="http://schema.org/Store" style="display:none"><?
	?><meta itemprop="name" content="<?=$result['name'];?>"><?

	?><div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress"><?
		?><meta itemprop="streetAddress" content="<?=$result['address']['streetAddress'];?>"><?
		?><meta itemprop="postalCode" content="<?=$result['address']['postalCode'];?>"><?
		?><meta itemprop="addressLocality" content="<?=$result['address']['addressLocality'];?>"><?
if ($result['address']['addressRegion']):
		?><meta itemprop="addressRegion" content="<?=$result['address']['addressRegion'];?>"><?
endif;
		?><meta itemprop="addressCountry" content="<?=$result['address']['addressCountry'];?>"><?
	?></div><?

if (array_key_exists('geo', $result)):
	?><div itemprop="geo" itemscope itemtype="http://schema.org/GeoCoordinates"><?
		if ($result['geo']['latitude']):
			?><meta itemprop="latitude" content="<?=$result['geo']['latitude'];?>"><?
		endif;

		if ($result['geo']['longitude']):
			?><meta itemprop="longitude" content="<?=$result['geo']['longitude'];?>"><?
		endif;
	?></div><?
endif;

if ($result['image']):
	?><meta itemprop="image" content="<?=$result['image'];?>"><?
else:
	?><meta itemprop="image" content="<?=$result['logo'];?>"><?
endif;

if ($result['telephone']):
	?><meta itemprop="telephone" content="<?=$result['telephone'];?>"><?
endif;

if ($result['email']):
	?><meta itemprop="email" content="<?=$result['email'];?>"><?
endif;

	?><meta itemprop="priceRange" content="<?=$result['priceRange'];?>"><?

?></div>