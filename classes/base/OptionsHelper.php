<?php
namespace plugins\NovaPoshta\classes\base;

use plugins\NovaPoshta\classes\Area;

/**
 * Class OptionsHelper
 * @package plugins\NovaPoshta\classes\base
 */
class OptionsHelper
{
    /**
     * @param Area[] $locations
     * @param bool $enableEmpty
     * @return array
     */
    public static function getList($locations, $enableEmpty = true)
    {
        $result = array();
        if ($enableEmpty) {
            $result[''] = __('Choose region', NOVA_POSHTA_TTN_DOMAIN);
        }
        foreach ($locations as $location) {
            $result[$location->ref] = $location->description;
        }
        return $result;
    }
}
