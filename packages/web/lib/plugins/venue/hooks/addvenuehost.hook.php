<?php
/**
 * Auto-assign hosts to venue host groups by subnet.
 *
 * PHP version 5
 *
 * @category AddVenueHost
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
/**
 * Auto-assign hosts to venue host groups by subnet.
 *
 * @category AddVenueHost
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
class AddVenueHost extends Hook
{
    /**
     * The hook name.
     *
     * @var string
     */
    public $name = 'AddVenueHost';
    /**
     * The hook description.
     *
     * @var string
     */
    public $description = 'Auto-assign hosts to venue host groups by subnet';
    /**
     * Is the hook active.
     *
     * @var bool
     */
    public $active = true;
    /**
     * The node this hook enacts with.
     *
     * @var string
     */
    public $node = 'venue';
    /**
     * Initializes object.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        self::$HookManager
            ->register(
                'REQUEST_CLIENT_INFO',
                array($this, 'addHostToVenue')
            )
            ->register(
                'BOOT_ITEM_NEW_SETTINGS',
                array($this, 'addHostToVenue')
            );
    }
    /**
     * Assign host to venue group if IP matches a venue subnet.
     *
     * @param mixed $arguments The hook arguments.
     *
     * @return void
     */
    public function addHostToVenue($arguments)
    {
        if (!in_array($this->node, (array)self::$pluginsinstalled)) {
            return;
        }
        $Host = $arguments['Host'];
        if (!$Host || !$Host->isValid()) {
            return;
        }
        $ip = $this->_resolveHostIP($Host);
        if (empty($ip)) {
            return;
        }
        $Venue = self::getClass('VenueManager')->getVenueByIP($ip);
        if (!$Venue) {
            return;
        }
        $groupID = $Venue->get('hostgroupID');
        if (!in_array($groupID, (array)$Host->get('groups'))) {
            $Host->addGroup($groupID)->save();
        }
    }
    /**
     * Resolve the IP address for a host.
     *
     * @param object $Host The host object.
     *
     * @return string
     */
    private function _resolveHostIP($Host)
    {
        $ip = $Host->get('ip');
        if (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        $hostname = $Host->get('name');
        $resolved = gethostbyname($hostname);
        if ($resolved !== $hostname && filter_var($resolved, FILTER_VALIDATE_IP)) {
            return $resolved;
        }
        return '';
    }
}
