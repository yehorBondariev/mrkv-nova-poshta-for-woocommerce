<?php

namespace plugins\NovaPoshta\classes;

use plugins\NovaPoshta\classes\base\Base;

/**
 * Class Scheduler
 * @property int $locationsLastUpdateDate
 * @property int interval
 * @package plugins\NovaPoshta\classes
 */
class DatabaseScheduler extends Base
{

    /**
     * Entry point for admin_init hook
     */
    public function ensureSchedule()
    {
        if ($this->requiresUpdate()) {
            DatabaseSync::instance()->synchroniseLocations();
        }
    }

    /**
     * @return bool
     */
    protected function requiresUpdate()
    {
      if( get_option('update_bases')){
        return ($this->locationsLastUpdateDate + 604800 ) < time(); //base more than 7 days old
      }
    }

    /**
     * @return int
     */
    protected function getInterval()
    {
        return DAY_IN_SECONDS;
    }

    /**
     * @return int
     */
    protected function getLocationsLastUpdateDate()
    {
        return NPttn()->options->locationsLastUpdateDate;
    }

    /**
     * @param int $value
     */
    protected function setLocationsLastUpdateDate($value)
    {
        NPttn()->options->setLocationsLastUpdateDate($value);
        $this->locationsLastUpdateDate = $value;
    }

}
