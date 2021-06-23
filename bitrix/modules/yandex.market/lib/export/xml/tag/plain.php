<?php

namespace Yandex\Market\Export\Xml\Tag;

use Yandex\Market;

class Plain extends Base
{
    public function validate($value, array $context, $siblingsValues = null, Market\Result\XmlNode $nodeResult = null, $settings = null)
    {
        $result = true;

        if ($value === null || $value === '')
        {
            $result = false;

            if ($nodeResult)
            {
                if ($this->isRequired)
                {
                    $nodeResult->registerError(
                        Market\Config::getLang('XML_NODE_VALIDATE_EMPTY'),
                        Market\Error\XmlNode::XML_NODE_VALIDATE_EMPTY
                    );
                }
                else
                {
                    $nodeResult->invalidate();
                }
            }
        }

        return $result;
    }

    public function exportNode($value, array $context, \SimpleXMLElement $parent, Market\Result\XmlNode $nodeResult = null, $settings = null)
    {
        $tagName = Market\Result\XmlNode::PLAIN_TAG_NAME;
        $valueExport = $value;

        if ($nodeResult !== null)
        {
            $valueExport = $nodeResult->addReplace($value);

            $nodeResult->registerPlain();
        }

        return $parent->addChild($tagName, $valueExport);
    }
}