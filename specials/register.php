<?php
/*--- jongeren inspiratiedag . nl --- jong mens (lode) november 2008 ---*/

/*--- process ---*/
/* process module
 * load page
*/

$log .= '[ special register ]';

$fieldnames = array(
	'fullname'					=> 'Volledige naam',
	'emailaddress'			=> 'E-mailadres',
	'nickname'					=> 'Nickname',
	'ticket'						=> 'dagdelen (dag of nacht)',
	'ticketextra'				=> 'dagdelen (dag of nacht)',
	'sharing'						=> 'Wat mis je nog in de dag',
	'comments'					=> 'Wil je nog wat kwijt',
);

// add log lines
function log_write($text) {
	$reglog = $GLOBALS['reglog'];
	@fwrite($reglog, $text);
}
function log_open() {
	$reglog = fopen('/domains/jongereninspiratiedag.nl/DEFAULT/specials/registerlog.txt', 'a+');
	return $reglog;
}
function log_close() {
	$reglog = $GLOBALS['reglog'];
	@fclose($reglog);
}
$reglog = log_open();
#error_reporting(E_ALL); // E_ALL ^ E_NOTICE
#ini_set('display_errors', 1);

function payment_costs($ticket) {
	$costs = 0;
	if ($ticket == 'dag') {
		$costs = '10,-';
	}
	elseif ($ticket == 'overnacht') {
		$costs = '7,50';
	}
	elseif ($ticket == 'dag_overnacht') {
		$costs = '12,50';
	}
	return $costs;
}
function set_register_state(&$user, &$register_state, $state) {
	$user->set_cookie('register', 'state', $state);
	$register_state = $state;
}
function registration_reload() {
	log_close();
	header('Location: '.BASE_URL.'index.php?p=Aanmelden');
	exit;
}

$page['no_cache'] = 1;

// login?
require_once('./user.php');
$user->check_login();

$register_state = $user->read_cookie('register', 'state');

// error message, user in use
if ($register_state == -1) {
	set_register_state($user, $register_state, 1);
}

if (empty($register_state)) {
	$register_state = 1;
	set_register_state($user, $register_state, 1);
}
if ($user->login) {
	// check registration
	$registration = mysql_select_row("SELECT * FROM `registers` WHERE `username` = '".mysql_rescue($user->name)."'");
}

/*--- form input! ---*/
if (!empty($_POST)) {
	
log_write(NLtxt.'-----'.NLtxt);
log_write('register post received from "'.$_POST['fullname'].'" ('.$_POST['emailaddress'].') coming for "'.$_POST['ticket'].'" and "'.$_POST['ticketextra'].'"'.NLtxt);
	
	/*--- check correct form input ---*/
	$fields = array(
		'fullname'					=> 'non-empty',
		'emailaddress'			=> 'non-empty',
		'nickname'					=> 'non-empty',
		'ticket'						=> 'dag|overnacht',
		'ticketextra'				=> '',
		'sharing'						=> '',
		'comments'					=> '',
	);
	$registration = array();
	$errors = array();
	foreach ($fields as $formkey => $requirement) {
		$value = $_POST[$formkey];
		
		// others may not be empty
		if ($requirement == 'non-empty' && empty($value)) {
			$errors[$formkey] = $requirement;
			unset($fields[$formkey]);
			continue;
		}
		
		// others need to choose
		elseif (strpos($requirement, '|')) {
			if (!isset($value)) {
				$errors[$formkey] = 'choose';
				continue;
			}
			else {
				$choice = array_flip(explode('|', $requirement));
				if (!isset($choice[$value])) {
					// just save to db. the change of getting this wrong is too small.
					// deal with it individually, mail the person if something went wrong
					$value = 'foute_invoer';
				}
			}
		}
		
		// all others are technically correct :)
		$registration[$formkey] = $value;
		unset($fields[$formkey]);
	}
	
$errors_text = ''; foreach($errors as $k => $v) { $errors_text .= $k.'='.$v.','; };
log_write('security check done, errors: "'.$errors_text.'"'.NLtxt);
	
	/*--- check nickname ---*/
	$check = $user->check_username($registration['nickname']);
	$loggedin = ($registration['nickname'] == $user->name) ? true : false;
	if ($check == true && $loggedin == false) {
		$errors['nickname'] = 'used';
	}
	
	/*--- adjust ticket ---*/
	if ($registration['ticket'] == 'dag' && !empty($registration['ticketextra'])) {
		$registration['ticket'] = 'dag_overnacht';
	}
	
	/*--- all ok? ---*/
	if (empty($errors)) {

		// save extra info
		$registration['ip'] = $_SERVER['REMOTE_ADDR'];
		$registration['time'] = date('c');
		
$reg_text = ''; foreach($registration as $k => $v) { $reg_text .= $k.'='.$v.','; };
log_write('start saving process, reg data: "'.$reg_text.'"'.NLtxt);

		// save info
		$insert = '';
		foreach ($registration as $key => $val) {
			// skip user/pass when logged in
			if (($user->login && $key == 'username'))
				continue;
			// change nickname => username
			if ($key == 'nickname')
				$key = 'username';
			// remove ticketextra
			if ($key == 'ticketextra')
				continue;
			// save fields for query
			$insert .= "`".$key."` = '".mysql_rescue($val)."',";
		}
		$insert = rtrim($insert, ',');
		#echo $insert;
		
log_write('ready for database write, data: "'.$insert.'"'.NLtxt);

		// already loggedin?
		if ($user->login) {

log_write('user logged in.. ');

			// update account
			$input = @mysql_update("UPDATE `registers` SET ".$insert." WHERE `username` = '".mysql_rescue($user->name)."'");

$mysql_err = @mysql_error();
log_write('row updated for "'.$user->name.'", errors: "'.$mysql_err.'"'.NLtxt);

		}
		else {

log_write('new user, not logged in.. ');

			// new registration
			$input = @mysql_insert("INSERT INTO `registers` SET ".$insert);

$mysql_err = @mysql_error();
log_write('row inserted for "'.$_POST['emailaddress'].'", errors: "'.$mysql_err.'"'.NLtxt);

			// also create a page
			$create_person = mysql_insert("INSERT INTO `pages` (`title`, `type`) VALUES('".mysql_rescue($registration['nickname'])."', 'person')");
			
			// login
			require_once('./user.php');
			$user->set_login($registration['nickname']);
			$user->login();
			
			// save joined info
			$user->move_joined();
		}
		
		// go next
		if ($input !== false) {
			$user->delete_cookie('login', 'show');
			set_register_state($user, $register_state, 2);
		}
		
log_write('send next screen to "'.$user->name.'" ('.$registration['emailaddress'].')'.NLtxt);

		// reload page to prevent double submitting
		registration_reload();
	}
	else {
		// show the error

log_write('going to show errors to "'.$_POST['emailaddress'].'"'.NLtxt);

		set_register_state($user, $register_state, 1);
	}
}

