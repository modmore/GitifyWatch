<?php
/**
 * @var modX $modx
 */
$evs = [
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
$events = [];

foreach ($evs as $e) {
    $events[$e] = $modx->newObject('modPluginEvent');
    $events[$e]->fromArray([
        'event' => $e,
        'priority' => 10, // firing a bit later so other plugins can do their thing first
        'propertyset' => 0,
    ], '', true, true);
}

return $events;
