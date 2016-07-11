<?php
/**
 * PHPCI - Continuous Integration for PHP
 *
 * @copyright    Copyright 2014, Block 8 Limited.
 * @license      https://github.com/Block8/PHPCI/blob/master/LICENSE.md
 * @link         https://www.phptesting.org/
 */

namespace PHPCensor\Plugin;

use PHPCensor\Builder;
use PHPCensor\Helper\Lang;
use PHPCensor\Model\Build;
use PHPCensor\Plugin;

/**
 * IRC Plugin - Sends a notification to an IRC channel
 * @author       Dan Cryer <dan@block8.co.uk>
 * @package      PHPCI
 * @subpackage   Plugins
 */
class Irc extends Plugin
{
    protected $message;
    protected $server;
    protected $port;
    protected $room;
    protected $nick;

    /**
     * {@inheritdoc}
     */
    public function __construct(Builder $phpci, Build $build, array $options = [])
    {
        parent::__construct($phpci, $build, $options);

        $this->message = $options['message'];
        $buildSettings = $this->phpci->getConfig('build_settings');

        if (isset($buildSettings['irc'])) {
            $irc = $buildSettings['irc'];

            $this->server = $irc['server'];
            $this->port   = $irc['port'];
            $this->room   = $irc['room'];
            $this->nick   = $irc['nick'];
        }
    }

    /**
     * Run IRC plugin.
     * @return bool
     */
    public function execute()
    {
        $msg = $this->phpci->interpolate($this->message);

        if (empty($this->server) || empty($this->room) || empty($this->nick)) {
            $this->phpci->logFailure(Lang::get('irc_settings'));
        }

        if (empty($this->port)) {
            $this->port = 6667;
        }

        $sock = fsockopen($this->server, $this->port);
        stream_set_timeout($sock, 1);

        $connectCommands = [
            'USER ' . $this->nick . ' 0 * :' . $this->nick,
            'NICK ' . $this->nick,
        ];
        $this->executeIrcCommands($sock, $connectCommands);
        $this->executeIrcCommand($sock, 'JOIN ' . $this->room);
        $this->executeIrcCommand($sock, 'PRIVMSG ' . $this->room . ' :' . $msg);

        fclose($sock);

        return true;
    }

    /**
     * @param resource $socket
     * @param array $commands
     * @return bool
     */
    private function executeIrcCommands($socket, array $commands)
    {
        foreach ($commands as $command) {
            fputs($socket, $command . "\n");
        }

        $pingBack = false;

        // almost all servers expect pingback!
        while ($response = fgets($socket)) {
            $matches = [];
            if (preg_match('/^PING \\:([A-Z0-9]+)/', $response, $matches)) {
                $pingBack = $matches[1];
            }
        }

        if ($pingBack) {
            $command = 'PONG :' . $pingBack . "\n";
            fputs($socket, $command);
        }
    }

    /**
     *
     * @param resource $socket
     * @param string $command
     * @return bool
     */
    private function executeIrcCommand($socket, $command)
    {
        return $this->executeIrcCommands($socket, [$command]);
    }
}
