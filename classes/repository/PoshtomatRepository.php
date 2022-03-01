<?php

namespace plugins\NovaPoshta\classes\repository;

// use plugins\NovaPoshta\classes\Warehouse;
use plugins\NovaPoshta\classes\Poshtomat;

/**
 * Class PoshtomatRepository
 * @package plugins\NovaPoshta\classes\repository
 */
class PoshtomatRepository extends AbstractAreaRepository
{

    /**
     * @return string
     */
    public function table()
    {
        return NPttnPM()->db->prefix . 'nova_poshta_poshtomat';
    }

    /**
     * @return string
     */
    protected function getAreaClass()
    {
        return Poshtomat::getClass();
    }

    /**
     * @param string $name
     * @return string
     * @override
     */
    protected function getNameSearchCriteria($name)
    {
        NPttnPM()->db->escape_by_ref($name);
        return sprintf("(`description` LIKE CONCAT('%%', '%s', '%%') OR `description_ru` LIKE CONCAT('%%', '%s', '%%'))", $name, $name);
    }

}
