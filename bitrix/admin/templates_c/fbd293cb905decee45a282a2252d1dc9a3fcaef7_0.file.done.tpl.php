<?php
/* Smarty version 4.3.1, created on 2023-05-06 15:59:32
  from '/var/www/polimer-vrn.ru/data/www/polimer-vrn.ru/bitrix/modules/thebrainstech.copyiblock/templates/done.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.3.1',
  'unifunc' => 'content_64564f34610a22_90411812',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'fbd293cb905decee45a282a2252d1dc9a3fcaef7' => 
    array (
      0 => '/var/www/polimer-vrn.ru/data/www/polimer-vrn.ru/bitrix/modules/thebrainstech.copyiblock/templates/done.tpl',
      1 => 1683377695,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_64564f34610a22_90411812 (Smarty_Internal_Template $_smarty_tpl) {
?><div style="text-align:center">
    <p style="font-size: 16px"><?php echo GetMessage('THEBRAINSE_COPYIBLOCK_MODULE_LIB_COPY_END');?>
</p>

    <a style="font-size: 20px" href="/bitrix/admin/iblock_edit.php?ID=<?php echo $_smarty_tpl->tpl_vars['id']->value;?>
&type=<?php echo $_smarty_tpl->tpl_vars['type']->value;?>
&lang=ru&admin=Y">
        <?php echo GetMessage('THEBRAINSE_COPYIBLOCK_MODULE_LIB_GO_TO_IB');?>

    </a>
</div>
<?php }
}
