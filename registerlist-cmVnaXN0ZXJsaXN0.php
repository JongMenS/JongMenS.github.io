<?php
/*--- jongeren inspiratiedag . nl --- jong mens (lode) november 2008 ---*/

/*--- request all registered users ---*/
require_once('./db.php');
echo mysql_error();
$new = mysql_select_array("SELECT * FROM `registers` WHERE `payment_paid` = '0'");
echo mysql_error();
$paid = mysql_select_array("SELECT * FROM `registers` WHERE `payment_paid` = '1'");
echo mysql_error();

function display_time($time_string) {
	return date('l jS F', strtotime($time_string));
}

function list_regs($reg_array) {
	$list = '';
	foreach ($reg_array as $reg) {
		$ticket = '';
		switch ($reg['ticket']) {
			case 'dag': $ticket = 'dag (€ 10)'; break;
			case 'dag_overnacht': $ticket = 'dag met overnachting (€ 12,50)'; break;
			case 'overnacht': $ticket = 'overnachting (€ 7,50)'; break;
			default: $ticket = 'onbekend'; break;
		}
		$extra = (!empty($reg['sharing']) || !empty($reg['comments'])) ? 'ja' : '-';
		
		$list .= '<tr>';
		$list .= '<td>'.$reg['fullname'].'</td>';
		$list .= '<td>'.$ticket.'</td>';
		$list .= '<td>'.$extra.'</td>';
		$list .= '<td>'.display_time($reg['time']).'</td>';
		$list .= '</tr>'."\r\n";
	}
	return $list;
}

$list_new = list_regs($new);
$list_paid = list_regs($paid);

?>
<!DOCTYPE html>
<html>
<head>
	<title>JoIn 2009 registratie lijst</title>
	<style>
		table { border: 1px solid #AAA; }
		table#betaald th { background-color: #AFA; }
		table#nieuw th { background-color: #FAA; }
		tr.handmatig td, span.handmatig { background-color: #FFA; }
		th { text-align: left; }
		td { width: 200px; }
	</style>
</head>
<body>
	<h1>JoIn 2009 registratie lijst - voor intern gebruik</h1>
	<h2>Betaalde aanmeldingen (<?php echo count($paid); ?>)</h2>
	<table id="betaald">
		<tr><th>Naam</th><th>Kaartje</th><th>Extra</th><th>Tijd van aanmelding</th></tr>
		<?php echo $list_paid; ?>
	</table>
	<h2>Nieuwe aanmeldingen (<?php echo (count($new)+1); ?>)</h2>
	<table id="nieuw">
		<tr><th>Naam</th><th>Kaartje</th><th>Extra</th><th>Tijd van aanmelding</th></tr>
		<?php echo $list_new; ?>
		<tr class="handmatig">
			<td>Mirjam Maas</td>
			<td>onbekend</td>
			<td>-</td>
			<td><?php echo display_time('Tue, Aug 11, 2009 9:50 PM'); ?></td>
		</tr>
	</table>
	<h2>Legenda</h2>
	<ul>
		<li>Extra betekent dat de persoon extra informatie heeft gegeven over wat ie zelf wil doen of overig commentaar heeft. Vraag Lode voor de info.</li>
		<li>Aanmeldingen die <span class="handmatig">geel gemarkeerd</span> zijn, zijn niet via de site gegaan maar bijvoorbeeld per e-mail.</li>
	</ul>
</body>
</html>
