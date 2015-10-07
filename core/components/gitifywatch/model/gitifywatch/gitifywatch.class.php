<?php
namespace mhwd;

use Git;
use GitRepo;
use modmore\Gitify\Gitify;
use modX;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class GitifyWatch {
    public $config = array();

    protected $environment = array();

    /** @var modX|null  */
    public $modx;

    /** @var Gitify|null  */
    protected $gitify;

    /** @var GitRepo|null */
    protected $repository;

    public function __construct(modX $modx, array $config = array())
    {
        $this->modx = $modx;
        $this->config = array_merge(array(
            'repositoryPath' => $this->modx->getOption('gitifywatch.repository_path', null, MODX_BASE_PATH, true)
        ), $config);
    }

    public function getGitifyInstance(array $options = array()) {
        if (!$this->gitify) {
            $path = $this->modx->getOption('gitifywatch.gitify_path', null, false, true);
            if (!$path || !is_dir($path)) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not load Gitify: no path specified or it is not a valid directory: ' . $path, '', __METHOD__, __FILE__, __LINE__);
                return false;
            }

            if (!file_exists($path . 'application.php')) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not load Gitify: this integration requires at last v0.8.', '', __METHOD__, __FILE__, __LINE__);
                return false;
            }

            try {
                define('GITIFY_API_MODE', true);
                define('GITIFY_WORKING_DIR', $this->config['repositoryPath']);
                $gitify = include $path . 'application.php';
            } catch (\Exception $e) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not load Gitify: ' . $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
                return false;
            }

            $this->gitify = $gitify;
        }
        return $this->gitify;
    }

    public function getGitRepository()
    {
        if (!$this->repository) {
            if (!$this->getGitifyInstance()) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not load Gitify instance', '', __METHOD__, __FILE__, __LINE__);
                return false;
            }

            $repo = $this->gitify->getGitRepository();
            if (!$repo) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not load Git Repository object', '', __METHOD__, __FILE__, __LINE__);
                return false;
            }

            $this->repository = $repo;
        }

        return $this->repository;
    }

    public function extract(array $partitions = array(), $commit = true, $commitMessage = '')
    {
        $gitify = $this->getGitifyInstance();
        if (!$gitify) {
            return false;
        }

        $extract = $gitify->find('extract');
        $inputArray = array(
            'command' => 'extract',
        );
        if (count($partitions) > 0) {
            $inputArray['partitions'] = $partitions;
        }
        $input = new ArrayInput($inputArray);
        $output = new BufferedOutput();
        $returnCode = $extract->run($input, $output);

        if ($returnCode === 0) {
            if ($commit) {
                return $this->commitAndPush($commitMessage);
            }
            return true;
        }

        $this->modx->log(modX::LOG_LEVEL_ERROR, 'Error extracting data: ' . $output->fetch(), '', __METHOD__, __FILE__, __LINE__);
        return false;
    }


    /**
     * @param string $message
     * @return bool
     */
    public function commitAndPush($message = '')
    {
        $repo = $this->getGitRepository();
        $environment = $this->getEnvironment();

        if (!$repo) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not commit changes; repository was not found.');
            return false;
        }

        if (!$environment) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not commit changes; environment configuration not found.');
            return false;
        }

        try {
            $log = array();
            // Add all changed files
            $log['add'] = $repo->add('.');
            $log['commit'] = $repo->commit($message);
            $log['push'] = $repo->push($environment['remote'], $repo->active_branch());

            $this->modx->log(modX::LOG_LEVEL_WARN, 'Auto-committing & pushing results: ' . print_r($log, true), '', __METHOD__, __FILE__, __LINE__);

            return true;
        } catch (\Exception $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Error committing & pushing: ' . $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
            return false;
        }
    }

    public function getEnvironment()
    {
        if (empty($this->environment)) {
            try {
                if ($this->getGitifyInstance()) {
                    $this->environment = $this->gitify->getEnvironment();
                }
                else {
                    $this->modx->log(modX::LOG_LEVEL_ERROR, 'Error loading environment configuration: Gitify not loaded', '', __METHOD__, __FILE__, __LINE__);
                }
            } catch (\RuntimeException $e) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'Error loading environment configuration: ' . $e->getMessage(), '', __METHOD__, __FILE__, __LINE__);
                return false;
            }
        }
        return $this->environment;
    }

    public function niceImplode($items)
    {
        $count = count($items);
        if ($count === 1) {
            return reset($items);
        }
        if ($count === 2) {
            return reset($items) . ' and ' . end($items);
        }

        return implode(', ', array_slice($items, 0, -1)) . ' and ' . end($items);
    }
}