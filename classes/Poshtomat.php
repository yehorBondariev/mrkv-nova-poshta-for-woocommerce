<?php

namespace plugins\NovaPoshta\classes;

use plugins\NovaPoshta\classes\repository\AbstractAreaRepository;
use plugins\NovaPoshta\classes\repository\AreaRepositoryFactory;

/**
 * Class poshtomat
 * @package plugins\NovaPoshta\classes
 */
class Poshtomat extends Area
{

    /**
     * @return string
     */
    protected static function _key()
    {
        return 'nova_poshta_poshtomat';
    }

    /**
     * @return AbstractAreaRepository
     */
    protected function getRepository()
    {
        return AreaRepositoryFactory::instance()->poshtomatRepo();
    }
}
