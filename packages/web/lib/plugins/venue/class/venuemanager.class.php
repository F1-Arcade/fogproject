<?php
/**
 * Venue manager class.
 *
 * PHP version 5
 *
 * @category VenueManager
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
/**
 * Venue manager class.
 *
 * @category VenueManager
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
class VenueManager extends FOGManagerController
{
    /**
     * The base table name.
     *
     * @var string
     */
    public $tablename = 'venue';
    /**
     * Install the venue table.
     *
     * @return bool
     */
    public function install()
    {
        $this->uninstall();
        $sql = Schema::createTable(
            $this->tablename,
            true,
            array(
                'vID',
                'vName',
                'vDescription',
                'vSubnet',
                'vStorageGroupID',
                'vHostGroupID',
                'vApiRegion',
                'vApiVenueRef',
                'vApiVenueData',
                'vCreatedBy',
                'vCreatedTime',
            ),
            array(
                'INTEGER',
                'VARCHAR(100)',
                'VARCHAR(255)',
                'VARCHAR(50)',
                'INTEGER',
                'INTEGER',
                'VARCHAR(10)',
                'VARCHAR(30)',
                'LONGTEXT',
                'VARCHAR(40)',
                'TIMESTAMP',
            ),
            array(
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
            ),
            array(
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                false,
                'CURRENT_TIMESTAMP',
            ),
            array(
                'vID',
                'vName',
                'vSubnet',
            ),
            'InnoDB',
            'utf8',
            'vID',
            'vID'
        );
        if (!self::$DB->query($sql)) {
            return false;
        }
        if (!self::getClass('VenueConfigManager')->install()) {
            return false;
        }
        return self::getClass('VenueConfigTemplateManager')->install();
    }
    /**
     * Uninstall the venue tables.
     *
     * @return bool
     */
    public function uninstall()
    {
        self::getClass('VenueConfigTemplateManager')->uninstall();
        self::getClass('VenueConfigManager')->uninstall();
        return parent::uninstall();
    }
    /**
     * Find the venue matching an IP address.
     *
     * @param string $ip The IP to match against venue subnets.
     *
     * @return Venue|null
     */
    public function getVenueByIP($ip)
    {
        Route::listem('venue');
        $items = json_decode(Route::getData());
        if (!isset($items->venues)) {
            return null;
        }
        foreach ($items->venues as &$venueData) {
            $Venue = self::getClass('Venue', $venueData->id);
            if ($Venue->isValid() && $Venue->ipInSubnet($ip)) {
                return $Venue;
            }
            unset($venueData);
        }
        return null;
    }
}
