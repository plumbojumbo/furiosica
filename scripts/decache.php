#!/usr/bin/env php
<?php
/*
 * Laconica - a distributed open-source microblogging tool
 * Copyright (C) 2009, Control Yourself, Inc.
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
    exit(1);
}

ini_set("max_execution_time", "0");
ini_set("max_input_time", "0");
set_time_limit(0);
mb_internal_encoding('UTF-8');

define('INSTALLDIR', realpath(dirname(__FILE__) . '/..'));
define('LACONICA', true);

require_once(INSTALLDIR . '/lib/common.php');

if ($argc < 3 || $argc > 4) {
    print "USAGE: decache.php <table> <id> [<column>]\n";
    print "Clears the cache for the object in table <table> with id <id>.\n\n";
    print "If <column> is specified, use that instead of 'id'\n";
    exit(1);
}

$table = $argv[1];
$id = $argv[2];
if ($argc > 3) {
    $column = $argv[3];
} else {
    $colum = 'id';
}

$object = Memcached_DataObject::staticGet($table, $column, $id);

if (!$object) {
    print "No such '$table' with $column = '$id'.\n";
    exit(1);
}

$result = $object->decache();
