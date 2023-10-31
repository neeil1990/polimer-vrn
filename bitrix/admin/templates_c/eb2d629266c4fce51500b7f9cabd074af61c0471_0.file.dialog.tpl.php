<?php
/* Smarty version 4.3.1, created on 2023-05-06 15:55:21
  from '/var/www/polimer-vrn.ru/data/www/polimer-vrn.ru/bitrix/modules/thebrainstech.copyiblock/templates/dialog.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.3.1',
  'unifunc' => 'content_64564e394d7163_64744265',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'eb2d629266c4fce51500b7f9cabd074af61c0471' => 
    array (
      0 => '/var/www/polimer-vrn.ru/data/www/polimer-vrn.ru/bitrix/modules/thebrainstech.copyiblock/templates/dialog.tpl',
      1 => 1683377695,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:content.tpl' => 1,
  ),
),false)) {
function content_64564e394d7163_64744265 (Smarty_Internal_Template $_smarty_tpl) {
ob_start();
$_smarty_tpl->_subTemplateRender('file:content.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
$_smarty_tpl->assign('content', ob_get_clean());
?>
javascript:(
    new BX.CDialog({
    content_url: "<?php echo $_smarty_tpl->tpl_vars['params']->value;?>
",
    width: 500,
    head: "",
    height: 260,
    resizable: false,
    draggable: true,
    content: "<?php echo strtr((string)$_smarty_tpl->tpl_vars['content']->value, array("\\" => "\\\\", "'" => "\\'", "\"" => "\\\"", "\r" => "\\r", 
                       "\n" => "\\n", "</" => "<\/", "<!--" => "<\!--", "<s" => "<\s", "<S" => "<\S",
                       "`" => "\\`", "\${" => "\\\$\{"));?>
",
    buttons: [BX.CDialog.btnSave, BX.CDialog.btnCancel]})
).Show();
<?php }
}
