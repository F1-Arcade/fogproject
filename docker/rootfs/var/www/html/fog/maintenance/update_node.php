<?php
/**
 * Updates an existing storage node's configuration.
 * Only updates the node matching the provided IP — a node cannot
 * modify other nodes' records.
 *
 * @category Update_Node
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
if (empty($_POST['ip'])) {
    return;
}

// Find the node by IP — only allow updating itself.
$ip = trim($_POST['ip']);
$Nodes = FOGCore::getClass('StorageNodeManager')
    ->find(array('ip' => $ip));

if (count($Nodes) === 0) {
    echo 'not_found';
    return;
}

// Allowed updatable fields and their POST key → model property mapping.
$fields = array(
    'name'           => 'name',
    'path'           => 'path',
    'ftppath'        => 'ftppath',
    'snapinpath'     => 'snapinpath',
    'sslpath'        => 'sslpath',
    'maxClients'     => 'maxClients',
    'user'           => 'user',
    'pass'           => 'pass',
    'interface'      => 'interface',
    'bandwidth'      => 'bandwidth',
    'webroot'        => 'webroot',
    'isGraphEnabled' => 'isGraphEnabled',
    'isEnabled'      => 'isEnabled',
);

foreach ($Nodes as &$Node) {
    foreach ($fields as $postKey => $prop) {
        if (isset($_POST[$postKey]) && strlen($_POST[$postKey]) > 0) {
            $Node->set($prop, trim($_POST[$postKey]));
        }
    }
    $Node->save();
    unset($Node);
}

echo 'updated';
