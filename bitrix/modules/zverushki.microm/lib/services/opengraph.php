<?php

namespace Zverushki\Microm\Services;

use Bitrix\Main\Context;
use Bitrix\Main\Page\Asset;
use CFile;
use Zverushki\Microm\Contracts\SchemaService;
use Zverushki\Microm\Entities\PageData;
use Zverushki\Microm\MicromTable;
use Zverushki\Microm\Options;

/**
 *
 */
class OpenGraph implements SchemaService
{
    /**
     * @var Asset
     */
    private $asset;

    /**
     * @var PageData
     */
    private $page;

    /**
     * @var \Bitrix\Main\Server
     */
    private $server;

    /**
     * @var array
     */
    private $site;

    /**
     * @var array
     */
    private $config;

    /**
     * @param PageData $page
     */
    public function __construct(PageData $page)
    {
        $this->asset = Asset::getInstance();
        $this->server = Context::getCurrent()->getServer();
        $this->site = Options::entity()->getSite();
        $this->page = $page;

        $config = MicromTable::getList([
            'filter' => ['SITE_ID' => $this->site['id'], 'CODE' => 'open_graph'],
            'cache'  => ['ttl' => 86900, 'cache_joins' => true],
        ])
            ->fetch();

        $this->config = $config ? $config['VALUE'] : [];
    }

    /**
     * @inheritDoc
     */
    public function handle()
    {
        foreach ($this->data() as $item) {
            if (!$item['content']) {
                continue;
            }

            $this->asset->addString("<meta property=\"{$item['property']}\" content=\"{$item['content']}\" />");
        }
    }

    /**
     * @param array $data
     * @return array
     */
    private function defaultData(array $data): array
    {
        $items = [
            [
                'property' => 'og:type',
                'content'  => 'website',
            ],
            [
                'property' => 'og:site_name',
                'content'  => $this->site['name'],
            ],
            [
                'property' => 'og:url',
                'content'  => $this->site['url'].$this->server->get('SCRIPT_URL'),
            ],
        ];

        if ($data['title']) {
            $items[] = [
                'property' => 'og:title',
                'content'  => $data['title'],
            ];
        }

        if ($data['description']) {
            $items[] = [
                'property' => 'og:description',
                'content'  => strip_tags($data['description']),
            ];
        }

        if ($data['picture']) {
            $file = getimagesize($this->server->getDocumentRoot().$data['picture']['path']);

            $items[] = [
                'property' => 'og:image',
                'content'  => $this->site['url'].$data['picture']['path'],
            ];

            $items[] = [
                'property' => 'og:image:type',
                'content'  => $file['mime'],
            ];

            $items[] = [
                'property' => 'og:image:width',
                'content'  => $file['0'],
            ];

            $items[] = [
                'property' => 'og:image:height',
                'content'  => $file['1'],
            ];

            $items[] = [
                'property' => 'vk:image',
                'content'  => $this->site['url'].$data['picture']['path'],
            ];
        }

        return $items;
    }

    /**
     * @param array $data
     * @return array
     */
    private function twitterData(array $data): array
    {
        $items = [
            [
                'property' => 'twitter:card',
                'content'  => 'summary_large_image',
            ],
            [
                'property' => 'twitter:site',
                'content'  => $this->site['name'],
            ],
        ];

        if ($data['title']) {
            $items[] = [
                'property' => 'twitter:title',
                'content'  => $data['title'],
            ];
        }

        if ($data['description']) {
            $items[] = [
                'property' => 'twitter:description',
                'content'  => strip_tags($data['description']),
            ];
        }

        if ($data['picture']) {
            $items[] = [
                'property' => 'twitter:image',
                'content'  => $this->site['url'].$data['picture']['path'],
            ];

            if ($data['title']) {
                $items[] = [
                    'property' => 'twitter:image:alt',
                    'content'  => $data['title'],
                ];
            }
        }

        return $items;
    }

    /**
     * @return array
     */
    private function data(): array
    {
        global $APPLICATION;

        $data = [
            'title'       => $APPLICATION->GetProperty('title') ?: $APPLICATION->GetTitle(),
            'description' => $APPLICATION->GetProperty('description'),
            'picture'     => $this->page->picture() ?? $this->defaultPicture(),
        ];

        $items = $this->defaultData($data);

        if ($this->config['open_graph_twitter'] == 'Y') {
            $items = array_merge($items, $this->twitterData($data));
        }

        return $items;
    }

    /**
     * @return array|null
     */
    private function defaultPicture(): ?array
    {
        if (!$this->config['open_graph_default_picture']) {
            return null;
        }

        $file = CFile::GetPath($this->config['open_graph_default_picture']);

        return file_exists($this->server->getDocumentRoot().$file)
            ? [
                'path' => $file,
            ]
            : null;
    }
}