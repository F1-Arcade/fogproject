<?php
/**
 * Venue config manager class.
 *
 * PHP version 5
 *
 * @category VenueConfigManager
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
/**
 * Venue config manager class.
 *
 * @category VenueConfigManager
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
class VenueConfigManager extends FOGManagerController
{
    /**
     * The base table name.
     *
     * @var string
     */
    public $tablename = 'venueConfig';
    /**
     * Install the venueConfig table.
     *
     * @return bool
     */
    public function install()
    {
        $sql = Schema::createTable(
            $this->tablename,
            true,
            array(
                'vcID',
                'vcVenueID',
                'vcKey',
                'vcValue',
            ),
            array(
                'INTEGER',
                'INTEGER',
                'VARCHAR(50)',
                'VARCHAR(255)',
            ),
            array(
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
            ),
            array(
                'vcID',
                array('vcVenueID', 'vcKey'),
            ),
            'InnoDB',
            'utf8',
            'vcID',
            'vcID'
        );
        return self::$DB->query($sql);
    }
    /**
     * Uninstall the table.
     *
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall();
    }
}
