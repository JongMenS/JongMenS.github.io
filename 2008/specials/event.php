<?php
/*--- jongeren inspiratiedag . nl --- jong mens (lode) november 2008 ---*/

/*--- process ---*/
/* process module
 * load page
*/

require_once('./user.php');
$log .= '[ start of event.php ]';

// check user/pass form
if (isset($_POST) && !empty($_POST)) {
	$log .= '[ process user/pass (create user, login, setcookie, move joined) ]';
	// create login
	$result = $user->create_user($_POST['username'], $_POST['password']);
	if ($result == false) {
		$user->set_cookie('login', 'show', 2);
		header('Location: '.BASE_URL.'index.php?p='.$request);
		exit;
	}
	
	// login
	$user->login($_POST['username'], $_POST['password']);
	$user->set_login($_POST['username'], $_POST['password']);
	
	// save joins in db
	if ($user->read_cookie('joined'))
		$user->move_joined();
	
	// destroy user/pass form
	$user->set_cookie('login', 'show', false);
}
// continue

// inloggen
$user->check_login();

// aanmelden
if (isset($action) && $action == 'join') {
	$log .= '[ join '.$page['title'].' ]';
	$user->join($page['title']);
	$status = 'joined';
	$show_login = true;
}

// aanmelden
elseif (isset($action) && $action == 'unjoin') {
	$log .= '[ un-join '.$page['title'].' ]';
	$user->unjoin($page['title']);
	$status = 'join';
}

/*--- viewing ---*/
require_once('./template.php');

// check for login form
if ((isset($show_login) || $user->read_cookie('login', 'show')) && $user->login == false) {
	$log .= '[ show user/pass form ]';
	// check for error
	$error = ($user->read_cookie('login', 'show') == 2) ? 'Deze naam bestaat al, kies een andere.' : '';
	// show login form
	$user->set_cookie('login', 'show');
	$page['before_text'] = get_tpl('userpass-form');
	$translate = array('{title}'=>$page['title'], '{error}'=>$error);
	$page['before_text'] = translate_tpl($page['before_text'], $translate);
}

// show current join status
if (!isset($status))
	$status = ($user->joined($page['title'])) ? 'joined' : 'join';
require_once('./template.php');
$page['event_join'] = get_tpl('event-'.$status);
$log .= '[ join-status is '.$status.' ]';

// show others comming
require_once('./db.php');
$people = mysql_select_array("SELECT `username` FROM `joined` WHERE `event_title` = '".mysql_rescue($page['title'])."'");
if (!empty($people)) {
	$page['event_people'] = '';
	foreach ($people as $person) {
		$page['event_people'] .= '<a href="./index.php?p='.$person['username'].'" class="person">'.$person['username'].'</a>, ';
	}
	$page['event_people'] = trim($page['event_people'], ', ');
}
else {
	$page['event_people'] = 'nog niemand..';
}

// add list of events
require_once('./specials/events.php');

// don't use cache for event type pages
$page['no_cache'] = true;

$log .= '[ end of event.php ]';

?>
