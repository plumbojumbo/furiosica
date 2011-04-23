<?php
define('LACONICA', true);
define('INSTALLDIR', dirname(__FILE__) . '/..');
require_once INSTALLDIR . '/lib/common.php';
require_once INSTALLDIR . '/lib/util.php';
common_init_locale(common_config('site', 'language'));
$nick = trim($_REQUEST['nick']);
$nick = common_parse_nickname($nick);
header('HTTP/1.1 200 OK');
header('Content-type: text/plain');
echo $nick;
?>