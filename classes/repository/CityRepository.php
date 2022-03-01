<?php

namespace plugins\NovaPoshta\classes\repository;

use plugins\NovaPoshta\classes\City;

/**
 * Class CityRepository
 * @package plugins\NovaPoshta\classes\repository
 */
class CityRepository extends AbstractAreaRepository
{

    /**
     * @return string
     */
    public function table()
    {
      return NPttn()->db->prefix . 'nova_poshta_city';
        return null;
    }

    /**
     * @return string
     */
    protected function getAreaClass()
    {
        return City::getClass();
    }

}
