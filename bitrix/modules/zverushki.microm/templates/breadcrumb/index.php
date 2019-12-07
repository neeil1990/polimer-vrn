<? /**
 *
 * Шаблон навигацыонной цепочки
 *
 * Входной массив
 * @var array [itemListElement]
 */

$ar = array();
foreach ($result['itemListElement'] as $item)
	$ar[] = '{"@type":"ListItem","position":'.$item['position'].',"item":{"@id":"'.$item['@id'].'", "name":"'.$item['name'].'"}}';

?><script type="application/ld+json" data-skip-moving="true">{<?
	?>"@context":"http://schema.org",<?
	?>"@type":"BreadcrumbList",<?
	?>"itemListElement":[<?=implode(',', $ar);?>]<?
?>}</script>