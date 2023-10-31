<?php

namespace Darneo\Ozon\Fields\Views;

use CUser;
use Darneo\Ozon\Fields\FieldInfo;
use DOMAttr;
use DOMDocument;
use DOMNode;

abstract class Base implements ViewInterface
{
    protected FieldInfo $field;
    protected string $html = '';
    protected string $htmlTitle = '';
    protected array $attributes = [];

    protected DOMDocument $dom;
    protected $value;
    protected array $parameters;
    protected bool $isReport = false;
    protected int $userId;
    private array $defaultAttributes = [];

    public function __construct(array $parameters = [])
    {
        $this->dom = new DOMDocument();
        $this->parameters = $parameters;
        $this->userId = (int)(new CUser())->GetID();
    }

    public function setField(FieldInfo $field): void
    {
        $this->field = $field;
    }

    public function setValue($value): void
    {
        $this->value = $value ?: '';
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function getHtml(): string
    {
        $this->buildHtml();

        return $this->html;
    }

    private function buildHtml(): void
    {
        $domNode = $this->getNode();

        $this->dom->appendChild($domNode);
        $this->defaultAttributes = $this->getDefaultAttributes();
        $this->addAttributesToNode();

        $this->html = html_entity_decode($this->dom->saveHTML());

        $this->dom->removeChild($domNode);
    }

    abstract protected function getNode();

    abstract protected function getDefaultAttributes();

    private function addAttributesToNode(): void
    {
        $attributes = $this->mergeAttributes($this->defaultAttributes, $this->attributes);
        foreach ($attributes as $attributeName => $attributeValue) {
            $this->dom->documentElement->setAttributeNode(new DOMAttr($attributeName, $attributeValue));
        }
    }

    private function mergeAttributes($attributes1, $attributes2): array
    {
        $attributes = array_merge($attributes1, $attributes2);

        foreach ($attributes as $attributeName => $attributeValue) {
            $attribute1Value = $attributes1[$attributeName];
            $attribute2Value = $attributes2[$attributeName];
            if ($attribute1Value !== null && $attribute2Value !== null) {
                $attributes[$attributeName] = $attribute1Value . ' ' . $attribute2Value;
            }
        }

        return $attributes;
    }

    public function getDom(): DOMDocument
    {
        return $this->dom;
    }

    protected function appendHTML(DOMNode $parent, $source): DOMNode
    {
        $tmpDoc = new DOMDocument();
        $tmpDoc->loadHTML('<?xml encoding="UTF-8">' . $source);
        foreach ($tmpDoc->getElementsByTagName('body')->item(0)->childNodes as $node) {
            $node = $parent->ownerDocument->importNode($node, true);
            $parent->appendChild($node);
        }

        return $parent;
    }

    protected function isReport(): bool
    {
        return $this->isReport;
    }

    public function setIsReport(bool $isReport): void
    {
        $this->isReport = $isReport;
    }
}