if ($register_state == 3) {
	$ticket_id = $registration['ticket_id'];
	$name = $registration['username'];
	$paid = $registration['payment_paid'];
	$ticket = $registration['ticket'];
	$costs = payment_costs($ticket);
	
log_write('user "'.$registration['emailaddress'].'" arrived at screen 3 (re-check one)'.NLtxt);

	$page['text'] = 'Hallo '.$name.NLtxt;
	
	if (!empty($ticket_id)) {
		if ($paid == 0) {
			$page['text'] .= 'We hebben nog geen betaling ontvangen.'.NLtxt
											.'Je kunt de betaling van &euro; <strong>'.$costs.'</strong> overmaken naar girorekening <strong>'.PAYMENT_BANK.'</strong> t.a.v. <strong>'.PAYMENT_NAME.'</strong>. Zet in de beschrijving <strong>'.PAYMENT_DESCRIPTION.'</strong> en je <strong>volledige naam</strong>. Als je betaling binnen is ontvang je van ons bericht.';
		}
		else {
			$page['text'] .= 'We hebben je betaling ontvangen!'.NLtxt
											.'Tot zaterdag de 3e!'.NLtxt;
		}
		$page['text'] .= NLtxt.'Wil je een extra persoon aanmelden? Ga naar een <a href="index.php?p=Heraanmelden">nieuw aanmeldformulier</a>.';
	}
	else {
		$page['text'] .= 'De status van je betaling is onbekend. Neem <a href="./index.php?p=Contact">contact</a> met ons op of meld je <a href="index.php?p=Heraanmelden">opnieuw aan</a>';
	}
	
	$page['text'] = paragraphs($page['text']);
}

if ($register_state == 2) {
	$payment_info = mysql_select_row("SELECT `ticket_id`, `ticket`"
																	." FROM `registers` WHERE `username` = '".mysql_rescue($user->name)."'");
	
log_write('user "'.$user->name.'" ('.$payment_info['ticket_id'].') arrived at screen 2 (confirm one)'.NLtxt);

	$page['text'] = 'Leuk dat je komt! Om je aan te melden kun je simpel de volgende stappen doorlopen.'.NLtxt
									.'<ol>'.NLtxt
									.'<li><s>Aanmeldformulier invullen.</s></li>'.NLtxt
									.'<li><strong>Bevestiging en betaling.</strong></li>'.NLtxt
									.'<li>.. Geen stap 3, je bent klaar!</li>'.NLtxt
									.'</ol>'.NLtxt;
	
	$page['text'] .= 'We hebben je aanmelding ontvangen, dankjewel!'.NLtxt;
	
	$strict = true;
	$costs = payment_costs($payment_info['ticket'], $strict);
	$page['text'] .= 'Je kunt de betaling van &euro; <strong>'.$costs.'</strong> overmaken naar girorekening <strong>'.PAYMENT_BANK.'</strong> t.a.v. <strong>'.PAYMENT_NAME.'</strong>. Zet in de beschrijving <strong>'.PAYMENT_DESCRIPTION.'</strong> en je <strong>volledige naam</strong>. Als je betaling binnen is ontvang je van ons bericht.';
	
	$page['text'] .= NLtxt.'Dat was het! Geef in het <a href="index.php?p=Programma">programma</a> wat je interessant vind als je dat nog niet gedaan had.'.NLtxt
									.'Tot zaterdag de 3e!';
	
	$page['text'] = paragraphs($page['text']);
	
	// go next
	set_register_state($user, $register_state, 3);
	
	# temporary delete the registration to test
	#mysql_delete("DELETE FROM `registers` WHERE `payment_code` = '".mysql_rescue($payment_info['payment_code'])."'");
}

