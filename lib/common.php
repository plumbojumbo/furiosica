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

if (!defined('LACONICA')) { exit(1); }

define('LACONICA_VERSION', '0.7.1');

define('AVATAR_PROFILE_SIZE', 96);
define('AVATAR_STREAM_SIZE', 48);
define('AVATAR_MINI_SIZE', 24);

define('NOTICES_PER_PAGE', 20);
define('PROFILES_PER_PAGE', 20);

define('FOREIGN_NOTICE_SEND', 1);
define('FOREIGN_NOTICE_RECV', 2);
define('FOREIGN_NOTICE_SEND_REPLY', 4);

define('FOREIGN_FRIEND_SEND', 1);
define('FOREIGN_FRIEND_RECV', 2);

define_syslog_variables();

# append our extlib dir as the last-resort place to find libs

set_include_path(get_include_path() . PATH_SEPARATOR . INSTALLDIR . '/extlib/');

# global configuration object

require_once('PEAR.php');
require_once('DB/DataObject.php');
require_once('DB/DataObject/Cast.php'); # for dates

require_once(INSTALLDIR.'/lib/language.php');

// try to figure out where we are

$_server = array_key_exists('SERVER_NAME', $_SERVER) ?
  strtolower($_SERVER['SERVER_NAME']) :
  null;
$_path = array_key_exists('SCRIPT_NAME', $_SERVER) ?
  substr($_SERVER['SCRIPT_NAME'], 1, strrpos($_SERVER['SCRIPT_NAME'], '/') - 1) :
  null;

// default configuration, overwritten in config.php

$config =
  array('site' =>
        array('name' => 'Just another Laconica microblog',
              'server' => $_server,
              'theme' => 'default',
              'path' => $_path,
              'logfile' => null,
              'fancy' => false,
              'locale_path' => INSTALLDIR.'/locale',
              'language' => 'en_US',
              'languages' => get_all_languages(),
              'email' =>
              array_key_exists('SERVER_ADMIN', $_SERVER) ? $_SERVER['SERVER_ADMIN'] : null,
              'broughtby' => null,
              'timezone' => 'UTC',
              'broughtbyurl' => null,
              'closed' => false,
              'inviteonly' => false,
              'private' => false),
        'syslog' =>
        array('appname' => 'laconica', # for syslog
              'priority' => 'debug'), # XXX: currently ignored
        'queue' =>
        array('enabled' => false),
        'license' =>
        array('url' => 'http://creativecommons.org/licenses/by/3.0/',
              'title' => 'Creative Commons Attribution 3.0',
              'image' => 'http://i.creativecommons.org/l/by/3.0/80x15.png'),
        'mail' =>
        array('backend' => 'mail',
              'params' => null),
        'nickname' =>
        array('blacklist' => array(),
              'featured' => array()),
        'profile' =>
        array('banned' => array()),
        'avatar' =>
        array('server' => null),
        'public' =>
        array('localonly' => true,
              'blacklist' => array()),
        'theme' =>
        array('server' => null),
        'throttle' =>
        array('enabled' => false, // whether to throttle edits; false by default
              'count' => 20, // number of allowed messages in timespan
              'timespan' => 600), // timespan for throttling
        'xmpp' =>
        array('enabled' => false,
              'server' => 'INVALID SERVER',
              'port' => 5222,
              'user' => 'update',
              'encryption' => true,
              'resource' => 'uniquename',
              'password' => 'blahblahblah',
              'host' => null, # only set if != server
              'debug' => false, # print extra debug info
              'public' => array()), # JIDs of users who want to receive the public stream
        'sphinx' =>
        array('enabled' => false,
              'server' => 'localhost',
              'port' => 3312),
        'tag' =>
        array('dropoff' => 864000.0),
        'popular' =>
        array('dropoff' => 864000.0),
        'daemon' =>
        array('piddir' => '/var/run',
              'user' => false,
              'group' => false),
        'integration' =>
        array('source' => 'Laconica'), # source attribute for Twitter
        'memcached' =>
        array('enabled' => false,
              'server' => 'localhost',
              'port' => 11211),
        'inboxes' =>
        array('enabled' => true), # on by default for new sites
		'sms' =>
		array('enabled' => 'false'),
        );

$config['db'] = &PEAR::getStaticProperty('DB_DataObject','options');

$config['db'] =
  array('database' => 'YOU HAVE TO SET THIS IN config.php',
        'schema_location' => INSTALLDIR . '/classes',
        'class_location' => INSTALLDIR . '/classes',
        'require_prefix' => 'classes/',
        'class_prefix' => '',
        'mirror' => null,
        'db_driver' => 'DB', # XXX: JanRain libs only work with DB
        'quote_identifiers' => false,
        'type' => 'mysql' );

if (function_exists('date_default_timezone_set')) {
    /* Work internally in UTC */
    date_default_timezone_set('UTC');
}

// From most general to most specific:
// server-wide, then vhost-wide, then for a path,
// finally for a dir (usually only need one of the last two).

$_config_files = array('/etc/laconica/laconica.php',
                  '/etc/laconica/'.$_server.'.php');

if (strlen($_path) > 0) {
    $_config_files[] = '/etc/laconica/'.$_server.'_'.$_path.'.php';
}

$_config_files[] = INSTALLDIR.'/config.php';

foreach ($_config_files as $_config_file) {
    if (file_exists($_config_file)) {
        include_once($_config_file);
    }
}

require_once('Validate.php');
require_once('markdown.php');

require_once(INSTALLDIR.'/lib/util.php');
require_once(INSTALLDIR.'/lib/action.php');
require_once(INSTALLDIR.'/lib/theme.php');
require_once(INSTALLDIR.'/lib/mail.php');
require_once(INSTALLDIR.'/lib/subs.php');
require_once(INSTALLDIR.'/lib/Shorturl_api.php');
require_once(INSTALLDIR.'/lib/twitter.php');

// XXX: other formats here

define('NICKNAME_FMT', VALIDATE_NUM.VALIDATE_ALPHA_LOWER);

function __autoload($class)
{
    if ($class == 'OAuthRequest') {
        require_once('OAuth.php');
    } else if (file_exists(INSTALLDIR.'/classes/' . $class . '.php')) {
        require_once(INSTALLDIR.'/classes/' . $class . '.php');
    } else if (file_exists(INSTALLDIR.'/lib/' . strtolower($class) . '.php')) {
        require_once(INSTALLDIR.'/lib/' . strtolower($class) . '.php');
    }
}
