<?php
/**
 * @var modX|MODX\Revolution\modX $modx
 * @var array $scriptProperties
 * @var GitifyWatch $gitifywatch
 */

$path = $modx->getOption('gitifywatch.core_path', null, MODX_CORE_PATH  . 'components/gitifywatch/', true);
require_once($path . 'model/gitifywatch/gitifywatch.class.php');
$gitifywatch = $modx->getService('gitifywatch', GitifyWatch::class, $path . 'model/gitifywatch/');

if (!$gitifywatch) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Could not load gitifywatch service from ' . $path);
    return;
}

$path = $modx->getOption('scheduler.core_path', null, $modx->getOption('core_path') . 'components/scheduler/');
$scheduler = $modx->getService('scheduler', Scheduler::class, $path . 'model/scheduler/');
if (!$scheduler) {
    $modx->log(modX::LOG_LEVEL_ERROR, 'Could not load Scheduler service from ' . $path);
    return;
}

$environment = $gitifywatch->getEnvironment();
$trigger = false;
$username = ($modx->user) ? $modx->user->get('username') : 'Anonymous';

if (!$environment || !$environment['auto_commit_and_push']) {
    $modx->log(modX::LOG_LEVEL_WARN, '[GitifyWatch] Not allowed to commit and push on this environment: ' . print_r($environment, true), '', 'GitifyWatch plugin', __FILE__, __LINE__);
    return;
}

switch ($modx->event->name) {
    case 'OnDocFormSave':
        /**
         * @var string $mode
         * @var modResource $resource
         */
        $trigger = [
            'username' => $username,
            'mode' => ($mode === modSystemEvent::MODE_NEW) ? 'created' : 'edited',
            'target' => $resource->get('pagetitle'),
            'partition' => $environment['partitions']['modResource'],
        ];
        break;

    case 'OnTempFormSave':
        /**
         * @var string $mode
         * @var modTemplate $template
         */
        $trigger = [
            'username' => $username,
            'mode' => ($mode === modSystemEvent::MODE_NEW) ? 'created' : 'edited',
            'target' => $template->get('templatename'),
            'partition' => $environment['partitions']['modTemplate'],
        ];
        break;

    case 'OnTempFormDelete':
        /**
         * @var modTemplate $template
         */
        $trigger = [
            'username' => $username,
            'mode' => 'deleted',
            'target' => $template->get('templatename'),
            'partition' => $environment['partitions']['modTemplate'],
        ];
        break;

    case 'OnTVFormSave':
        /**
         * @var int $mode
         * @var modTemplateVar $tv
         */
        $trigger = [
            'username' => $username,
            'mode' => ($mode === modSystemEvent::MODE_NEW) ? 'created' : 'edited',
            'target' => $tv->get('name'),
            'partition' => $environment['partitions']['modTemplateVar'],
        ];
        break;
    case 'OnTVFormDelete':
        /**
         * @var modTemplateVar $tv
         */
        $trigger = [
            'username' => $username,
            'mode' => 'deleted',
            'target' => $tv->get('name'),
            'partition' => $environment['partitions']['modTemplateVar'],
        ];
        break;

    case 'OnChunkFormSave':
        /**
         * @var string $mode
         * @var modChunk $chunk
         */
        $trigger = [
            'username' => $username,
            'mode' => ($mode === modSystemEvent::MODE_NEW) ? 'created' : 'edited',
            'target' => $chunk->get('name'),
            'partition' => $environment['partitions']['modChunk'],
        ];
        break;
    case 'OnChunkFormDelete':
        /**
         * @var modChunk $chunk
         */
        $trigger = [
            'username' => $username,
            'mode' => 'deleted',
            'target' => $chunk->get('name'),
            'partition' => $environment['partitions']['modChunk'],
        ];
        break;
    
    case 'OnSnipFormSave':
        /**
         * @var string $mode
         * @var modSnippet $snippet
         */
        $trigger = [
            'username' => $username,
            'mode' => ($mode === modSystemEvent::MODE_NEW) ? 'created' : 'edited',
            'target' => $snippet->get('name'),
            'partition' => $environment['partitions']['modSnippet'],
        ];
        break;
    case 'OnSnipFormDelete':
        /**
         * @var modSnippet $snippet
         */
        $trigger = [
            'username' => $username,
            'mode' => 'deleted',
            'target' => $snippet->get('name'),
            'partition' => $environment['partitions']['modSnippet'],
        ];
        break;
    case 'OnPluginFormSave':
        /**
         * @var string $mode
         * @var modPlugin $plugin
         */
        $trigger = [
            'username' => $username,
            'mode' => ($mode === modSystemEvent::MODE_NEW) ? 'created' : 'edited',
            'target' => $plugin->get('name'),
            'partition' => $environment['partitions']['modPlugin'],
        ];
        break;
    case 'OnPluginFormDelete':
        /**
         * @var modPlugin $plugin
         */
        $trigger = [
            'username' => $username,
            'mode' => 'deleted',
            'target' => $plugin->get('name'),
            'partition' => $environment['partitions']['modPlugin'],
        ];
        break;
}

if ($trigger) {
    /** @var sTask $task */
    $task = $scheduler->getTask('gitifywatch', 'extract');
    if ($task instanceof sTask) {
        // Try to find one already scheduled
        $run = $modx->getObject(sTaskRun::class, [
            'task' => $task->get('id'),
            'status' => sTaskRun::STATUS_SCHEDULED,
        ]);

        if ($run instanceof sTaskRun) {
            $data = $run->get('data');
            $data['triggers'][] = $trigger;
            $run->set('data', $data);
            $run->save();
        } else {
            $commitDelay = isset($environment['commit_delay']) ? $environment['commit_delay'] : 'instant';
            if ($commitDelay == 'instant') {
                $time = time() - 60;
            }
            else {
                $time = time() + ($commitDelay * 60);
            }

            $task->schedule($time, [
                'triggers' => [$trigger],
            ]);
        }
    }
    else {
        $modx->log(modX::LOG_LEVEL_ERROR, 'Could not find sTask gitifywatch:extract');
    }
}