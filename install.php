<?php
// *** LICENSE ***
// oText is free software.
//
// By Fred Nassar (2006) and Timo Van Neerden (since 2010)
// See "LICENSE" file for info.
// *** LICENSE ***

define('IS_IT_INSTALL', true);

// install or reinstall with same config ?
if ( file_exists('config/mysql.ini') and file_get_contents('config/mysql.ini') == '' ) {
	$step3 = TRUE;
} else {
	$step3 = FALSE;
}

// install is already done
if ( (file_exists('config/user.ini')) and (file_exists('config/prefs.php')) and $step3 === FALSE) {
	header('Location: auth.php');
	exit;
}

// some constants definition
define('DISPLAY_PHP_ERRORS', '-1');
$GLOBALS['fuseau_horaire'] = 'UTC';
$GLOBALS['racine'] = '';

if (isset($_GET['l'])) {
	$lang = $_GET['l'];
	if ($lang == 'cn' or $lang == 'en' or $lang == 'fr') {
		$GLOBALS['lang'] = $lang;
	} else {
		$GLOBALS['lang'] = 'cn';
	}

}

require_once 'inc/boot.php';
require_once 'inc/lang.php';

if (isset($_GET['s']) and is_numeric($_GET['s'])) {
	$GLOBALS['step'] = $_GET['s'];
} else {
	$GLOBALS['step'] = '1';
}

if ($GLOBALS['step'] == '1') {
	// LANGUE
	if (isset($_POST['verif_envoi_1'])) {
		if ($err_1 = valid_install_1()) {
				afficher_form_1($err_1);
		} else {
			redirection('install.php?s=2&l='.$_POST['langue']);
		}
	} else {
		afficher_form_1();
	}
}

elseif ($GLOBALS['step'] == '2') {
	// ID + MOT DE PASSE
	if (isset($_POST['verif_envoi_2'])) {
		if ($err_2 = valid_install_2()) {
				afficher_form_2($err_2);
		} else {
			creer_dossier(DIR_CONFIG, 1);
			creer_dossier(DIR_DATABASES, 1);
			creer_dossier(DIR_VAR, 1);
			fichier_user();
			import_ini_file(DIR_CONFIG.'user.ini');

			traiter_install_2();
			redirection('install.php?s=3&l='.$_POST['langue']);
		}
	} else {
		afficher_form_2();
	}

} elseif ($GLOBALS['step'] == '3') {
	// CHOIX DB
	if (isset($_POST['verif_envoi_3'])) {
		if ($err_3 = valid_install_3()) {
			afficher_form_3($err_3);
		} else {
			if (isset($_POST['sgdb']) and $_POST['sgdb'] == 'mysql') {
				fichier_mysql('mysql');
			}
			else {
				fichier_mysql('sqlite');
			}
			traiter_install_3();
			if (!file_exists('config/config-advanced.ini')) {
				fichier_adv_conf(); // is done right after DB init
			}
			redirection('auth.php');
		}
	} else {
		afficher_form_3();
	}
}

// affiche le form de choix de langue
function afficher_form_1($erreurs='') {
	afficher_html_head('Install', "install");
	echo '<div id="axe">'."\n";
	echo '<div id="pageauth">'."\n";
	echo '<h1>'.BLOGOTEXT_NAME.'</h1>'."\n";
	echo '<h1 id="step">Bienvenue / Welcome</h1>'."\n";
	echo erreurs($erreurs);

	$conferrors = array();
	// check PHP version
	if (version_compare(PHP_VERSION, MINIMAL_PHP_REQUIRED_VERSION, '<')) {
		$conferrors[] = "\t".'<li>Your PHP Version is '.PHP_VERSION.'. BlogoText requires '.MINIMAL_PHP_REQUIRED_VERSION.'.</li>'."\n";
	}
	// pdo_sqlite and pdo_mysql (minimum one is required)
	if (!extension_loaded('pdo_sqlite') and !extension_loaded('pdo_mysql') ) {
		$conferrors[] = "\t".'<li>Neither <b>pdo_sqlite</b> or <b>pdo_mysql</b> PHP-modules are loaded. Blogotext needs at least one (SQLite recommended).</li>'."\n";
	}
	// check directory readability
	if (!is_writable('../') ) {
		$conferrors[] = "\t".'<li>Blogotext has no write rights (chmod of home folder must be 644 at least).</li>'."\n";
	}
	if (!empty($conferrors)) {
		echo '<ol class="erreurs">'."\n";
		echo implode($conferrors, '');
		echo '</ol>'."\n";
		echo '<p classe="erreurs">Installation aborded.</p>'."\n";
		echo '</div>'."\n".'</div>'."\n".'</html>';
		die;
	}

	echo '<div id="install">'."\n";
	echo '<form method="post" action="install.php">'."\n";
	echo '<p>';
	echo form_select('langue', $GLOBALS['langs'], '', 'Choisissez votre langue / Choose your language: ');
	echo hidden_input('verif_envoi_1', '1');
	echo '</p>';
	echo '<button class="submit button-submit" type="submit" name="enregistrer">Ok</button>'."\n";
	echo '</form>'."\n";
	echo '<div>'."\n";
}

