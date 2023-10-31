<?php
namespace Sotbit\Seometa\Generator;

use Sotbit\Seometa\Property\PropertySetEntity;

class ComboxGenerator extends AbstractGenerator {
    protected function generatePriceParams(PropertySetEntity $propertySetEntity) {
        throw new \Exception("the method [" . __METHOD__ . "] didn't implement");
    }

    protected function generateParams(PropertySetEntity $propertySetEntity) {
        throw new \Exception("the method [".__METHOD__."] didn't implement");
    }

    protected function generateFilterParams(PropertySetEntity $propertySetEntity) {
        throw new \Exception("the method [".__METHOD__."] didn't implement");
    }
}
?>