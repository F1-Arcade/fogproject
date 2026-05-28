<?php
/**
 * Installs and activates a FOG plugin by name.
 *
 * Used by the container's fog-plugin-install service to enable plugins
 * declaratively via the FOG_PLUGINS environment variable. Equivalent to
 * clicking "Install" then "Activate" in the web UI's Plugin Management page.
 *
 * @category Install_Plugin
 * @package  FOGProject
 * @author   Container auto-registration
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
require '../commons/base.inc.php';

// All values are base64-encoded (same convention as create_update_node.php).
foreach ((array)$_POST as $key => &$val) {
    if (!isset($val)) {
        continue;
    }
    $_POST[$key] = trim(base64_decode($val));
    unset($val);
}

if (!isset($_POST['fogverified'])) {
    return;
}
if (empty($_POST['plugin'])) {
    echo 'missing_name';
    return;
}

$name = strtolower(trim($_POST['plugin']));

// Whitelist: name must match an existing plugin directory on disk.
$pluginDir = BASEPATH . 'lib/plugins/' . $name;
if (!is_dir($pluginDir) || !is_file($pluginDir . '/config/plugin.config.php')) {
    echo 'unknown_plugin';
    return;
}

// Ensure the plugin system is enabled globally.
$Setting = FOGCore::getClass('Service')
    ->set('name', 'FOG_PLUGINSYS_ENABLED')
    ->load('name');
if ($Setting->get('value') !== '1') {
    $Setting->set('value', '1')->save();
}

// Look up (or create) the Plugin row.
$Plugin = FOGCore::getClass('Plugin')
    ->set('name', $name)
    ->load('name');

$wasInstalled = (isset($Plugin->installed) && $Plugin->installed);

if (!$wasInstalled) {
    // Run the plugin's own install routine (creates its DB tables).
    if (!$Plugin->getManager()->install($name)) {
        echo 'install_failed';
        return;
    }
    $Plugin
        ->set('name', $name)
        ->set('installed', 1)
        ->set('version', 1);
}

// Activate regardless of prior state so toggling FOG_PLUGINS is idempotent.
$Plugin->set('state', 1);

if (!$Plugin->save()) {
    echo 'save_failed';
    return;
}

echo $wasInstalled ? 'activated' : 'installed';
