<?
if(file_exists($_SERVER['DOCUMENT_ROOT'] ."/seometa_link_count.txt"))
    echo file_get_contents($_SERVER['DOCUMENT_ROOT'] ."/seometa_link_count.txt");
else
    echo 'stop';
exit;
?>
