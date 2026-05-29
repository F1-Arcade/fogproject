<?php
/**
 * Auto-assign storage nodes to venue storage groups by IP.
 *
 * PHP version 5
 *
 * @category AddVenueNode
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
/**
 * Auto-assign storage nodes to venue storage groups by IP.
 *
 * @category AddVenueNode
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
class AddVenueNode extends Hook
{
    /**
     * The hook name.
     *
     * @var string
     */
    public $name = 'AddVenueNode';
    /**
     * The hook description.
     *
     * @var string
     */
    public $description = 'Auto-assign storage nodes to venue storage groups';
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
                'STORAGE_NODE_SETTING',
                array($this, 'assignNodeToVenue')
            );
    }
    /**
     * Assign a storage node to the venue's storage group by IP.
     *
     * @param mixed $arguments The hook arguments.
     *
     * @return void
     */
    public function assignNodeToVenue($arguments)
    {
        if (!in_array($this->node, (array)self::$pluginsinstalled)) {
            return;
        }
        $StorageNode = $arguments['StorageNode'];
        if (!$StorageNode || !$StorageNode->isValid()) {
            return;
        }
        $ip = $StorageNode->get('ip');
        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return;
        }
        $Venue = self::getClass('VenueManager')->getVenueByIP($ip);
        if (!$Venue) {
            return;
        }
        $StorageNode->set('storagegroupID', $Venue->get('storagegroupID'));
    }
}