// form pour login + mdp + url
function afficher_form_2($erreurs='') {
	afficher_html_head('Install', "install");
	echo '<div id="axe">'."\n";
	echo '<div id="pageauth">'."\n";
	echo '<h1>'.BLOGOTEXT_NAME.'</h1>'."\n";
	echo '<h1 id="step">'.$GLOBALS['lang']['install'].'</h1>'."\n";
	echo erreurs($erreurs);
	echo '<div id="install">'."\n";
	echo '<form method="post" action="install.php?s='.$GLOBALS['step'].'&amp;l='.$GLOBALS['lang']['id'].'">'."\n".'<div id="erreurs_js" class="erreurs"></div>'."\n";
	echo '<p>';
	echo '<label for="identifiant">'.$GLOBALS['lang']['install_id'].' </label><input type="text" name="identifiant" id="identifiant" size="30" value="" class="text" placeholder="John Doe" required />'."\n";
	echo '</p>'."\n";
	echo '<p>';
	echo '<label for="mdp">'.$GLOBALS['lang']['install_mdp'].' </label><input type="password" name="mdp" id="mdp" size="30" value="" class="text" autocomplete="off" placeholder="••••••••••••" required /><button type="button" class="unveilmdp" onclick="return revealpass(\'mdp\');"></button>'."\n";
	echo '</p>'."\n";
	$lien = str_replace('install.php', '', 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']);
	echo '<p>';
	echo '<label for="racine">'.$GLOBALS['lang']['pref_racine'].' </label><input type="text" name="racine" id="racine" size="30" value="'.$lien.'" class="text"  placeholder="'.$lien.'" required />'."\n";
	echo '</p>'."\n";
	echo hidden_input('comm_defaut_status', '1');
	echo hidden_input('langue', $GLOBALS['lang']['id']);
	echo hidden_input('verif_envoi_2', '1');
	echo '<button class="submit button-submit" type="submit" name="enregistrer">Ok</button>'."\n";
	echo '</form>'."\n";
	echo '</div>'."\n";
}


// form choix SGBD
function afficher_form_3($erreurs='') {

	afficher_html_head('Install', "install");
	echo '<div id="axe">'."\n";
	echo '<div id="pageauth">'."\n";
	echo '<h1>'.BLOGOTEXT_NAME.'</h1>'."\n";
	echo '<h1 id="step">'.$GLOBALS['lang']['install'].'</h1>'."\n";
	echo erreurs($erreurs);
	echo '<div id="install">'."\n";
	echo '<form method="post" action="'.basename($_SERVER['SCRIPT_NAME']).'?'.$_SERVER['QUERY_STRING'].'">'."\n";
	echo '<p><label>'.$GLOBALS['lang']['install_choose_sgdb'].'</label>';
	echo '<select id="sgdb" name="sgdb" onchange="show_mysql_form()">'."\n";
	if (extension_loaded('pdo_sqlite')) {
		echo "\t".'<option value="sqlite">SQLite</option>'."\n";
	}
	if (extension_loaded('pdo_mysql') ) {
		echo "\t".'<option value="mysql">MySQL</option>'."\n";
	}
	echo '</select></p>'."\n";

	echo '<div id="mysql_vars" style="display:none;">'."\n";
	if (extension_loaded('pdo_mysql') ) {
		echo '<p><label for="mysql_user">MySQL User: </label>
					<input type="text" id="mysql_user" name="mysql_user" size="30" value="" class="text" placeholder="mysql_user" /></p>'."\n";
		echo '<p><label for="mysql_password">MySQL Password: </label>
					<input type="password" id="mysql_password" name="mysql_passwd" size="30" value="" class="text" placeholder="••••••••••••" autocomplete="off" /><button type="button" class="unveilmdp" onclick="return revealpass(\'mysql_password\');"></button></p>'."\n";
		echo '<p><label for="mysql_db">MySQL Database: </label>
					<input type="text" id="mysql_db" name="mysql_db" size="30" value="" class="text" placeholder="db_blogotext" /></p>'."\n";
		echo '<p><label for="mysql_host">MySQL Host: </label>
					<input type="text" id="mysql_host" name="mysql_host" size="30" value="" class="text" placeholder="localhost" /></p>'."\n";
	}
	echo '</div>'."\n";

	echo hidden_input('langue', $GLOBALS['lang']['id']);
	echo hidden_input('verif_envoi_3', '1');
	echo '<button class="submit button-submit" type="submit" name="enregistrer">Ok</button>'."\n";

	echo '</form>'."\n";
	echo '</div>'."\n";

}

function traiter_install_2() {
	$config_dir = '../config';
	if (!is_file($config_dir.'/prefs.php')) fichier_prefs();
	fichier_mysql(FALSE); // create an empty file
}

function traiter_install_3() {
	import_ini_file(DIR_CONFIG.'/'.'mysql.ini');
}


function valid_install_1() {
	$erreurs = array();
	if (!strlen(trim($_POST['langue']))) {
		$erreurs[] = 'Vous devez choisir une langue / You have to choose a language';
	}
	return $erreurs;
}

function valid_install_2() {
	$erreurs = array();
	if (!strlen(trim($_POST['identifiant']))) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_identifiant'];
	}
	if (preg_match('#[=\'"\\\\|]#iu', $_POST['identifiant'])) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_id_syntaxe'];
	}
	if ( (strlen($_POST['mdp']) < 6) ) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_mdp'] ;
	}
	if ( !strlen(trim($_POST['racine'])) or !preg_match('#^(https?://).*/$#', $_POST['racine']) ) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_racine'];
	} elseif (!preg_match('/^https?:\/\//', $_POST['racine'])) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_racine_http'];
	} elseif (!preg_match('/\/$/', $_POST['racine'])) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_racine_slash'];
	}
	return $erreurs;
}

