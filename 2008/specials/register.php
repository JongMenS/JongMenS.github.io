<?php
/*--- jongeren inspiratiedag . nl --- jong mens (lode) november 2008 ---*/

/*--- process ---*/
/* process module
 * load page
*/

$log .= '[ special register ]';

$fieldnames = array(
	'realname'					=> 'Echte naam',
	'emailaddress'			=> 'E-mailadres',
	'username'					=> 'Nickname',
	'password'					=> 'Wachtwoord',
	'ticket'						=> 'dagdelen',
	'payment_method'		=> 'manier van betalen',
	'payment_exchange'	=> 'Ruilmiddel',
	'age'								=> 'Leeftijd',
	'whats_missing'			=> 'Wat mis je nog in de dag',
	'can_you_help'			=> 'Wil jij dat verzorgen',
	'comments'					=> 'Wil je nog wat kwijt',
);

function generate_payment_code() {
	$payment_code = '';
	
	for ($i=0; $i<4; $i++)
		$payment_code .= mt_rand(1000, 9999).'-';
	
	$payment_code = trim($payment_code, '-');
	return $payment_code;
}
function payment_costs($ticket) {
	$costs = 0;
	if ($ticket == 'dag')
		$costs = 10;
	elseif ($ticket == 'overnacht')
		$costs = 15;
	elseif ($ticket == 'dag_overnacht')
		$costs = 25;
	return $costs;
}
function set_register_state(&$user, &$register_state, $state) {
	$user->set_cookie('register', 'state', $state);
	$register_state = $state;
}
function registration_reload() {
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
	/*--- check correct form input ---*/
	$fields = array(
		'realname'					=> 'non-empty',
		'emailaddress'			=> 'non-empty',
		'username'					=> 'non-empty',
		'password'					=> 'non-empty',
		'ticket'						=> 'dag|overnacht|dag_overnacht',
		'payment_method'		=> 'overmaken|contant|ruilhandel',
		'payment_exchange'	=> '',
		'age'								=> '',
		'whats_missing'			=> '',
		'can_you_help'			=> '',
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
				if (!isset($choice[$value]))
					// just save to db. the change of getting this wrong is too small.
					// deal with it individually, mail the person if something went wrong
					$value = 'foute_invoer';
			}
		}
		
		// all others are technically correct :)
		$registration[$formkey] = $value;
		unset($fields[$formkey]);
	}
	
	/*--- check username ---*/
	$check = $user->check_username($registration['username']);
	$loggedin = ($registration['username'] == $user->name) ? true : false;
	if ($check == true && $loggedin == false) {
		$errors['username'] = 'used';
	}
	
	/*--- all ok? ---*/
	if (empty($errors)) {
		// save extra info
		$registration['ip'] = $_SERVER['REMOTE_ADDR'];
		$registration['time'] = date('c');
		
		// generate code
		do {
			$payment_code = generate_payment_code();
		} while (mysql_select_string("SELECT `payment_code` FROM `registers` WHERE `payment_code` = '".mysql_rescue($payment_code)."'"));
		
		// save info
		$insert = '';
		foreach ($registration as $key => $val) {
			// skip user/pass when logged in
			if (($user->login && $key == 'username') || ($user->login && $key == 'password'))
				continue;
			// save fields for query
			$insert .= "`".$key."` = '".mysql_rescue($val)."',";
		}
		$insert .= "`payment_code` = '".$payment_code."'";
		
		// already loggedin?
		if ($user->login) {
			// update account
			$input = mysql_update("UPDATE `registers` SET ".$insert." WHERE `username` = '".mysql_rescue($user->name)."'");
		}
		else {
			// new registration
			$input = mysql_insert("INSERT INTO `registers` SET ".$insert."");
			
			// also create a page
			$create_person = mysql_insert("INSERT INTO `pages` (`title`, `type`) VALUES('".mysql_rescue($registration['username'])."', 'person')");
			
			// login
			require_once('./user.php');
			$user->set_login($registration['username'], $registration['password']);
			$user->login();
			
			// save joined info
			$user->move_joined();
		}
		
		// go next
		if ($input !== false) {
			set_register_state($user, $register_state, 2);
		}
		
		// reload page to prevent double submitting
		registration_reload();
	}
	else {
		// show the error
		set_register_state($user, $register_state, 1);
	}
}

