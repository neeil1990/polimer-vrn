<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if( !empty( $_GET["id"] ) )
    $id = (int)$_GET["id"];

if( !empty( $_GET["quantity"] ) )
    $quantity = (int)$_GET["quantity"];
else
    $quantity = 1;

if( !$id )
    die( 'Ошибка добавления товара в корзину' );

CModule::IncludeModule( 'catalog' );
CModule::IncludeModule( 'sale' );


if( Add2BasketByProductID( $id, $quantity ) )
    print 'Товар успешно добавлен в корзину';
else
    print 'Товара нет в наличии, принимаем заказ на товар по телефону';

?>
