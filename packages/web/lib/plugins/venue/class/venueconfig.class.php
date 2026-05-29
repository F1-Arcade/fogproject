<?php
/**
 * Venue configuration key-value class.
 *
 * PHP version 5
 *
 * @category VenueConfig
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
/**
 * Venue configuration key-value class.
 *
 * @category VenueConfig
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
class VenueConfig extends FOGController
{
    /**
     * The table name.
     *
     * @var string
     */
    protected $databaseTable = 'venueConfig';
    /**
     * The table fields and common names.
     *
     * @var array
     */
    protected $databaseFields = array(
        'id' => 'vcID',
        'venueID' => 'vcVenueID',
        'key' => 'vcKey',
        'value' => 'vcValue',
    );
    /**
     * The required fields.
     *
     * @var array
     */
    protected $databaseFieldsRequired = array(
        'venueID',
        'key',
    );
}
