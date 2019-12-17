<? /**
 *
 * Шаблон навигацыонной цепочки
 *
 * Входной массив
 * @var array [itemListElement]
 */

?><script type="application/ld+json">{<?
	?>"@context":"http://schema.org",<?
	?>"@type":"Store",<?
	?>"@id":"<?=$result['@id'];?>",<?
	?>"name":"<?=$result['name'];?>",<?

	?>"address":{<?
		?>"@type":"PostalAddress",<?
		?>"streetAddress":"<?=$result['address']['streetAddress'];?>",<?
		?>"addressLocality":"<?=$result['address']['addressLocality'];?>"<?
	if ($result['address']['addressRegion']):
		?>,"addressRegion":"<?=$result['address']['addressRegion'];?>"<?
	endif;
		?>,"postalCode":"<?=$result['address']['postalCode'];?>"<?
		?>,"addressCountry":"<?=$result['address']['addressCountry'];?>"<?
	?>}<?

	if (array_key_exists('geo', $result)):
		?>,"geo":{<?
			?>"@type":"GeoCoordinates"<?

			if ($result['geo']['latitude']):
				?>,"latitude":"<?=$result['geo']['latitude'];?>"<?
			endif;

			if ($result['geo']['longitude']):
				?>,"longitude":"<?=$result['geo']['longitude'];?>"<?
			endif;
		?>}<?
	endif;

	if ($result['url']):
		?>,"url":"<?=$result['url'];?>"<?
	endif;

	if ($result['image']):
		?>,"image":"<?=$result['image'];?>"<?
	else:
		?>,"image":"<?=$result['url'];?>"<?
	endif;

	if ($result['email']):
		?>,"email":"<?=$result['email'];?>"<?
	endif;

	if ($result['logo']):
		?>,"logo":"<?=$result['logo'];?>"<?
	endif;

	if ($result['telephone']):
		?>,"telephone":"<?=$result['telephone'];?>"<?
	endif;

	?>,"priceRange":"<?=$result['priceRange'];?>"<?

?>}</script>