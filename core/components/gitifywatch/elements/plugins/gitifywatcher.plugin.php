<?php
/**
 * @var modX $modx
 * @var array $scriptProperties
 * @var GitifyWatch $gitifywatch
 */

use mhwd\GitifyWatch;

$path = $modx->getOption('gitifywatch.core_path', null, MODX_CORE_PATH  . 'components/gitifywatch/', true);
require_once($path . 'model/gitifywatch/gitifywatch.class.php');
$gitifywatch = $modx->getService('gitifywatch', 'mhwd\GitifyWatch', $path . 'model/gitifywatch/');

if (!$gitifywatch) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Could not load gitifywatch service from ' . $path);
    return;
}

$path = $modx->getOption('scheduler.core_path', null, $modx->getOption('core_path') . 'components/scheduler/');
$scheduler = $modx->getService('scheduler', 'Scheduler', $path . 'model/scheduler/');
if (!$scheduler) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Could not load Scheduler service from ' . $path);
    return;
}

$environment = $gitifywatch->getEnvironment();
$trigger = false;
$username = ($modx->user) ? $modx->user->get('username') : 'Anonymous';


switch ($modx->event->name) {
    case 'OnDocFormSave':
        /**
         * @var int $mode
         * @var modResource $resource
         */
        $trigger = array(
            'username' => $username,
            'mode' => ($mode === modSystemEvent::MODE_NEW) ? 'created' : 'edited',
            'target' => $resource->get('pagetitle'),
            'partition' => $environment['partitions']['modResource'],
        );
        break;

    case 'OnTempFormSave':
        /**
         * @var int $mode
         * @var modTemplate $template
         */
        $trigger = array(
            'username' => $username,
            'mode' => ($mode === modSystemEvent::MODE_NEW) ? 'created' : 'edited',
            'target' => $template->get('templatename'),
            'partition' => $environment['partitions']['modTemplate'],
        );
        break;

    case 'OnTempFormDelete':
        /**
         * @var modTemplate $template
         */
        $trigger = array(
            'username' => $username,
            'mode' => 'deleted',
            'target' => $template->get('templatename'),
            'partition' => $environment['partitions']['modTemplate'],
        );
        break;

    case 'OnTVFormSave':
        /**
         * @var int $mode
         * @var modTemplateVar $tv
         */
        $trigger = array(
            'username' => $username,
            'mode' => ($mode === modSystemEvent::MODE_NEW) ? 'created' : 'edited',
            'target' => $tv->get('name'),
            'partition' => $environment['partitions']['modTemplateVar'],
        );
        break;
    case 'OnTVFormDelete':
        /**
         * @var modTemplateVar $tv
         */
        $trigger = array(
            'username' => $username,
            'mode' => 'deleted',
            'target' => $tv->get('name'),
            'partition' => $environment['partitions']['modTemplateVar'],
        );
        break;

    case 'OnChunkFormSave':
        /**
         * @var int $mode
         * @var modChunk $chunk
         */
        $trigger = array(
            'username' => $username,
            'mode' => ($mode === modSystemEvent::MODE_NEW) ? 'created' : 'edited',
            'target' => $chunk->get('name'),
            'partition' => $environment['partitions']['modChunk'],
        );
        break;
    case 'OnChunkFormDelete':
        /**
         * @var modChunk $chunk
         */
        $trigger = array(
            'username' => $username,
            'mode' => 'deleted',
            'target' => $chunk->get('name'),
            'partition' => $environment['partitions']['modChunk'],
        );
        break;
    
    case 'OnSnipFormSave':
        /**
         * @var int $mode
         * @var modSnippet $snippet
         */
        $trigger = array(
            'username' => $username,
            'mode' => ($mode === modSystemEvent::MODE_NEW) ? 'created' : 'edited',
            'target' => $snippet->get('name'),
            'partition' => $environment['partitions']['modSnippet'],
        );
        break;
    case 'OnSnipFormDelete':
        /**
         * @var modSnippet $snippet
         */
        $trigger = array(
            'username' => $username,
            'mode' => 'deleted',
            'target' => $snippet->get('name'),
            'partition' => $environment['partitions']['modSnippet'],
        );
        break;
    case 'OnPluginFormSave':
        /**
         * @var int $mode
         * @var modPlugin $plugin
         */
        $trigger = array(
            'username' => $username,
            'mode' => ($mode === modSystemEvent::MODE_NEW) ? 'created' : 'edited',
            'target' => $plugin->get('name'),
            'partition' => $environment['partitions']['modPlugin'],
        );
        break;
    case 'OnPluginFormDelete':
        /**
         * @var modPlugin $plugin
         */
        $trigger = array(
            'username' => $username,
            'mode' => 'deleted',
            'target' => $plugin->get('name'),
            'partition' => $environment['partitions']['modPlugin'],
        );
        break;
}

if ($trigger) {
    /** @var sTask $task */
    $task = $scheduler->getTask('gitifywatch', 'extract');
    if ($task instanceof sTask) {
        // Try to find one already scheduled
        $run = $modx->getObject('sTaskRun', array(
            'task' => $task->get('id'),
            'status' => sTaskRun::STATUS_SCHEDULED,
        ));

        if ($run instanceof sTaskRun) {
            $data = $run->get('data');
            $data['triggers'][] = $trigger;
            $run->set('data', $data);
            $run->save();
        } else {
            $task->schedule(time() - 60, array(
                'triggers' => array($trigger),
            ));
        }
    }
    else {
        $modx->log(modX::LOG_LEVEL_ERROR, 'Could not find sTask gitifywatch:extract');
    }
}