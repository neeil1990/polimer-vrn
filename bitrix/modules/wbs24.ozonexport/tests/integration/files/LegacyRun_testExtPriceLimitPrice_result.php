<? $disableReferers = false;
if (!isset($_GET["referer1"]) || strlen($_GET["referer1"])<=0) $_GET["referer1"] = "yandext";
$strReferer1 = htmlspecialchars($_GET["referer1"]);
if (!isset($_GET["referer2"]) || strlen($_GET["referer2"]) <= 0) $_GET["referer2"] = "";
$strReferer2 = htmlspecialchars($_GET["referer2"]);
header("Content-Type: text/xml; charset=UTF-8");
echo "<"."?xml version=\"1.0\" encoding=\"UTF-8\"?".">"?>
<!DOCTYPE yml_catalog SYSTEM "shops.dtd">
<yml_catalog date="2021-11-09 23:35">
<shop>
<name>Сантехстрой Групп</name>
<company>Сантехстрой Групп</company>
<url>http://santehstroy</url>
<platform>1C-Bitrix</platform>
<offers>
<offer id="65077">
<price>1200</price>
<oldprice>1333</oldprice>
<premium_price>960</premium_price>
<outlets><outlet instock="20"></outlet></outlets>
</offer>
</offers>
</shop>
</yml_catalog>
