<?php
// *** LICENSE ***
// oText is free software.
//
// By Fred Nassar (2006) and Timo Van Neerden (since 2010)
// See "LICENSE" file for info.
// *** LICENSE ***


function valider_form_preferences() {
	$erreurs = array();
	if (!( isset($_POST['token']) and check_token($_POST['token'])) ) {
		$erreurs[] = $GLOBALS['lang']['err_wrong_token'];
	}
	if (!strlen(trim($_POST['auteur']))) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_auteur'];
	}
	if ($GLOBALS['require_email'] == 1) {
		if (!preg_match('#^[\w.+~\'*-]+@[\w.-]+\.[a-zA-Z]{2,6}$#i', trim($_POST['email']))) {
			$erreurs[] = $GLOBALS['lang']['err_prefs_email'] ;
		}
	}
	if (!preg_match('#^(https?://).*/$#', $_POST['racine'])) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_racine_slash'];
	}
	if (!strlen(trim($_POST['identifiant']))) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_identifiant'];
	}
	if ($_POST['identifiant'] != USER_LOGIN and (!strlen($_POST['mdp']))) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_id_mdp'];
	}
	if (preg_match('#[=\'"\\\\|]#iu', $_POST['identifiant'])) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_id_syntaxe'];
	}
	if ( (!empty($_POST['mdp'])) and (!password_verify($_POST['mdp'], USER_PWHASH)) ) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_oldmdp'];
	}
	if ( (!empty($_POST['mdp'])) and (strlen($_POST['mdp_rep']) < '6') ) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_mdp'];
	}
	if ( (empty($_POST['mdp_rep'])) xor (empty($_POST['mdp'])) ) {
		$erreurs[] = $GLOBALS['lang']['err_prefs_newmdp'] ;
	}
	return $erreurs;
}

function valider_form_rss() {
	$erreurs = array();
	// check unique-token only on critical actions (session ID check is still there)
	//if (isset($_POST['add-feed']) or isset($_POST['delete_old'])) {
	//	if (!( isset($_POST['token']) and check_token($_POST['token'])) ) {
	//		$erreurs[] = $GLOBALS['lang']['err_wrong_token'];
	//	}
	//}
	// on feed add: URL needs to be valid, not empty, and must not already be in DB
	if (isset($_POST['add-feed'])) {
		if (empty($_POST['add-feed'])) {
			$erreurs[] = $GLOBALS['lang']['err_lien_vide'];
		}
		if (!preg_match('#^(https?://[\S]+)[a-z]{2,6}[-\#_\w?%*:.;=+\(\)/&~$,]*$#', trim($_POST['add-feed'])) ) {
			$erreurs[] = $GLOBALS['lang']['err_comm_webpage'];
		}
		if (array_key_exists($_POST['add-feed'], $GLOBALS['liste_flux'])) {
			$erreurs[] = $GLOBALS['lang']['err_feed_exists'];
		}
	}
	elseif (isset($_POST['mark-as-read'])) {
		if ( !(in_array($_POST['mark-as-read'], array('all', 'site', 'post', 'folder', 'postlist'))) ) {
			$erreurs[] = $GLOBALS['lang']['err_feed_wrong_param'];
		}
	}
	return $erreurs;
}

function valider_form_maintenance() {
	$erreurs = array();
	$token = isset($_POST['token']) ? $_POST['token'] : (isset($_GET['token']) ? $_GET['token'] : 'false');
	if (!check_token($token)) {
		$erreurs[] = $GLOBALS['lang']['err_wrong_token'];
	}
	return $erreurs;
}


