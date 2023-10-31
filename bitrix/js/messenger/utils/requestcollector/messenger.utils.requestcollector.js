/**
 * Bitrix Messenger
 * Rest Request Collector
 *
 * @package bitrix
 * @subpackage im
 * @copyright 2001-2019 Bitrix
 */

class RequestCollector
{
	constructor()
	{
		this.list = {};
	}

	register(name, xhr)
	{
		this.list[name] = xhr;
		return true;
	}

	unregister(name, abort = false)
	{
		if (this.list[name])
		{
			if (abort)
			{
				this.list[name].abort();
			}
			delete this.list[name];
		}
	}

	get(name)
	{
		return this.list[name]? this.list[name]: null;
	}

	abort(name)
	{
		if (this.list[name])
		{
			this.list[name].abort();
		}
		return true;
	}

	cleaner()
	{
		for (let name in this.list)
		{
			if (this.list.hasOwnProperty(name))
			{
				this.unregister(name, true);
			}
		}
	}
}

if (!window.BX)
{
	window.BX = {};
}
if (typeof window.BX.Messenger == 'undefined')
{
	window.BX.Messenger = {};
}
if (typeof window.BX.Messenger.RequestCollector == 'undefined')
{
	BX.Messenger.RequestCollector = RequestCollector;
}