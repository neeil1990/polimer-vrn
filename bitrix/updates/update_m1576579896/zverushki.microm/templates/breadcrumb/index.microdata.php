<? /**
 *
 * Ўаблон навигацыонной цепочки формата Microdata
 *
 * ¬ходной массив
 * @var array [itemListElement]
 */

?><ol itemscope itemtype="http://schema.org/BreadcrumbList"><?
foreach ($result['itemListElement'] as $item):

	?><li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"><?
		?><a itemscope itemtype="http://schema.org/Thing" itemprop="item" href="<?=$item['@id'];?>"><?
			?><span itemprop="name"><?=$item['name'];?></span><?
		?></a><?
		?><meta itemprop="position" content="<?=$item['position'];?>" /><?
	?></li><?

endforeach;
?></ol>