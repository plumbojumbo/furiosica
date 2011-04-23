#!/usr/bin/env php
<?php
/*
 * Laconica - a distributed open-source microblogging tool
 * Copyright (C) 2008, Controlez-Vous, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

# Abort if called from a web server
if (isset($_SERVER) && array_key_exists('REQUEST_METHOD', $_SERVER)) {
    print "This script must be run from the command line\n";
    exit();
}

define('INSTALLDIR', realpath(dirname(__FILE__) . '/..'));
define('LACONICA', true);

require_once(INSTALLDIR . '/lib/common.php');
require_once(INSTALLDIR . '/lib/omb.php');
require_once(INSTALLDIR . '/lib/queuehandler.php');

set_error_handler('common_error_handler');

class OmbQueueHandler extends QueueHandler
{
    
    function transport()
    {
        return 'omb';
    }
    
    function start()
    {
        $this->log(LOG_INFO, "INITIALIZE");
        return true;
    }

    function handle_notice($notice)
    {
        if ($this->is_remote($notice)) {
            $this->log(LOG_DEBUG, 'Ignoring remote notice ' . $notice->id);
            return true;
        } else {
            return omb_broadcast_remote_subscribers($notice);
        }
    }
    
    function finish()
    {
    }

    function is_remote($notice)
    {
        $user = User::staticGet($notice->profile_id);
        return is_null($user);
    }
}

ini_set("max_execution_time", "0");
ini_set("max_input_time", "0");
set_time_limit(0);
mb_internal_encoding('UTF-8');

$id = ($argc > 1) ? $argv[1] : null;

$handler = new OmbQueueHandler($id);

$handler->runOnce();
