<?php

namespace plugins\NovaPoshta\classes;

use plugins\NovaPoshta\classes\base\Base;
use plugins\NovaPoshta\classes\repository\AreaRepositoryFactory;
use wpdb;

/**
 * Class Base
 * @package plugins\NovaPoshta\classes
 * @property string tableLocations
 * @property string tableLocationsUpdate
 * @property wpdb $db
 * @property mixed last_error
 * @method prepare($query, $args)
 * @method get_row($query)
 * @method get_results($query)
 * @method query($query);
 * @method insert($table, $data, $format = null)
 * @method get_var($query = null, $x = 0, $y = 0)
 */
// class DatabasePM extends Base
class DatabasePM extends Base
{

    /**
     * @var self
     */
    private static $_instance;

    /**
     * @return DatabasePM
     */
    public static function instance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Database upgrade entry point
     */
    public function upgrade()
    {

        $this->dropTables();
        $this->createTables();
    }

    /**
     * Database downgrade entry point
     */
    public function downgrade()
    {
        $this->dropTables();
        delete_site_option('nova_poshta_db_version');
    }

    /**
     * @return wpdb
     */
    protected function getDb()
    {
        return NPttnPM()->db;
    }

    private function createTables()
    {
        $factory = AreaRepositoryFactory::instance();
        if ($this->db->has_cap('collation')) {
            $collate = $this->db->get_charset_collate();
        } else {
            $collate = '';
        }

        /*
        * create Regions table
        */
       $regionTableName = $factory->regionRepo()->table();
       $regionQuery = <<<AREA
           CREATE TABLE {$regionTableName} (
               `ref` VARCHAR(50) NOT NULL,
               `description` VARCHAR(256) NOT NULL,
               `description_ru` VARCHAR(256) NOT NULL,
               `updated_at` INT(10) UNSIGNED NOT NULL,
               PRIMARY KEY (`ref`)
           ) $collate;
AREA;
       $this->db->query($regionQuery);

       $indexQuery = <<<INDEX
ALTER TABLE {$regionTableName} ADD INDEX idx_nova_poshta_region_description (description);
INDEX;
       $this->db->query($indexQuery);

       $indexQuery = <<<INDEX
ALTER TABLE {$regionTableName} ADD INDEX idx_nova_poshta_region_description_ru (description_ru)
INDEX;
       $this->db->query($indexQuery);

        /*
         * Create cities table
         */
        $cityTableName = $factory->cityRepo()->table();
        $cityQuery = <<<CITY
            CREATE TABLE {$cityTableName} (
                `ref` VARCHAR(100) NOT NULL,
                `description` VARCHAR(400) NOT NULL,
                `description_ru` VARCHAR(400) NOT NULL,
                `parent_ref` VARCHAR(100) NOT NULL,
                `updated_at` INT(10) UNSIGNED NOT NULL,
                PRIMARY KEY (`ref`)

            ) {$collate};
CITY;
        $this->db->query($cityQuery);

        /*
         * create poshtomat table
         */
        $poshtomatTableName = $factory->poshtomatRepo()->table();
        $poshtomatQuery = <<<POSHTOMAT
            CREATE TABLE {$poshtomatTableName} (
                `ref` VARCHAR(100) NOT NULL,
                `description` VARCHAR(400) NOT NULL,
                `description_ru` VARCHAR(400) NOT NULL,
                `parent_ref` VARCHAR(100) NOT NULL,
                `updated_at` INT(11) UNSIGNED NOT NULL,
                PRIMARY KEY (`ref`),
                CONSTRAINT `fk_poshtomat_parent_ref_city_ref` FOREIGN KEY (`parent_ref`) REFERENCES `$cityTableName`(`ref`) ON DELETE CASCADE
            ) $collate;
POSHTOMAT;
        $this->db->query($poshtomatQuery);

        $indexQuery = <<<INDEX
ALTER TABLE {$poshtomatTableName} ADD INDEX idx_nova_poshta_poshtomat_parent_ref_description (parent_ref)
INDEX;
        $this->db->query($indexQuery);

        $indexQuery = <<<INDEX
ALTER TABLE {$poshtomatTableName} ADD INDEX idx_nova_poshta_poshtomat_parent_ref_description_ru (parent_ref)
INDEX;
        $this->db->query($indexQuery);

    }

    private function dropTables()
    {
        $factory = AreaRepositoryFactory::instance();
        $factory->cityRepo()->table();
        $this->dropTableByName($factory->poshtomatRepo()->table());
        $this->dropTableByName($factory->cityRepo()->table());
        $this->dropTableByName($factory->regionRepo()->table());
    }

    /**
     * @param string $table
     */
    private function dropTableByName($table)
    {
        $query = "DROP TABLE IF EXISTS {$table}";
        $this->db->query($query);
    }

    /**
     * @access private
     */
    private function __construct()
    {
    }

    /**
     * @access private
     */
    private function __clone()
    {
    }

}