if ($register_state == 3) {
	$payment_code = $registration['payment_code'];
	$name = $registration['username'];
	$method = $registration['payment_method'];
	$paid = $registration['payment_paid'];
	$ticket = $registration['ticket'];
	$costs = payment_costs($ticket);
	
	$page['text'] = 'Hallo '.$name.NLtxt;
	
	if ($method == 'overmaken') {
		if ($paid == 0) {
			$page['text'] .= 'We hebben nog geen betaling ontvangen.'.NLtxt
											.'Je kunt de betaling van &euro; <strong>'.$costs.',-</strong> overmaken naar girorekening <strong>'.PAYMENT_BANK.'</strong> t.a.v. <strong>'.PAYMENT_NAME.'</strong>. Zet in de beschrijving <strong>'.PAYMENT_DESCRIPTION.'</strong> en je betalingscode <strong>'.$payment_code.'</strong>. Als je betaling binnen is ontvang je van ons bericht.';
		}
		else {
			$page['text'] .= 'We hebben je betaling ontvangen!'.NLtxt
											.'Tot zaterdag de 22e!'.NLtxt;
		}
	}
	elseif ($method == 'contant') {
		$page['text'] .= 'Je hebt aangegeven contant te betalen.'.NLtxt
										.'Als je alsnog wil overmaken kun je de betaling van &euro; <strong>'.$costs.',-</strong> overmaken naar girorekening <strong>'.PAYMENT_BANK.'</strong> t.a.v. <strong>'.PAYMENT_NAME.'</strong>. Zet in de beschrijving <strong>'.PAYMENT_DESCRIPTION.'</strong> en je betalingscode <strong>'.$payment_code.'</strong>. Als je betaling binnen is ontvang je van ons bericht.';
	}
	elseif ($method == 'ruilhandel') {
		$page['text'] .= 'Je hebt aangegeven via ruilhandel te betalen.'.NLtxt
										.'Als je alsnog wil overmaken kun je de betaling van &euro; <strong>'.$costs.',-</strong> overmaken naar girorekening <strong>'.PAYMENT_BANK.'</strong> t.a.v. <strong>'.PAYMENT_NAME.'</strong>. Zet in de beschrijving <strong>'.PAYMENT_DESCRIPTION.'</strong> en je betalingscode <strong>'.$payment_code.'</strong>. Als je betaling binnen is ontvang je van ons bericht.';
	}
	else {
		$page['text'] .= 'De status van je betaling is onbekend. Neem <a href="./index.php?p=Contact">contact</a> met ons op.';
	}
	
	$page['text'] = paragraphs($page['text']);
}

