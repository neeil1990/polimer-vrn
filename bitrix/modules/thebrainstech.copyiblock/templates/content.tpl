<h3 style="text-align: center">{$title}</h3>

<form style="text-align: center">
    <select name="IBLOCKCOPIES">
        <option value="IBLOCK">{GetMessage('THEBRAINSE_COPYIBLOCK_ONLY_IBLOCK')}</option>
        <option value="SECTIONS">{GetMessage('THEBRAINSE_COPYIBLOCK_ONLY_SECTION')}</option>
        <option value="ELEMENTS">{GetMessage('THEBRAINSE_COPYIBLOCK_ALL_COPY')}</option>
    </select>

    <input type="hidden" name="IBLOCKCOPY_ACTION" value="COPY">
    <input type="hidden" name="ID" value="{$ID}">

    <h3 style="text-align: center">{GetMessage('THEBRAINSE_COPYIBLOCK_MODULE_LIB_COPY_TYPE_IB')}</h3>

    <select name="TYPE">
        {foreach $types as $type}
        <option value="{$type.ID}">{$type.NAME|escape}</option>
        {/foreach}
    </select>
</form>
