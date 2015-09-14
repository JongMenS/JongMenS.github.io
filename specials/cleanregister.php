<?php
/*--- jongeren inspiratiedag . nl --- jong mens (lode) november 2008 ---*/

/*--- process ---*/
/* process module
 * load page
*/

$log .= '[ special clean-register ]';
$page['no_cache'] = 1;

// login?
require_once('./user.php');

// restart registration
$user->delete_cookie('register', 'state');
// logoff the user
$user->logout();

// reload to the new form
header('Location: '.BASE_URL.'index.php?p=Aanmelden');
exit;

?>