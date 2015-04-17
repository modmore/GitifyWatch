<?php
$evs = array(
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
);
$events = array();

foreach ($evs as $e) {
    $events[$e] = $modx->newObject('modPluginEvent');
    $events[$e]->fromArray(array(
        'event' => $e,
        'priority' => 10, // firing a bit later so other plugins can do their thing first
        'propertyset' => 0,
    ), '', true, true);
}

return $events;
