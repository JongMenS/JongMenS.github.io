<?php
/*--- jongeren inspiratiedag . nl --- jong mens (lode) november 2008 ---*/

/*--- process ---*/
/* process module
 * load page
*/

require_once('./user.php');
$log .= '[ start of event.php ]';

// check user/pass form
if (isset($_POST) && !empty($_POST['username'])) {
	$log .= '[ process user (create user, login, setcookie, move joined) ]';
	// check username
	if ($user->check_username($_POST['username'])) {
		$user->set_cookie('login', 'show', 2);
		header('Location: '.BASE_URL.'index.php?p='.$request);
		exit;
	}
	// create new user
	$result = $user->create_user($_POST['username']);
	
	// login
	$user->login($_POST['username']);
	$user->set_login($_POST['username']);
	
	// save joins in db
	if ($user->read_cookie('joined'))
		$user->move_joined();
	
	// continue with register teaser
	$user->set_cookie('login', 'show', 3);
	$show_teaser = true;
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

// afmelden
elseif (isset($action) && $action == 'unjoin') {
	$log .= '[ un-join '.$page['title'].' ]';
	$user->unjoin($page['title']);
	$status = 'join';
}

/*--- viewing ---*/
require_once('./template.php');

// check for login form
if ((isset($show_login) || $user->read_cookie('login', 'show')) && $user->login == false) {
	$log .= '[ show user form ]';
	// check for error
	$error = ($user->read_cookie('login', 'show') == 2) ? 'Deze naam bestaat al, kies een andere.' : '';
	// show login form
	$user->set_cookie('login', 'show');
	$page['before_text'] = get_tpl('userpass-form');
	$translate = array('{title}'=>$page['title'], '{error}'=>$error);
	$page['before_text'] = translate_tpl($page['before_text'], $translate);
}
// check for register teaser
if ((isset($show_teaser) || $user->read_cookie('login', 'show') == 3) && $user->login == true) {
	$log .= '[ show register teaser ]';
	// show login form
	$page['before_text'] = get_tpl('register-teaser');
	$translate = array('{title}'=>$page['title']);
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
