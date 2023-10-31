<? /**
 *
 * Ўаблон навигацыонной цепочки формата Microdata
 *
 * ¬ходной массив
 * @var array [itemListElement]
 */

?><ol data-module=zverushki_microm itemscope itemtype="http://schema.org/BreadcrumbList" style="display:none"><?
foreach ($result['itemListElement'] as $item):

	?><li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"><?
		?><a itemprop="item" href="<?=$item['@id'];?>"><?
			?><meta itemprop="name" content="<?=$item['name'];?>"><?
		?></a><?
		?><meta itemprop="position" content="<?=$item['position'];?>"><?
	?></li><?

endforeach;
?></ol>