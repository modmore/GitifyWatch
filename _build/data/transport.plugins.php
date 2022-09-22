<?php
/**
 * @var modX $modx
 * @var array $sources
 */
$plugins = [];

/** create the plugin object */
$plugins[0] = $modx->newObject('modPlugin');
$plugins[0]->set('name', 'GitifyWatch');
$plugins[0]->set('description', 'Watches for changes in resources and elements to automatically run Gitify and git to commit & push changes.');
$plugins[0]->set('plugincode', getSnippetContent($sources['plugins'] . 'gitifywatch.plugin.php'));

$events = include $sources['data'].'transport.plugin.events.php';
if (is_array($events) && !empty($events)) {
    $plugins[0]->addMany($events);
    $modx->log(xPDO::LOG_LEVEL_INFO,'Packaged in '.count($events).' Plugin Events for GitifyWatch.'); flush();
} else {
    $modx->log(xPDO::LOG_LEVEL_ERROR,'Could not find plugin events for GitifyWatch!');
}
unset($events);

return $plugins;
