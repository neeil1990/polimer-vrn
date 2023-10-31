<?php

namespace Yandex\Market\Trading\Service\Reference\Document;

interface HasRenderFile
{
	public function canRenderFile(array $items, array $settings = []);

	public function renderFile(array $items, array $settings = []);
}