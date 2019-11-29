<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
if($arResult['ITEMS'])
{
	foreach($arResult['ITEMS'] as $Item)
	{
		?>
		<div class="sotbit-seometa-tags-wrapper">
			<?
			if($Item['TITLE'] && $Item['URL'])
			{
				?>
				<div class="sotbit-seometa-tag">
					<a class="sotbit-seometa-tag-link" href="<?=$Item['URL'] ?>" title="<?=$Item['TITLE'] ?>"><?=$Item['TITLE'] ?></a>
				</div>
				<?
			}
			?>
		</div>
		<?
	}
}

?>