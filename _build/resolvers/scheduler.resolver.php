<?php
/** @var modX $modx */
/** @var Scheduler $scheduler */
$modx = $object->xpdo;

$path = $modx->getOption('scheduler.core_path', null, $modx->getOption('core_path') . 'components/scheduler/');
$scheduler = $modx->getService('scheduler', 'Scheduler', $path . 'model/scheduler/');

if (!$scheduler) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Scheduler does not seem to be installed! GitifyWatch depends on
    Scheduler for running the extract asynchronously. Please install Scheduler first and reinstall GitifyWatch after that.');
    return false;
}
else {
    if (!$scheduler->getTask('gitifywatch', 'extract')) {
        $task = $modx->newObject('sTask');
        $task->fromArray(array(
            'class_key' => 'sFileTask',
            'content' => 'elements/tasks/extract.task.php',
            'namespace' => 'gitifywatch',
            'reference' => 'extract',
            'description' => 'Extracts data from the database, commits it and pushes it to the remote git server.'
        ));
        return $task->save();
    }
}