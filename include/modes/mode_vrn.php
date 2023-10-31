<div class="header__timework">
    <div class="line cl">
        <div class="days">ПН-ПТ</div>
        <div class="hours"><?= tplvar('week', true);?></div>
    </div>
    <div class="line cl">
        <div class="days">CБ</div>
        <div class="hours"><?= tplvar('saturday', true);?></div>
    </div>
    <div class="line cl">
        <div class="days">ВС</div>
        <div class="hours"><span class="weekend"><?= tplvar('sun', true);?></span></div>
    </div>
</div>
