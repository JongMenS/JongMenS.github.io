<?php
/*--- jongeren inspiratiedag . nl --- jong mens (lode) november 2008 ---*/
# shutdown processes for archived website (marked with #archive#)

#archive#
# don't change the archied website
unset($_GET['a']);
$_POST = array();
unset($_COOKIE);
#archive#

/*--- process ---*/
/* safety first
 * get url params
*/

define('LOG', false);
define('BASE_URL', 'http://www.jongereninspiratiedag.nl/2008/');
define('NLtxt', "\n");
define('NLdbg', "<br />\r\n");
define('CACHE', false);
define('START', 'Welkom!');
define('TITLE', 'Jongeren Inspiratiedag 22/11 2008');
define('PAYMENT_BANK', '212269844');
define('PAYMENT_NAME', 'Partij voor Mens en Spirit te Arnhem');
define('PAYMENT_DESCRIPTION', 'jongereninspiratiedag');
define('COOKIE_DOMAIN', 'www.jongereninspiratiedag.nl'); // false for localhost
define('COOKIE_PATH', '/'); // '/' for localhost
define('COOKIE_SECURE', 0); // false for localhost
define('COOKIE_EXPIRE', time()+60*60*24*30);
global $log;
$log = '';

function _print_r($array) {
	$element = is_array($array) ? 'pre' : 'textarea';
	echo '<'.$element.' style="width: 900px; height: 450px;">';
	print_r($array);
	echo '</'.$element.'>';
}

// for the sake of safety: unset variables set by register_globals
if (ini_get('register_globals')) {
	if (!empty($_GET)) { foreach (array_keys($_GET) as $key) { unset($$key); } }
	if (!empty($_POST)) { foreach (array_keys($_POST) as $key) { unset($$key); } }
	if (!empty($_COOKIE)) { foreach (array_keys($_COOKIE) as $key) { unset($$key); } }
	unset($_REQUEST);
}

// for the sake of safety: turn off magic_quotes for runtime environments, like databases and files
if (function_exists('set_magic_quotes_runtime')) {
	@set_magic_quotes_runtime(0);
}

/*--- get url params ---*/
if (isset($_GET['p']))
	$request = preg_replace('/[^a-zA-Z0-9!() -]+/', '', $_GET['p']);
if (isset($_GET['a']))
	$action = preg_replace('/[^a-zA-Z]+/', '', $_GET['a']);
if (empty($request))
	$request = START;

foreach ($_POST as $k => $v) {
	$v = html_entity_decode($v, ENT_QUOTES, 'UTF-8');
	$v = rawurldecode($v);
	$v = trim($v);
	$v = str_replace("\r\n", NLtxt, $v); // windows
	$v = str_replace("\r", NLtxt, $v); // old macs
	if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())
		$v = stripcslashes($v);
	$_POST[$k] = $v;
}

?>
