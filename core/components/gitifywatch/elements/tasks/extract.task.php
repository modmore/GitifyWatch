<?php
/**
 * @var modX $modx
 * @var sTask $task
 * @var sTaskRun $run
 *
 * @var GitifyWatch $gitifywatch
 */

use mhwd\GitifyWatch;

$path = $modx->getOption('gitifywatch.core_path', null, MODX_CORE_PATH  . 'components/gitifywatch/', true);
require_once($path . 'model/gitifywatch/gitifywatch.class.php');
$gitifywatch = $modx->getService('gitifywatch', 'mhwd\GitifyWatch', $path . 'model/gitifywatch/');

if (!$gitifywatch) {
    $run->addError('error_loading_service', array(
        'message' => 'Could not load required gitifywatch service.',
        'path' => $path . 'model/gitifywatch/',
    ));
    return false;
}

$environment = $gitifywatch->getEnvironment();

$data = $run->get('data');

$partitions = array();
$users = array();
$targets = array();
$chronological = array();
$modes = array();

foreach ($data['triggers'] as $trigger) {
    $chronological[] = "{$trigger['username']} {$trigger['mode']} {$trigger['target']} ";

    if (!in_array($trigger['username'], $users)) {
        $users[] = $trigger['username'];
    }

    if (!in_array($trigger['partition'], $partitions)) {
        $partitions[] = $trigger['partition'];
    }

    if (!in_array($trigger['target'], $targets)) {
        $targets[] = $trigger['target'];
    }

    if (!in_array($trigger['mode'], $modes)) {
        $modes[] = $trigger['mode'];
    }

}

if (count($chronological) === 1) {
    $message = reset($chronological) . ' on ' . $environment['name'];
}
else {
    // Start by saying who did something
    $uc = count($users);
    if ($uc > 2) {
        $message = "{$uc} users";
    }
    else {
        $message = $gitifywatch->niceImplode($users);
    }

    // Add what happened (created, edited, deleted etc)
    $message .= ' ' . $gitifywatch->niceImplode($modes) . ' ';

    // Add the targets
    $targetCount = count($targets);
    if ($targetCount > 2) {
        $message .= $targetCount . ' objects';
    }
    else {
        $message .= $gitifywatch->niceImplode($targets);
    }

    // Add the environment name
    $message .= ' on ' . $environment['name'];

    // If there were multiple events, add in a description to the commit
    if (count($chronological) > 1) {
        $message .= "\n\n * " . implode("\n * ", $chronological);
    }
}

$gitifywatch->extract($partitions, true, $message);
