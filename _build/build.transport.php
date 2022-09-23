<?php

/**
 * @param string $filename The name of the file.
 * @return string The file's content
 * @by splittingred
 */
function getSnippetContent($filename = ''): string
{
    $o = file_get_contents($filename);
    $o = str_replace('<?php','',$o);
    $o = str_replace('?>','',$o);
    $o = trim($o);
    return $o;
}

$tstart = explode(' ', microtime());
$tstart = $tstart[1] + $tstart[0];
set_time_limit(0);

if (!defined('MOREPROVIDER_BUILD')) {
    /* define version */
    define('PKG_NAME','GitifyWatch');
    define('PKG_NAME_LOWER',strtolower(PKG_NAME));
    define('PKG_VERSION','2.0.0');
    define('PKG_RELEASE','rc1');

    /* load modx */
    require_once dirname(dirname(__FILE__)) . '/config.core.php';
    require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
    $modx= new modX();
    $modx->initialize('mgr');
    $modx->setLogLevel(modX::LOG_LEVEL_INFO);
    $modx->setLogTarget('ECHO');


    echo '<pre>';
    flush();
    $targetDirectory = dirname(dirname(__FILE__)) . '/_packages/';
}
else {
    $targetDirectory = MOREPROVIDER_BUILD_TARGET;
}
/* define build paths */
$root = dirname(dirname(__FILE__)).'/';
$sources = [
    'root' => $root,
    'build' => $root.'_build/',
    'data' => $root.'_build/data/',
    'validators' => $root.'_build/validators/',
    'resolvers' => $root.'_build/resolvers/',
    'chunks' => $root.'core/components/'.PKG_NAME_LOWER.'/elements/chunks/',
    'snippets' => $root.'core/components/'.PKG_NAME_LOWER.'/elements/snippets/',
    'plugins' => $root.'core/components/'.PKG_NAME_LOWER.'/elements/plugins/',
    'lexicon' => $root.'core/components/'.PKG_NAME_LOWER.'/lexicon/',
    'docs' => $root.'core/components/'.PKG_NAME_LOWER.'/docs/',
    'elements' => $root.'core/components/'.PKG_NAME_LOWER.'/elements/',
    'source_assets' => $root.'assets/components/'.PKG_NAME_LOWER.'/',
    'source_core' => $root.'core/components/'.PKG_NAME_LOWER.'/',
];
unset($root);

$modx->loadClass('transport.modPackageBuilder','',false, true);
/** @var modPackageBuilder $builder **/
$builder = new modPackageBuilder($modx);
$builder->directory = $targetDirectory;
$builder->createPackage(PKG_NAME_LOWER,PKG_VERSION,PKG_RELEASE);
$builder->registerNamespace(PKG_NAME_LOWER, false, true, '{core_path}components/'.PKG_NAME_LOWER.'/');

/* Settings */
$settings = include_once $sources['data'].'transport.settings.php';
$attributes= [
    xPDOTransport::UNIQUE_KEY => 'key',
    xPDOTransport::PRESERVE_KEYS => true,
    xPDOTransport::UPDATE_OBJECT => false,
];
if (is_array($settings)) {
    foreach ($settings as $setting) {
        $vehicle = $builder->createVehicle($setting,$attributes);
        $builder->putVehicle($vehicle);
    }
    $modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($settings).' system settings.'); flush();
    unset($settings,$setting,$attributes);
}

/** @var $category modCategory */
$category = $modx->newObject('modCategory');
$category->set('category',PKG_NAME);

/* add plugin */
$plugins = include $sources['data'].'transport.plugins.php';
if (is_array($plugins)) {
    $category->addMany($plugins,'Plugins');
    $modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($plugins).' plugins.'); flush();
}
else {
    $modx->log(modX::LOG_LEVEL_FATAL,'Adding plugins failed.');
}
unset($plugins);


/* create category vehicle */
$attr = [
    xPDOTransport::UNIQUE_KEY => 'category',
    xPDOTransport::PRESERVE_KEYS => true,
    xPDOTransport::UPDATE_OBJECT => false,
    xPDOTransport::RELATED_OBJECTS => true,
    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
        'Plugins' => [
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                'PluginEvents' => [
                    xPDOTransport::PRESERVE_KEYS => true,
                    xPDOTransport::UPDATE_OBJECT => false,
                    xPDOTransport::UNIQUE_KEY => ['pluginid','event'],
                ],
            ],
        ],
    ]
];

$vehicle = $builder->createVehicle($category,$attr);

/* file resolvers */
$modx->log(modX::LOG_LEVEL_INFO, 'Adding core/assets file resolvers to category...');
/*$vehicle->resolve('file',array(
    'source' => $sources['source_assets'],
    'target' => "return MODX_ASSETS_PATH . 'components/';",
));*/
$vehicle->resolve('file', [
    'source' => $sources['source_core'],
    'target' => "return MODX_CORE_PATH . 'components/';",
]);
$vehicle->resolve('php', [
    'source' => $sources['resolvers'] . 'scheduler.resolver.php',
]);
$vehicle->resolve('php', [
    'source' => $sources['resolvers'] . 'composer.resolver.php',
]);

$builder->putVehicle($vehicle);
unset($vehicle, $menu);


/* zip up package */
$modx->log(modX::LOG_LEVEL_INFO,'Adding package attributes and setup options...');
$builder->setPackageAttributes([
    'license' => file_get_contents($sources['docs'].'license.txt'),
    'readme' => file_get_contents($sources['docs'].'readme.txt'),
    'changelog' => file_get_contents($sources['docs'].'changelog.txt'),
    /*'setup-options' => array(
        'source' => $sources['build'].'setup.options.php',
    ),*/
]);

$modx->log(modX::LOG_LEVEL_INFO,'Packing up transport package zip...');
$builder->pack();

$tend = explode(" ", microtime());
$tend = $tend[1] + $tend[0];
$totalTime = sprintf("%2.4f s", ($tend - $tstart));

$modx->log(modX::LOG_LEVEL_INFO, "Package Built. Execution time: {$totalTime}\n");
$modx->log(modX::LOG_LEVEL_INFO, "\n-----------------------------\n".PKG_NAME." ".PKG_VERSION."-".PKG_RELEASE." built\n-----------------------------");
flush();