if ($register_state == 2) {
	$payment_info = mysql_select_row("SELECT `payment_code`, `second_code`, `ticket`, `payment_method`, `payment_exchange`"
																	." FROM `registers` WHERE `username` = '".mysql_rescue($user->name)."'");
	
	$page['text'] = 'Leuk dat je komt! Om je aan te melden kun je simpel de volgende stappen doorlopen.'.NLtxt
									.'<ol>'.NLtxt
									.'<li><s>Aanmeldformulier invullen.</s></li>'.NLtxt
									.'<li><strong>Bevestiging en eventuele betaling.</strong></li>'.NLtxt
									.'<li>.. Geen stap 3, je bent klaar!</li>'.NLtxt
									.'</ol>'.NLtxt;
	
	$page['text'] .= 'We hebben je aanmelding ontvangen, dankjewel! Je betalingscode is <strong>'.$payment_info['payment_code'].'</strong>.'.NLtxt;
	
	$costs = payment_costs($payment_info['ticket']);
	if ($payment_info['payment_method'] == 'overmaken') {
		$page['text'] .= 'Je kunt de betaling van &euro; <strong>'.$costs.',-</strong> overmaken naar girorekening <strong>'.PAYMENT_BANK.'</strong> t.a.v. <strong>'.PAYMENT_NAME.'</strong>. Zet in de beschrijving <strong>'.PAYMENT_DESCRIPTION.'</strong> en je betalingscode <strong>'.$payment_info['payment_code'].'</strong>. Als je betaling binnen is ontvang je van ons bericht.';
	}
	elseif ($payment_info['payment_method'] == 'contant') {
		$page['text'] .= 'Zorg dat je op de dag &euro; <strong>'.$costs.',-</strong> kunt betalen. Neem je betalingscode <strong>'.$payment_info['payment_code'].'</strong> mee, dan weten we voor wie je betaald.';
	}
	elseif ($payment_info['payment_method'] == 'ruilhandel') {
		$page['text'] .= 'Zorg dat je op de dag je ruilmiddel meeneemt. Neem je betalingscode <strong>'.$payment_info['payment_code'].'</strong> mee, dan weten we voor wie je betaald.';
	}
	
	$page['text'] .= NLtxt.'Dat was het! Geef in het <a href="index.php?p=Programma">programma</a> wat je interessant vind als je dat nog niet gedaan had.'.NLtxt
									.'Tot zaterdag de 22e!';
	
	$page['text'] = paragraphs($page['text']);
	
	// go next
	set_register_state($user, $register_state, 3);
	
	# temporary delete the registration to test
	#mysql_delete("DELETE FROM `registers` WHERE `payment_code` = '".mysql_rescue($payment_info['payment_code'])."'");
}

if ($register_state == 1) {
	require_once('./template.php');
	$tpl = get_tpl('register');
	$translate = array('{TICKET_DAG}'=>payment_costs('dag'), '{TICKET_OVERNACHT}'=>payment_costs('overnacht'));
	$tpl = translate_tpl($tpl, $translate);
	$page['text'] = 'Leuk dat je komt! Om je aan te melden kun je simpel de volgende stappen doorlopen.'.NLtxt
									.'<ol>'.NLtxt
									.'<li><strong>Aanmeldformulier invullen.</strong></li>'.NLtxt
									.'<li>Bevestiging en eventuele betaling.</li>'.NLtxt
									.'<li>.. Geen stap 3, je bent klaar!</li>'.NLtxt
									.'</ol>'.NLtxt;
	
	// error message
	if (!empty($errors)) {
		$page['text'] .= 'Bijna goed, nog even wat corrigeren:'.NLtxt
										.'<ul>'.NLtxt;
		foreach ($errors as $field => $error) {
			if ($error == 'non-empty') $msg = '<em>'.$fieldnames[$field].'</em> mag niet leeg zijn.';
			if ($error == 'used') $msg = '<em>'.$fieldnames[$field].'</em> bestaat al, kies een andere.';
			if ($error == 'choose') $msg = 'Maak een keuze bij de <em>'.$fieldnames[$field].'</em>.';
			$page['text'] .= '<li>'.$msg.'</li>'.NLtxt;
		}
		$page['text'] .= '</ul>';
	}
	
	$page['text'] = paragraphs($page['text']);
	
	// fill in the form values based on previous input
	if ($user->login) {
		if (empty($_POST['username'])) $_POST['username'] = $user->read_cookie('login', 'user');
		if (empty($_POST['password'])) $_POST['password'] = $user->read_cookie('login', 'pass');
	}
	$find = array();
	$replace = array();
	foreach ($fieldnames as $fieldname => $v) {
		$find[] = '{'.strtoupper($fieldname).'}';
		$replace[] = isset($_POST[$fieldname]) ? $_POST[$fieldname] : '';
	}
	$page['after_text'] = str_replace($find, $replace, $tpl);
}

?>
