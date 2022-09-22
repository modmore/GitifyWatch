<?php
/* Get the core config */
if (!file_exists(dirname(dirname(__FILE__)).'/config.core.php')) {
    die('ERROR: missing '.dirname(dirname(__FILE__)).'/config.core.php file defining the MODX core path.');
}

echo "<pre>";
/* Boot up MODX */
echo "Loading modX...\n";
require_once dirname(dirname(__FILE__)).'/config.core.php';
require_once MODX_CORE_PATH.'model/modx/modx.class.php';
$modx = new modX();
echo "Initializing manager...\n";
$modx->initialize('mgr');
$modx->getService('error','error.modError', '', '');

$componentPath = dirname(dirname(__FILE__));

require_once($componentPath . '/core/components/gitifywatch/model/gitifywatch/gitifywatch.class.php');
$gitifywatch = $modx->getService('gitifywatch','GitifyWatch', $componentPath.'/core/components/gitifywatch/model/gitifywatch/', [
    'gitifywatch.core_path' => $componentPath.'/core/components/gitifywatch/',
]);


/* Namespace */
if (!createObject('modNamespace', [
    'name' => 'gitifywatch',
    'path' => $componentPath.'/core/components/gitifywatch/',
    'assets_path' => $componentPath.'/assets/components/gitifywatch/',
],'name', false)) {
    echo "Error creating namespace gitifywatch.\n";
}

/* Path settings */
if (!createObject('modSystemSetting', [
    'key' => 'gitifywatch.core_path',
    'value' => $componentPath.'/core/components/gitifywatch/',
    'xtype' => 'textfield',
    'namespace' => 'gitifywatch',
    'area' => 'Paths',
    'editedon' => time(),
], 'key', false)) {
    echo "Error creating gitifywatch.core_path setting.\n";
}

/* Fetch assets url */
$url = 'http';
if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) {
    $url .= 's';
}
$url .= '://'.$_SERVER["SERVER_NAME"];
if ($_SERVER['SERVER_PORT'] != '80') {
    $url .= ':'.$_SERVER['SERVER_PORT'];
}
$requestUri = $_SERVER['REQUEST_URI'];
$bootstrapPos = strpos($requestUri, '_bootstrap/');
$requestUri = rtrim(substr($requestUri, 0, $bootstrapPos), '/').'/';
$assetsUrl = "{$url}{$requestUri}assets/components/gitifywatch/";

if (!createObject('modSystemSetting', [
    'key' => 'gitifywatch.assets_url',
    'value' => $assetsUrl,
    'xtype' => 'textfield',
    'namespace' => 'gitifywatch',
    'area' => 'Paths',
    'editedon' => time(),
], 'key', false)) {
    echo "Error creating gitifywatch.assets_url setting.\n";
}


if (!createObject('modPlugin', [
    'name' => 'GitifyWatch',
    'static' => true,
    'static_file' => $componentPath.'/core/components/gitifywatch/elements/plugins/gitifywatch.plugin.php',
], 'name', false)) {
    echo "Error creating GitifyWatch Plugin.\n";
}
$plugin = $modx->getObject('modPlugin', ['name' => 'GitifyWatch']);
if ($plugin) {
    $events = [
        'OnDocFormSave',
        'OnTempFormSave',
        'OnTempFormDelete',
        'OnTVFormSave',
        'OnTVFormDelete',
        'OnChunkFormSave',
        'OnChunkFormDelete',
        'OnSnipFormSave',
        'OnSnipFormDelete',
        'OnPluginFormSave',
        'OnPluginFormDelete',
    ];

    foreach ($events as $ev) {
        if (!createObject('modPluginEvent', [
            'pluginid' => $plugin->get('id'),
            'event' => $ev,
            'priority' => 0,
        ], ['pluginid','event'], false)) {
            echo "Error creating modPluginEvent {$ev}.\n";
        }
    }
}

// Menu
/*
if (!createObject('modAction', array(
    'namespace' => 'gitifywatch',
    'parent' => '0',
    'controller' => 'index',
    'haslayout' => '1',
    'lang_topics' => 'gitifywatch:default',
), 'namespace', false)) {
    echo "Error creating action.\n";
}
$action = $modx->getObject('modAction', array(
    'namespace' => 'gitifywatch'
));

if ($action) {
    if (!createObject('modMenu', array(
        'text' => 'gitifywatch.menu',
        'parent' => 'components',
        'description' => 'gitifywatch.menu_desc',
        'icon' => 'images/icons/plugin.gif',
        'menuindex' => '0',
        'action' => $action->get('id')
    ), 'text', false)) {
        echo "Error creating menu.\n";
    }
}
*/

$settings = include dirname(dirname(__FILE__)) . '/_build/data/settings.php';
foreach ($settings as $key => $opts) {
    if (!createObject('modSystemSetting', [
        'key' => 'gitifywatch.' . $key,
        'value' => $opts['value'],
        'xtype' => (isset($opts['xtype'])) ? $opts['xtype'] : 'textfield',
        'namespace' => 'gitifywatch',
        'area' => $opts['area'],
        'editedon' => time(),
    ], 'key', false)) {
        echo "Error creating gitifywatch.".$key." setting.\n";
    }
}


/** @var Scheduler $scheduler */
$path = $modx->getOption('scheduler.core_path', null, $modx->getOption('core_path') . 'components/scheduler/');
$scheduler = $modx->getService('scheduler', 'Scheduler', $path . 'model/scheduler/');

if (!$scheduler) {
    echo "<strong>Please install Scheduler to install the Tasks</strong>\n";
}
elseif (!createObject('sTask', [
    'class_key' => 'sFileTask',
    'content' => 'elements/tasks/extract.task.php',
    'namespace' => 'gitifywatch',
    'reference' => 'extract',
    'description' => 'Extracts data from the database, commits it and pushes it to the remote git server.'
], 'reference', false)) {
    echo "Error creating sTask object";
}



$manager = $modx->getManager();


/* Create the tables */
/*
$objectContainers = array(

);
echo "Creating tables...\n";

foreach ($objectContainers as $oC) {
    $manager->createObjectContainer($oC);
}

echo "Done.";
*/


/**
 * Creates an object.
 *
 * @param string $className
 * @param array $data
 * @param string $primaryField
 * @param bool $update
 * @return bool
 */
function createObject ($className = '', array $data = [], $primaryField = '', $update = true) {
    global $modx;
    /* @var xPDOObject $object */
    $object = null;

    /* Attempt to get the existing object */
    if (!empty($primaryField)) {
        if (is_array($primaryField)) {
            $condition = [];
            foreach ($primaryField as $key) {
                $condition[$key] = $data[$key];
            }
        }
        else {
            $condition = [$primaryField => $data[$primaryField]];
        }
        $object = $modx->getObject($className, $condition);
        if ($object instanceof $className) {
            if ($update) {
                $object->fromArray($data);
                return $object->save();
            } else {
                $condition = $modx->toJSON($condition);
                echo "Skipping {$className} {$condition}: already exists.\n";
                return true;
            }
        }
    }

    /* Create new object if it doesn't exist */
    if (!$object) {
        $object = $modx->newObject($className);
        $object->fromArray($data, '', true);
        return $object->save();
    }

    return false;
}
