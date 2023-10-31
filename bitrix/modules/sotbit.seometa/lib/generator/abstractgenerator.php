<?

namespace Sotbit\Seometa\Generator;

use Sotbit\Seometa\Property\PropertySetEntity;
use Sotbit\Seometa\Url\AbstractUrl;

abstract class AbstractGenerator {
    protected $propertyTemplate;
    protected $mask;
    public function generate(AbstractUrl $mask, PropertySetEntity $propertySetEntity) {
        $this->mask = $mask;

        switch ($propertySetEntity->getEntityType()) {
            case 'PRICE':
                return $this->generatePriceParams($propertySetEntity);
            case 'FILTER':
                return $this->generateFilterParams($propertySetEntity);
            default:
                return $this->generateParams($propertySetEntity);
        }
    }

    abstract protected function generatePriceParams(PropertySetEntity $propertySetEntity);

    abstract protected function generateFilterParams(PropertySetEntity $propertySetEntity);

    abstract protected function generateParams(PropertySetEntity $propertySetEntity);
}
?>