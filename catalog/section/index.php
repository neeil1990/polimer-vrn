<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Каталог");
?>

<div class="row cl">                  
  <div class="ct__leftbar">

     <div class="cat filter open">
        <a href="#" class="name" onclick="return false"><div class="cube"><span></span><span></span></div>Фильтр</a>
        <ul class="cat-ul">
           <li class="cat-li li-open">
              <a href="#" class="title" onclick="return false">Цена <span>(Руб.)</span></a>
              <div class="inner">
                 <div class="range-slider">
                   <div class="extra-controls">
                      <span class="from">от</span>
                      <input type="text" class="js-input-from" value="0" />
                      <span class="to">до</span>
                      <input type="text" class="js-input-to" value="0" />
                   </div>
                   <input type="text" class="js-range-slider" value="" />
                </div>
             </div>
           </li>

           <li class="cat-li li-open">
              <a href="#" class="title" onclick="return false">Производители</a>
              <div class="inner">
                 <div class="checkboxes cl">
                    <div class="item">
                       <input type="checkbox" id="Bosch" checked >
                       <label for="Bosch">Bosch</label>
                    </div>
                    <div class="item">
                       <input type="checkbox" id="Dewalt" disabled>
                       <label for="Dewalt">Dewalt</label>
                    </div>
                    <div class="item">
                       <input type="checkbox" id="Hitachi">
                       <label for="Hitachi">Hitachi</label>
                    </div>
                    <div class="item">
                       <input type="checkbox" id="Makita">
                       <label for="Makita">Makita</label>
                    </div>
                    <div class="item">
                       <input type="checkbox" id="Metabo">
                       <label for="Metabo">Metabo</label>
                    </div>
                    <div class="item">
                       <input type="checkbox" id="Ryobi">
                       <label for="Ryobi">Ryobi</label>
                    </div>
                    <div class="item">
                       <input type="checkbox" id="Sturm">
                       <label for="Sturm">Sturm</label>
                    </div>
                    <div class="item">
                       <input type="checkbox" id="Interskol">
                       <label for="Interskol">Интерскол</label>
                    </div>
                    <a href="#" class="checkboxes_all">Все производители</a>
                 </div>
              </div>
           </li>

           <li class="cat-li">
              <a href="/catalog/" class="title">Внутренний бак</a>
           </li>
           
           <li class="cat-li">
              <a href="/catalog/" class="title">Ширина</a>
           </li>

           <li class="cat-li">
              <a href="/catalog/" class="title">Высота</a>
           </li>

           <li class="cat-li">
              <a href="/catalog/" class="title">Объем</a>
           </li>

           <li class="cat-li li-open">
              <a href="#" class="title" onclick="return false">Мощность <span>(кВт)</span></a>
              <div class="inner">
                 <select name="select1" id="select1">
                    <option value="" selected disabled>- Выберите из списка -</option>
                    <option>1</option>
                    <option>2</option>
                    <option>3</option>
                    <option>4</option>
                    <option>5</option>
                    <option>6</option>
                    <option>7</option>
                    <option>8</option>
                    <option>9</option>
                    <option>10</option>
                 </select>
              </div>
           </li>

           <li class="cat-li">
              <a href="/catalog/" class="title">Время нагревания на 45 °С</a>
           </li>

           <li class="cat-li">
              <a href="/catalog/" class="title">Гарантия на внутренний бак</a>
           </li>

           <li class="cat-li">
              <a href="/catalog/" class="title">Гарантия</a>
           </li>
        </ul>
     </div>

     <div class="cat insan open">
        <a href="#" class="name" onclick="return false"><div class="cube"><span></span><span></span></div>Инженерная сантехника</a>
        <ul class="cat-ul">
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Котельное оборудование</a>
           </li>
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Радиаторы отопления, арматура</a>
           </li>
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Трубы и фитинги</a>
           </li>
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Насосное оборудование</a>
           </li>
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Краны шаровые, вентили</a>
           </li>
           <li class="cat-li li-open">
              <a href="#" class="title" onclick="return false">
                 <div class="cube"><span></span><span></span></div>Баки расширительные</a>
                 <div class="inner">
                    <a href="#" class="list-item">Водонагреватели электрические</a>
                    <a href="#" class="list-item">Модели классической круглой формы</a>
                    <a href="#" class="list-item">Модели плоской формы</a>
                    <a href="#" class="list-item">Компактные малоемкостные модели</a>
                    <a href="#" class="list-item">Проточные</a>
                    <a href="#" class="list-item">Запасные части для водонагревателей</a>
                 </div>
           </li>
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Задвижки и затворы</a>
           </li>
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Баки пластиковые для воды</a>
           </li>
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Измерительные приборы</a>
           </li>
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Бойлеры</a>
           </li>
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Клапаны, регуляторы давления</a>
           </li>
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Коллекторные системы</a>
           </li>
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Оборудование Danfoss, Esbe</a>
           </li>
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Сантехника</a>
           </li>
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Воздухоотводчики</a>
           </li>
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Люки смотровых колодцев</a>
           </li>
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Подводка гибкая (вода, газ)</a>
           </li>
           <li class="cat-li"><a ></a>
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Счётчики воды и газа</a>
           </li>
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Сварочные аппараты</a> 
           </li>
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Теплоизоляция</a>
           </li>
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>Сопутствующие материалы</a>
           </li>
           <li class="cat-li">
              <a href="/catalog/" class="title">
                 <div class="cube"><span></span><span></span></div>РАСПРОДАЖА</a>
           </li>
        </ul>
     </div>

     <div class="cat stroima">
        <a href="#" class="name" onclick="return false"><div class="cube"><span></span><span></span></div>Строительные материалы</a>
     </div>

     <div class="cat pricel">
        <a href="#" class="name" onclick="return false"><div class="cube"><span></span><span></span></div>Прайс-листы</a>
     </div>
  </div>


  <div class="ct__content">
     <h1>Модели круглой классической формы</h1>

     <div class="products_roll">
        <div class="pr_header cl">
           <div class="sort">
              <label for="select_prh">Сортировать по:</label>
              <select name="select_prh" id="select_prh">
                 <option selected>Популярности</option>
                 <option>Наличию</option>
                 <option>Цене</option>
              </select>
           </div>
           <div class="view">
              <a href="#" class="list">
                 <div class="icon cl">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                 </div>
                 Список</a>
              <a href="#" class="tab active">
              <div class="icon cl">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                 </div>
                 Таблица</a>
           </div>
           <div class="quan">
              <label for="quan">Товаров на стр. :</label>
              <select name="quan" id="quan">
                 <option selected>20</option>
                 <option>40</option>
                 <option>80</option>
              </select>
           </div>
           <a href="#" class="filter" onclick="return false">
              <span>Фильтр</span>
              <span>Закрыть</span>
           </a>
        </div>

        <div class="pr_box cl">
           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>
           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>
           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>
           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>

           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>
           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>
           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>
           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>

           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>
           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>
           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>
           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>

           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>
           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>
           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>
           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>

           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>
           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>
           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>
           <div class="item">
              <div class="hover">
                 <div class="inner">
                    <div class="compare">
                       <label>
                          <input type="checkbox">
                          <span>Сравнить</span>
                       </label>
                    </div>
                    <div class="close"></div>
                    <a href="/catalog/section/detail/" class="pic">
                       <span>
                          <img src="<?=SITE_TEMPLATE_PATH?>/img/card/item1/current.png" alt="">
                       </span>
                    </a>
                    <a href="/catalog/section/detail/" class="title">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
                    <div class="cost"><span>6 700</span> Руб.</div>
                    <div class="quantity">
                       <a class="minus na" href="#"></a>
                       <input type="text" value="1"/>
                       <a class="plus" href="#"></a>
                    </div>
                    <div class="cost_total"><span>17 700</span> Руб.</div>
                    <a href="#" class="add2cart" onclick="return false">
                       <span class="txt1">В корзину</span>
                       <span class="txt2">Добавить в корзину</span>
                    </a>
                    <div class="instock">Товар в наличии</div>
                 </div>
              </div>
           </div>
        </div>

        <div class="pr_footer cl">
           <div class="ns__paginator cl">
              <div class="name">Страницы:</div>
              <a href="#" class="arrow left">
                 <span></span>
                 <span></span>
              </a>
              <div class="pages cl">
                 <a href="" class="page active">1</a>
                 <a href="" class="page">2</a>
                 <a href="" class="page">3</a>
                 <a href="" class="page">4</a>
                 <div class="dots">...</div>
                 <a href="" class="page">10</a>
              </div>
              <a href="#" class="arrow right aractive">
                 <span></span>
                 <span></span>
              </a>
           </div>
           <div class="quan_b">
              <label for="quan_b">Товаров на стр. :</label>
              <select name="quan" id="quan_b">
                 <option selected>20</option>
                 <option>40</option>
                 <option>80</option>
              </select>
           </div>
        </div>
     </div><!--end::products_roll-->

      <h2>Вы смотрели</h2>
 <div class="slider_product" id="mp__product__action">
    <div>
       <div class="product">
          <a href="/catalog/detail/">
             <img src="<?=SITE_TEMPLATE_PATH?>/img/product/pr6.jpg" alt="" width="132" height="110" class="img">
          </a>
          <a href="/catalog/detail/" class="name">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
          <div class="price"><span>6 700</span> Руб</div>
          <a href="#" class="cart">В корзину</a>
       </div>
    </div>
    <div>
       <div class="product ">
          <a href="/catalog/detail/">
             <img src="<?=SITE_TEMPLATE_PATH?>/img/product/pr1.jpg" alt="" width="132" height="110" class="img">
          </a>
          <a href="/catalog/detail/" class="name">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
          <div class="price"><span>6 700</span> Руб</div>
          <a href="#" class="cart">В корзину</a>
       </div>
    </div>
    <div>
       <div class="product">
          <a href="/catalog/detail/">
             <img src="<?=SITE_TEMPLATE_PATH?>/img/product/pr3.jpg" alt="" width="132" height="110" class="img">
          </a>
          <a href="/catalog/detail/" class="name">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
          <div class="price"><span>6 700</span> Руб</div>
          <a href="#" class="cart">В корзину</a>
       </div>
    </div>
    <div>
       <div class="product">
          <a href="/catalog/detail/">
             <img src="<?=SITE_TEMPLATE_PATH?>/img/product/pr3.jpg" alt="" width="132" height="110" class="img">
          </a>
          <a href="/catalog/detail/" class="name">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
          <div class="price"><span>6 700</span> Руб</div>
          <a href="#" class="cart">В корзину</a>
       </div>
    </div>
    <div>
       <div class="product">
          <a href="/catalog/detail/">
             <img src="<?=SITE_TEMPLATE_PATH?>/img/product/pr4.jpg" alt="" width="132" height="110" class="img">
          </a>
          <a href="/catalog/detail/" class="name">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
          <div class="price"><span>6 700</span> Руб</div>
          <a href="#" class="cart">В корзину</a>
       </div>
    </div>
    <div>
       <div class="product">
          <a href="/catalog/detail/">
             <img src="<?=SITE_TEMPLATE_PATH?>/img/product/pr5.jpg" alt="" width="132" height="110" class="img">
          </a>
          <a href="/catalog/detail/" class="name">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
          <div class="price"><span>6 700</span> Руб</div>
          <a href="#" class="cart">В корзину</a>
       </div>
    </div>
    <div>
       <div class="product">
          <a href="/catalog/detail/">
             <img src="<?=SITE_TEMPLATE_PATH?>/img/product/pr1.jpg" alt="" width="132" height="110" class="img">
          </a>
          <a href="/catalog/detail/" class="name">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
          <div class="price"><span>6 700</span> Руб</div>
          <a href="#" class="cart">В корзину</a>
       </div>
    </div>
    <div>
       <div class="product">
          <a href="/catalog/detail/">
             <img src="<?=SITE_TEMPLATE_PATH?>/img/product/pr2.jpg" alt="" width="132" height="110" class="img">
          </a>
          <a href="/catalog/detail/" class="name">Радиатор биметаллический RADENA BIMETALL CS 500, 10 секций</a>
          <div class="price"><span>6 700</span> Руб</div>
          <a href="#" class="cart">В корзину</a>
       </div>
    </div>
 </div><!-- end::slider_product -->

     <div class="related_articles cl">
        <div class="col-txt">
           <h1>Водонагреватели электрические</h1>
           <p>Компания ООО «Полимер» была основана в 2007 году как дочернее предприятие ООО «Металлинвест плюс» (одного из крупнейших поставщиков стального металлопроката и труб в Воронежской области с почти 20-летней историей). Изначально целью основания фирмы была продажа уже имеющимся клиентам большего ассортимента товаров, а именно полипропиленовых труб и фитингов.</p>
           <p>В настоящее время ООО «Полимер» является одной из крупнейших компаний оптово-розничной торговли материалами и оборудованием для отопления и водоснабжения в Воронежской области. Наш ассортимент постоянно расширяется и уже можно выделить несколько основных товарных групп:</p>
           <ul>
              <li>Инженерная сантехника (газовые котлы, радиаторы отопления, трубы и фитинги, запорная арматура, насосы и др.)</li>
              <li>Строительно-отделочные материалы (гипсокартон, сухие смеси, поликарбонат, лакокраска, инструменты, электрика, крепеж и др.)</li>
           </ul>
        </div>
        <div class="col-articles">
           <h1>Статьи</h1>
           <a href="#">Подробно о перфораторах</a>
           <a href="#">Что выбрать – перфоратор или дрель?</a>
           <a href="#">Устройство, тип патрона, функции перфоратора</a>
           <a href="#">Дополнительная оснастка для перфораторов</a>
           <a href="#">Как выбрать перфоратор – коротко о главном в водонагревателях</a>
           <a href="#" class="allarticles">Все статьи</a>
        </div>
     </div>
  </div>

  <div class="ct__mask">
  </div>
</div>



<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>