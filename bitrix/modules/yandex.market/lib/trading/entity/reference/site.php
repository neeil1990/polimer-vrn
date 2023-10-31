<?php

namespace Yandex\Market\Trading\Entity\Reference;

use Yandex\Market;

abstract class Site
{
	protected $environment;

	public function __construct(Environment $environment)
	{
		$this->environment = $environment;
	}

	/**
	 * @return string[]
	 */
    public function getVariants()
    {
    	throw new Market\Exceptions\NotImplementedMethod(static::class, 'getVariants');
    }

	/**
	 * @param string $siteId
	 *
	 * @return string
	 */
	public function getTitle($siteId)
    {
	    throw new Market\Exceptions\NotImplementedMethod(static::class, 'getTitle');
    }

	/**
	 * @param string $siteId
	 *
	 * @return string
	 */
	public function getLanguage($siteId)
    {
	    throw new Market\Exceptions\NotImplementedMethod(static::class, 'getLanguage');
    }
}