<?php
/*--- jongeren inspiratiedag . nl --- jong mens (lode) november 2008 ---*/

/*--- process ---*/
/* process module
 * load page
*/

function paragraphs($text) {
	// repair newlines
	$text = str_replace("\r\n", NLtxt, $text); // windows
	$text = str_replace("\r", NLtxt, $text); // old macs
	
	// make paragraphs
	$text = str_replace(NLtxt, '</p>'.NLtxt.'<p>', $text);
	
	// repair wrong paragraphs
	$search = array('<p></p>', '<p><ul></p>', '<p><ol></p>', '<p><li>', '</li></p>', '<p></ul></p>', '<p></ol></p>');
	$replace = array('', '<ul>', '<ol>', '<li>', '</li>', '</ul>', '</ol>');
	$text = str_replace($search, $replace, $text);
	return $text;
}

if (CACHE && !isset($action) && file_exists('./pages/'.$request.'.html')) {
	$html = file_get_contents('./pages/'.$request.'.html');
}
else {
	
	require_once('./db.php');
	
	if (DB == false) {
		// sorry, no db and no cache
		$log .= '[ no db ]';
		require_once('./sorry.php');
	}
	else {
		$page = mysql_select_row("SELECT * FROM `pages` WHERE `title` = '".mysql_rescue($request)."'");
		$page['text'] = paragraphs($page['text']);
		if (empty($page)) {
			// sorry, not found
			require_once('./sorry.php');
		}
		elseif ($page['type'] == 'special') {
			if (file_exists('./specials/'.$page['author'].'.php')) {
				require_once('./specials/'.$page['author'].'.php');
			}
		}
		elseif ($page['type'] == 'event') {
			require_once('./specials/event.php');
		}
		elseif ($page['type'] == 'person') {
			require_once('./specials/person.php');
		}
		elseif ($page['title'] == 'Welkom!') {
			// add list of events
			require_once('./specials/events.php');
			// don't use cache for event type pages
			$page['no_cache'] = true;
		}
	}
	
}

?>
