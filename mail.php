<?
$mail="sv6@list.ru"; // ваша почта
$subject ="Test" ; // тема письма
$text= "Line 1\nLine 2\nLine 3"; // текст письма
if( mail($mail, $subject, $text) )
{ echo 'Успешно отправлено!'; }
else{ echo 'Отправка не удалась!'; }
?>