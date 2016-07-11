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

/**
 * PHP Lint Plugin - Provides access to PHP lint functionality.
 * @author       Dan Cryer <dan@block8.co.uk>
 * @package      PHPCI
 * @subpackage   Plugins
 */
class Lint extends Plugin
{
    protected $directories;
    protected $recursive = true;
    protected $ignore;

    /**
     * {@inheritdoc}
     */
    public function __construct(Builder $phpci, Build $build, array $options = [])
    {
        parent::__construct($phpci, $build, $options);

        $this->directories = [''];
        $this->ignore      = $this->phpci->ignore;

        if (!empty($options['directory'])) {
            $this->directories[] = $options['directory'];
        }

        if (!empty($options['directories'])) {
            $this->directories = $options['directories'];
        }

        if (array_key_exists('recursive', $options)) {
            $this->recursive = $options['recursive'];
        }
    }

    /**
     * Executes parallel lint
     */
    public function execute()
    {
        $this->phpci->quiet = true;
        $success            = true;

        $php = $this->phpci->findBinary('php');

        foreach ($this->directories as $dir) {
            if (!$this->lintDirectory($php, $dir)) {
                $success = false;
            }
        }

        $this->phpci->quiet = false;

        return $success;
    }

    /**
     * Lint an item (file or directory) by calling the appropriate method.
     * @param $php
     * @param $item
     * @param $itemPath
     * @return bool
     */
    protected function lintItem($php, $item, $itemPath)
    {
        $success = true;

        if ($item->isFile() && $item->getExtension() == 'php' && !$this->lintFile($php, $itemPath)) {
            $success = false;
        } elseif ($item->isDir() && $this->recursive && !$this->lintDirectory($php, $itemPath . DIRECTORY_SEPARATOR)) {
            $success = false;
        }

        return $success;
    }

    /**
     * Run php -l against a directory of files.
     * @param $php
     * @param $path
     * @return bool
     */
    protected function lintDirectory($php, $path)
    {
        $success = true;
        $directory = new \DirectoryIterator($this->phpci->buildPath . $path);

        foreach ($directory as $item) {
            if ($item->isDot()) {
                continue;
            }

            $itemPath = $path . $item->getFilename();

            if (in_array($itemPath, $this->ignore)) {
                continue;
            }

            if (!$this->lintItem($php, $item, $itemPath)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Run php -l against a specific file.
     * @param $php
     * @param $path
     * @return bool
     */
    protected function lintFile($php, $path)
    {
        $success = true;

        if (!$this->phpci->executeCommand($php . ' -l "%s"', $this->phpci->buildPath . $path)) {
            $this->phpci->logFailure($path);
            $success = false;
        }

        return $success;
    }
}
