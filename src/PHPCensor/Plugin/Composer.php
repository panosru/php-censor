<?php
/**
 * PHPCI - Continuous Integration for PHP
 *
 * @copyright    Copyright 2014, Block 8 Limited.
 * @license      https://github.com/Block8/PHPCI/blob/master/LICENSE.md
 * @link         https://www.phptesting.org/
 */

namespace PHPCensor\Plugin;

use PHPCensor;
use PHPCensor\Builder;
use PHPCensor\Model\Build;
use PHPCensor\Plugin;
use PHPCensor\ZeroConfigPlugin;

/**
* Composer Plugin - Provides access to Composer functionality.
* @author       Dan Cryer <dan@block8.co.uk>
* @package      PHPCI
* @subpackage   Plugins
*/
class Composer extends Plugin implements ZeroConfigPlugin
{
    protected $directory;
    protected $action;
    protected $preferDist;
    protected $nodev;
    protected $ignorePlatformReqs;
    protected $preferSource;

    /**
     * {@inheritdoc}
     */
    public function __construct(Builder $phpci, Build $build, array $options = [])
    {
        parent::__construct($phpci, $build, $options);

        $path                     = $this->phpci->buildPath;
        $this->directory          = $path;
        $this->action             = 'install';
        $this->preferDist         = false;
        $this->preferSource       = false;
        $this->nodev              = false;
        $this->ignorePlatformReqs = false;

        if (array_key_exists('directory', $options)) {
            $this->directory = $path . DIRECTORY_SEPARATOR . $options['directory'];
        }

        if (array_key_exists('action', $options)) {
            $this->action = $options['action'];
        }

        if (array_key_exists('prefer_dist', $options)) {
            $this->preferDist = (bool)$options['prefer_dist'];
        }

        if (array_key_exists('prefer_source', $options)) {
            $this->preferDist   = false;
            $this->preferSource = (bool)$options['prefer_source'];
        }

        if (array_key_exists('no_dev', $options)) {
            $this->nodev = (bool)$options['no_dev'];
        }

        if (array_key_exists('ignore_platform_reqs', $options)) {
            $this->ignorePlatformReqs = (bool)$options['ignore_platform_reqs'];
        }
    }

    /**
     * Check if this plugin can be executed.
     * @param $stage
     * @param Builder $builder
     * @param Build $build
     * @return bool
     */
    public static function canExecute($stage, Builder $builder, Build $build)
    {
        $path = $builder->buildPath . DIRECTORY_SEPARATOR . 'composer.json';

        if (file_exists($path) && $stage == 'setup') {
            return true;
        }

        return false;
    }

    /**
    * Executes Composer and runs a specified command (e.g. install / update)
    */
    public function execute()
    {
        $composerLocation = $this->phpci->findBinary(['composer', 'composer.phar']);

        $cmd = '';

        if (IS_WIN) {
            $cmd = 'php ';
        }

        $cmd .= $composerLocation . ' --no-ansi --no-interaction ';

        if ($this->preferDist) {
            $this->phpci->log('Using --prefer-dist flag');
            $cmd .= ' --prefer-dist';
        }

        if ($this->preferSource) {
            $this->phpci->log('Using --prefer-source flag');
            $cmd .= ' --prefer-source';
        }

        if ($this->nodev) {
            $this->phpci->log('Using --no-dev flag');
            $cmd .= ' --no-dev';
        }

        if ($this->ignorePlatformReqs) {
            $this->phpci->log('Using --ignore-platform-reqs flag');
            $cmd .= ' --ignore-platform-reqs';
        }

        $cmd .= ' --working-dir="%s" %s';

        return $this->phpci->executeCommand($cmd, $this->directory, $this->action);
    }
}
