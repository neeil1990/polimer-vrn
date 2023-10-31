<? $disableReferers = true;
header("Content-Type: text/xml; charset=UTF-8");
echo "<"."?xml version=\"1.0\" encoding=\"UTF-8\"?".">"?>
<!DOCTYPE yml_catalog SYSTEM "shops.dtd">
<yml_catalog date="2021-11-09 23:35">
<shop>
<name>Современная Одежда+</name>
<company>Современная Одежда+</company>
<url>http://demo</url>
<shipment-options><option days="3" order-before="12" store-id="0"/></shipment-options>
<currencies>
<currency id="RUB" rate="1" />
<currency id="USD" rate="72.7089" />
<currency id="EUR" rate="78.32" />
<currency id="UAH" rate="2.511" />
<currency id="BYN" rate="32.2" />
</currencies>
<categories>
<category id="27">Тест marketplaces</category>
</categories>
<offers>
<offer id="test1" available="true">
<url>http://demo/site2/catalog/test_marketplaces/test-1/</url>
<price>15000</price>
<categoryId>27</categoryId>
<name>Тест 1</name>
<description></description>
<outlets><outlet id="0" instock="15"></outlet></outlets>
</offer>
<offer id="test-tp1" available="true">
<url>http://demo/site2/catalog/test_marketplaces/test_s_tp/?oid=347</url>
<price>1000</price>
<categoryId>27</categoryId>
<name>Тест с ТП 1</name>
<description></description>
<outlets><outlet id="0" instock="20"></outlet></outlets>
</offer>
<offer id="test-tp2" available="true">
<url>http://demo/site2/catalog/test_marketplaces/test_s_tp/?oid=348</url>
<price>500</price>
<categoryId>27</categoryId>
<name>Тест с ТП 2</name>
<description></description>
<outlets><outlet id="0" instock="17"></outlet></outlets>
</offer>
</offers>
</shop>
</yml_catalog>
