<?php
/**
 * Plugin configuration file.
 *
 * PHP version 5
 *
 * @category Config
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
$fog_plugin = array();
$fog_plugin['name'] = 'venue';
$fog_plugin['description'] = 'Venue-based subnet routing for hosts, tasks, and '
    . 'storage nodes. Associates subnets with storage groups and host groups '
    . 'for automatic provisioning. Replaces location and subnetgroup plugins.';
$fog_plugin['menuicon'] = 'fa fa-building';
$fog_plugin['menuicon_hover'] = null;
$fog_plugin['entrypoint'] = 'html/run.php';
