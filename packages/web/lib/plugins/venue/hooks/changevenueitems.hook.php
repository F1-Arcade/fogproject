<?php
/**
 * Route tasks to venue storage group by subnet.
 *
 * PHP version 5
 *
 * @category ChangeVenueItems
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
/**
 * Route tasks to venue storage group by subnet.
 *
 * @category ChangeVenueItems
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
class ChangeVenueItems extends Hook
{
    /**
     * The hook name.
     *
     * @var string
     */
    public $name = 'ChangeVenueItems';
    /**
     * The hook description.
     *
     * @var string
     */
    public $description = 'Route tasks to venue storage group by subnet';
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
                'SNAPIN_NODE',
                array($this, 'storageNode')
            )
            ->register(
                'SNAPIN_GROUP',
                array($this, 'storageGroup')
            )
            ->register(
                'BOOT_ITEM_NEW_SETTINGS',
                array($this, 'bootItemSettings')
            )
            ->register(
                'BOOT_TASK_NEW_SETTINGS',
                array($this, 'bootTaskSettings')
            )
            ->register(
                'HOST_NEW_SETTINGS',
                array($this, 'hostSettings')
            )
            ->register(
                'CHECK_NODE_MASTERS',
                array($this, 'storageGroup')
            )
            ->register(
                'CHECK_NODE_MASTER',
                array($this, 'storageNode')
            );
    }
    /**
     * Get venue for a host by its IP.
     *
     * @param object $Host The host object.
     *
     * @return Venue|null
     */
    private function _getVenueForHost($Host)
    {
        $ip = $Host->get('ip');
        if (empty($ip)) {
            $ip = gethostbyname($Host->get('name'));
            if ($ip === $Host->get('name')) {
                return null;
            }
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }
        return self::getClass('VenueManager')->getVenueByIP($ip);
    }
    /**
     * Override storage node/group for snapins.
     *
     * @param mixed $arguments The arguments.
     *
     * @return void
     */
    public function storageNode($arguments)
    {
        if (!in_array($this->node, (array)self::$pluginsinstalled)) {
            return;
        }
        if (!isset($arguments['Host'])) {
            return;
        }
        $Venue = $this->_getVenueForHost($arguments['Host']);
        if (!$Venue) {
            return;
        }
        $StorageGroup = $Venue->getStorageGroup();
        if (!$StorageGroup->isValid()) {
            return;
        }
        $StorageNode = $StorageGroup->getMasterStorageNode();
        if ($StorageNode->isValid()) {
            $arguments['StorageNode'] = $StorageNode;
        }
    }
    /**
     * Override storage group.
     *
     * @param mixed $arguments The arguments.
     *
     * @return void
     */
    public function storageGroup($arguments)
    {
        if (!in_array($this->node, (array)self::$pluginsinstalled)) {
            return;
        }
        if (!isset($arguments['Host'])) {
            return;
        }
        $Venue = $this->_getVenueForHost($arguments['Host']);
        if (!$Venue) {
            return;
        }
        $StorageGroup = $Venue->getStorageGroup();
        if ($StorageGroup->isValid()) {
            $arguments['StorageGroup'] = $StorageGroup;
        }
    }
    /**
     * Override boot item settings.
     *
     * @param mixed $arguments The arguments.
     *
     * @return void
     */
    public function bootItemSettings($arguments)
    {
        if (!in_array($this->node, (array)self::$pluginsinstalled)) {
            return;
        }
        if (!isset($arguments['Host'])) {
            return;
        }
        $Venue = $this->_getVenueForHost($arguments['Host']);
        if (!$Venue) {
            return;
        }
        $StorageGroup = $Venue->getStorageGroup();
        if (!$StorageGroup->isValid()) {
            return;
        }
        $StorageNode = $StorageGroup->getMasterStorageNode();
        if ($StorageNode->isValid()) {
            $arguments['StorageNode'] = $StorageNode;
            $arguments['StorageGroup'] = $StorageGroup;
            $arguments['storageip'] = $StorageNode->get('ip');
        }
    }
    /**
     * Override boot task settings.
     *
     * @param mixed $arguments The arguments.
     *
     * @return void
     */
    public function bootTaskSettings($arguments)
    {
        $this->bootItemSettings($arguments);
    }
    /**
     * Override host settings.
     *
     * @param mixed $arguments The arguments.
     *
     * @return void
     */
    public function hostSettings($arguments)
    {
        if (!in_array($this->node, (array)self::$pluginsinstalled)) {
            return;
        }
        if (!isset($arguments['Host'])) {
            return;
        }
        $Venue = $this->_getVenueForHost($arguments['Host']);
        if (!$Venue) {
            return;
        }
        $StorageGroup = $Venue->getStorageGroup();
        if ($StorageGroup->isValid()) {
            $arguments['StorageGroup'] = $StorageGroup;
        }
    }
}
