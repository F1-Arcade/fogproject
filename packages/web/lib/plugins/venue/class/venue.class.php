<?php
/**
 * The venue class.
 *
 * PHP version 5
 *
 * @category Venue
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
/**
 * The venue class — represents a site/subnet with its storage and host groups.
 *
 * @category Venue
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
class Venue extends FOGController
{
    /**
     * The venue table.
     *
     * @var string
     */
    protected $databaseTable = 'venue';
    /**
     * The venue table fields and common names.
     *
     * @var array
     */
    protected $databaseFields = array(
        'id' => 'vID',
        'name' => 'vName',
        'description' => 'vDescription',
        'subnet' => 'vSubnet',
        'storagegroupID' => 'vStorageGroupID',
        'hostgroupID' => 'vHostGroupID',
        'apiRegion' => 'vApiRegion',
        'apiVenueRef' => 'vApiVenueRef',
        'apiVenueData' => 'vApiVenueData',
        'createdBy' => 'vCreatedBy',
        'createdTime' => 'vCreatedTime',
    );
    /**
     * The required fields.
     *
     * @var array
     */
    protected $databaseFieldsRequired = array(
        'name',
        'subnet',
        'storagegroupID',
        'hostgroupID',
    );
    /**
     * Additional fields.
     *
     * @var array
     */
    protected $additionalFields = array(
        'storagegroup',
        'hostgroup',
    );
    /**
     * Database -> Class field relationships.
     *
     * @var array
     */
    protected $databaseFieldClassRelationships = array(
        'StorageGroup' => array(
            'id',
            'storagegroupID',
            'storagegroup'
        ),
        'Group' => array(
            'id',
            'hostgroupID',
            'hostgroup'
        ),
    );
    /**
     * Get the storage group for this venue.
     *
     * @return object
     */
    public function getStorageGroup()
    {
        return self::getClass('StorageGroup', $this->get('storagegroupID'));
    }
    /**
     * Get the host group for this venue.
     *
     * @return object
     */
    public function getHostGroup()
    {
        return self::getClass('Group', $this->get('hostgroupID'));
    }
    /**
     * Check if an IP address falls within this venue's subnet.
     *
     * @param string $ip The IP address to check.
     *
     * @return bool
     */
    public function ipInSubnet($ip)
    {
        $cidr = $this->get('subnet');
        if (empty($cidr) || empty($ip)) {
            return false;
        }
        if (strpos($cidr, '/') === false) {
            return ($ip === $cidr);
        }
        list($net, $mask) = explode('/', $cidr);
        $ipNet = ip2long($net);
        $ipMask = ~((1 << (32 - (int)$mask)) - 1);
        $ipAddr = ip2long($ip);
        return (($ipAddr & $ipMask) == ($ipNet & $ipMask));
    }
    /**
     * Destroy this venue and cascade to related config.
     *
     * @param string $key The key to match for destroy.
     *
     * @return bool
     */
    public function destroy($key = 'id')
    {
        self::getClass('VenueConfigManager')
            ->destroy(array('venueID' => $this->get('id')));
        return parent::destroy($key);
    }
}
