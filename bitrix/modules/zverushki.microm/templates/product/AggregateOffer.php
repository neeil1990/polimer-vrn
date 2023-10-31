<? /**
 *
 * Ўаблон товара детально
 *
 * ¬ходной массив
 * @var array
 */

?><script type="application/ld+json" data-skip-moving="true">{<?
    ?>"@context": "http://schema.org/"<?
    ?>,"@type": "Product"<?
    ?>,"name": "<?=$result['name'];?>"<?

    if ($result['picture'] !== false)
        echo ',"image": "'.$result['picture'].'"';

    if ($result['description'] !== false)
        echo ',"description": "'.$result['description'].'"';

    foreach (array('weight', 'height', 'width') as $c)
        if ($result[$c] !== false)
            echo ',"'.$c.'":{'.
                '"@type": "QuantitativeValue"'.
                ($result[$c]['min'] != $result[$c]['max']
                    ? ',"minValue":'.$result[$c]['min'].',"maxValue":'.$result[$c]['max']
                    : ',"value":'.$result[$c]['min']).
                ',"unitCode": "'.$result[$c]['unitCode'].'"'.
            '}';

    if ($result['rating'] !== false)
        echo ',"aggregateRating":{'.
            '"@type": "AggregateRating"'.
            ',"ratingValue": "'.$result['rating']['value'].'"'.
            ',"reviewCount": "'.$result['rating']['count'].'"'.
        '}';

    if ($result['offers'] !== false) {
        $offers = array();

        $typeOffers = array();
        $__offers = $result['offers']['offers'];
        unset($result['offers']['offers']);

        foreach ($__offers as $offer) {
            $ar = array();
            foreach ($offer as $code => $val)
                $ar[] = '"'.$code.'": "'.$val.'"';

            $typeOffers[] = '{'.implode(', ', $ar).'}';
        }

        foreach ($result['offers'] as $code => $val)
            $offers[] = '"'.$code.'": "'.$val.'"';

        echo ',"offers": {'.
            '"@type": "http://schema.org/AggregateOffer",'.
            '"itemCondition": "http://schema.org/NewCondition",'.
            implode(',', $offers).
            ',"offers": ['.implode(',', $typeOffers).']'.
        '}';
    }

    if ($result['brand'] !== false)
        echo ',"brand": {'.
            '"@type": "http://schema.org/Brand",'.
            '"name": "'.$result['brand']['name'].'"'.
        '}';

    if ($result['manufacturer'] !== false)
        echo ',"manufacturer": {'.
            '"@type": "http://schema.org/Organization",'.
            '"name": "'.$result['manufacturer']['name'].'"'.
        '}';

    if ($result['model'] !== false)
        echo ',"model": "'.$result['model']['name'].'"';

?>}</script><?