if ($register_state == 1) {
	require_once('./template.php');
	$tpl = get_tpl('register');
	$translate = array('{TICKET_DAG}'=>payment_costs('dag'), '{TICKET_OVERNACHT}'=>payment_costs('overnacht'), '{TICKET_DAG_OVERNACHT}'=>payment_costs('dag_overnacht'));
	$tpl = translate_tpl($tpl, $translate);
	$page['text'] = 'Leuk dat je komt! Om je aan te melden kun je simpel de volgende stappen doorlopen.'.NLtxt
									.'<ol>'.NLtxt
									.'<li><strong>Aanmeldformulier invullen.</strong></li>'.NLtxt
									.'<li>Bevestiging en betaling.</li>'.NLtxt
									.'<li>.. Geen stap 3, je bent klaar!</li>'.NLtxt
									.'</ol>'.NLtxt;
	
	// error message
	if (!empty($errors)) {

$errors_text = ''; foreach($errors as $k => $v) { $errors_text .= $k.'='.$v.','; };
log_write('user sees errors: "'.$errors_text.'"'.NLtxt);

		$page['text'] .= '<div style="margin: 0 1em; padding: 0.5em; border: 2px solid #A00;"><span style="font-weight: bold; color: #A00;">Bijna goed</span>, nog even wat corrigeren:'.NLtxt
										.'<ul>'.NLtxt;
		foreach ($errors as $field => $error) {
			if ($error == 'non-empty') $msg = '<strong>'.$fieldnames[$field].'</strong> mag niet leeg zijn.';
			if ($error == 'used') $msg = '<strong>'.$fieldnames[$field].'</strong> bestaat al, kies een andere.';
			if ($error == 'choose') $msg = 'Maak een keuze voor een dagdeel bij het <strong>kaartje</strong>.';
			$page['text'] .= '<li>'.$msg.'</li>'.NLtxt;
		}
		$page['text'] .= '</ul></div>';
	}
	
	$page['text'] = paragraphs($page['text']);
	
	// fill in the form values based on previous input
	if ($user->login) {
		if (empty($_POST['nickname'])) $_POST['nickname'] = $user->read_cookie('login', 'user');
	}
	$find = array();
	$replace = array();
	foreach ($fieldnames as $fieldname => $v) {
		if ($fieldname == 'ticket' || $fieldname == 'ticketextra') {
			// reorder the tickets
			if ($fieldname == 'ticket' && isset($_POST[$fieldname])) {
				if ($_POST[$fieldname] == 'dag') {
					$find[] = '{'.strtoupper('checked_ticket_dag').'}';
					$replace[] = 'checked="checked"';
					$find[] = '{'.strtoupper('checked_ticket_overnacht').'}';
					$replace[] = '';
				}
				elseif ($_POST[$fieldname] == 'overnacht') {
					$find[] = '{'.strtoupper('checked_ticket_overnacht').'}';
					$replace[] = 'checked="checked"';
					$find[] = '{'.strtoupper('checked_ticket_dag').'}';
					$replace[] = '';
				}
			}
			elseif ($fieldname == 'ticket') {
				$find[] = '{'.strtoupper('checked_ticket_dag').'}';
				$replace[] = '';
				$find[] = '{'.strtoupper('checked_ticket_overnacht').'}';
				$replace[] = '';
			}
			elseif ($fieldname == 'ticketextra' && isset($_POST[$fieldname])) {
				$find[] = '{'.strtoupper('checked_ticket_dag_overnacht').'}';
				$replace[] = 'checked="checked"';
			}
			elseif ($fieldname == 'ticketextra') {
				$find[] = '{'.strtoupper('checked_ticket_dag_overnacht').'}';
				$replace[] = '';
			}
		}
		else {
			$find[] = '{'.strtoupper($fieldname).'}';
			$replace[] = isset($_POST[$fieldname]) ? $_POST[$fieldname] : '';
		}
	}
	$page['after_text'] = str_replace($find, $replace, $tpl);
}

log_close();

?>
