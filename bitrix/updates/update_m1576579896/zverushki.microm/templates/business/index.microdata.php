<? /**
 *
 * Шаблон Адреса и организации формата Microdata
 *
 * Входной массив
 * @var array
 */

?><div itemscope itemtype="http://schema.org/Store"><?
	?><span itemprop="name"><?=$result['name'];?></span><?

	?><div itemprop="address" itemscope itemtype="http://schema.org/PostalAddress"><?
		?><span itemprop="streetAddress"><?=$result['address']['streetAddress'];?></span><?
		?><span itemprop="postalCode"><?=$result['address']['postalCode'];?></span><?
		?><span itemprop="addressLocality"><?=$result['address']['addressLocality'];?></span>,<?
if ($result['address']['addressRegion']):
		?><span itemprop="addressRegion"><?=$result['address']['addressRegion'];?></span>,<?
endif;
		?><span itemprop="addressCountry"><?=$result['address']['addressCountry'];?></span>,<?
	?></div><?

if (array_key_exists('geo', $result)):
	?><div itemprop="geo" itemscope itemtype="http://schema.org/GeoCoordinates"><?
		if ($result['geo']['latitude']):
			?><span itemprop="latitude"><?=$result['geo']['latitude'];?></span><?
		endif;

		if ($result['geo']['longitude']):
			?><span itemprop="longitude"><?=$result['geo']['longitude'];?></span><?
		endif;
	?></div><?
endif;

if ($result['image']):
	?><span itemprop="image"><?=$result['image'];?></span>,<?
else:
	?><span itemprop="image"><?=$result['logo'];?></span>,<?
endif;

if ($result['telephone']):
	?><span itemprop="telephone"><?=$result['telephone'];?></span>,<?
endif;

if ($result['email']):
	?><span itemprop="email"><?=$result['email'];?></span><?
endif;

	?><span itemprop="priceRange"><?=$result['priceRange'];?></span><?

?></div>