<?
namespace Sotbit\Seometa\Property;

class PropertyValue {
    private $data = [];

    public function __construct(array &$dataValue) {
        $this->data = $dataValue;
    }

    public function __get($name) {
        return (isset($this->data[$name])) ? $this->data[$name] : null;
    }

    public function getData()
    {
        return $this->data;
    }

    public function __set($name, $value) {
        return $this->data[$name] = $value;
    }

    public function getValueByType($propertyType) {
        switch($propertyType) {
            case 'Ux':
                return $this->data['URL_ID'];
            case 'L':
            case 'S':
            case 'N':
            default:
                return $this->data['VALUE'];
        }
/* //Backup
        case 'L':
            return $this->data['html_value'];
*/
    }
}