function valid_install_3() {
	$erreurs = array();
	if ($_POST['sgdb'] == 'mysql') {

		if (!strlen(trim($_POST['mysql_user']))) {
			$erreurs[] = $GLOBALS['lang']['install_err_mysql_usr_empty'];
		}	
		if (!strlen(trim($_POST['mysql_passwd']))) {
			$erreurs[] = $GLOBALS['lang']['install_err_mysql_pss_empty'];
		}
		if (!strlen(trim($_POST['mysql_db']))) {
			$erreurs[] = $GLOBALS['lang']['install_err_mysql_dba_empty'];
		}	
		if (!strlen(trim($_POST['mysql_host']))) {
			$erreurs[] = $GLOBALS['lang']['install_err_mysql_hst_empty'];
		}

		if ( test_connection_mysql() == FALSE ) {
			$erreurs[] = $GLOBALS['lang']['install_err_mysql_connect'];
		}
	}
	return $erreurs;
}

function test_connection_mysql() {
	try {
		$options_pdo[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
		$db_handle = new PDO('mysql:host='.htmlentities($_POST['mysql_host'], ENT_QUOTES).';dbname='.htmlentities($_POST['mysql_db'], ENT_QUOTES), htmlentities($_POST['mysql_user'], ENT_QUOTES), htmlentities($_POST['mysql_passwd'], ENT_QUOTES), $options_pdo);
		return TRUE;
	} catch (Exception $e) {
		return FALSE;
	}
}


echo '<script>
function getSelectSgdb() {
	var selectElmt = document.getElementById("sgdb");
	if (!selectElmt) return false;
	return selectElmt.options[selectElmt.selectedIndex].value;
}
function show_mysql_form() {
	var selected = getSelectSgdb();
	if (selected) {
		if (selected == "mysql") {
			document.getElementById("mysql_vars").style.display = "block";
		} else {
			document.getElementById("mysql_vars").style.display = "none";
		}
	}
}
show_mysql_form(); // needed if MySQL is only option.

function revealpass(fieldId) {
	var field = document.getElementById(fieldId);
	if (field.type == "password") { field.type = "text"; }
	else { field.type = "password"; }
	field.focus();
	field.setSelectionRange(field.value.length, field.value.length);
	return false;
}

</script>'."\n";

footer();
