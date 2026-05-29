<?php
/**
 * Add Venue menu item to main navigation.
 *
 * PHP version 5
 *
 * @category AddVenueMenuItem
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
/**
 * Add Venue menu item to main navigation.
 *
 * @category AddVenueMenuItem
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
class AddVenueMenuItem extends Hook
{
    /**
     * The hook name.
     *
     * @var string
     */
    public $name = 'AddVenueMenuItem';
    /**
     * The hook description.
     *
     * @var string
     */
    public $description = 'Add Venues to the main menu';
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
                'MAIN_MENU_DATA',
                array($this, 'menuData')
            )
            ->register(
                'SEARCH_PAGES',
                array($this, 'addSearch')
            )
            ->register(
                'PAGES_WITH_OBJECTS',
                array($this, 'addPageWithObject')
            )
            ->register(
                'ACTIONBOX',
                array($this, 'clearActionBox')
            );
    }
    /**
     * Add the menu item.
     *
     * @param mixed $arguments The arguments.
     *
     * @return void
     */
    public function menuData($arguments)
    {
        if (!in_array($this->node, (array)self::$pluginsinstalled)) {
            return;
        }
        self::arrayInsertAfter(
            'storage',
            $arguments['main'],
            $this->node,
            array(
                _('Venues'),
                'fa fa-building'
            )
        );
    }
    /**
     * Add venue to search pages.
     *
     * @param mixed $arguments The arguments.
     *
     * @return void
     */
    public function addSearch($arguments)
    {
        if (!in_array($this->node, (array)self::$pluginsinstalled)) {
            return;
        }
        array_push($arguments['searchPages'], $this->node);
    }
    /**
     * Add venue to pages with objects.
     *
     * @param mixed $arguments The arguments.
     *
     * @return void
     */
    public function addPageWithObject($arguments)
    {
        if (!in_array($this->node, (array)self::$pluginsinstalled)) {
            return;
        }
        array_push($arguments['PagesWithObjects'], $this->node);
    }
    /**
     * Clear the default action box for the venue node.
     *
     * @param mixed $arguments The arguments.
     *
     * @return void
     */
    public function clearActionBox($arguments)
    {
        global $node;
        if ($node !== $this->node) {
            return;
        }
        $arguments['actionbox'] = '';
    }
}
