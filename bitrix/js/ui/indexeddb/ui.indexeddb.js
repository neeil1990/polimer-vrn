/**
 * Bitrix UI
 * IndexedDB manager (integration with Dexie.js)
 *
 * @package bitrix
 * @subpackage im
 * @copyright 2001-2019 Bitrix
 *
 * @see	https://dexie.org/docs/Tutorial/Getting-started
 */

class IndexedDBIntegrationWithDexie
{
	constructor(database)
	{
		return new Dexie(database);
	}
}

if (!window.BX)
{
	window.BX = {};
}
if (typeof window.BX.IndexedDB === 'undefined')
{
	BX.IndexedDB = IndexedDBIntegrationWithDexie;
}