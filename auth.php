<?php
// *** LICENSE ***
// oText is free software.
//
// By Fred Nassar (2006) and Timo Van Neerden (since 2010)
// See "LICENSE" file for info.
// *** LICENSE ***


require_once 'inc/boot.php';



// Acces LOG
if (isset($_POST['nom_utilisateur'])) {
	creer_dossier(DIR_LOG, 1);
	// IP
	$ip = htmlspecialchars($_SERVER["REMOTE_ADDR"]);
	// Proxy IPs, if exists.
	$ip .= (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? '_'.htmlspecialchars($_SERVER['HTTP_X_FORWARDED_FOR']) : '';
	$data = '<?php // '.date('r').' - '.$ip.' - '.((valider_form()===TRUE) ? 'login succes' : 'login failed for '. '“'.htmlspecialchars($_POST['nom_utilisateur']).'”') ."\n";
	file_put_contents(DIR_LOG.'xauthlog.php', $data, FILE_APPEND);
}


if (check_session() === TRUE) { // return to index if session is already open.
	header('Location: index.php');
	exit;
}

// Auth checking :
if (isset($_POST['_verif_envoi']) and valider_form() === TRUE) { // OK : getting in.
	if (USE_IP_IN_SESSION == 1) {
		$ip = get_ip();
	} else {
		$ip = date('m'); // make session expire at least once a month, disregarding IP changes.
	}
	$_SESSION['user_id'] = $_POST['nom_utilisateur'].hash('sha256', $_POST['mot_de_passe'].$_SERVER['HTTP_USER_AGENT'].$ip); // set special hash
	usleep(100000); // 100ms sleep to avoid bruteforce

	if (!empty($_POST['stay_logged'])) { // if user wants to stay logged
		$user_id = hash('sha256', USER_PWHASH.USER_LOGIN.md5($_SERVER['HTTP_USER_AGENT'].$ip));
		setcookie('BT-admin-stay-logged', $user_id, time()+365*24*60*60, null, null, isHTTPS(), true);
		session_set_cookie_params(365*24*60*60); // set expiration time to the browser
	} else {
		$_SESSION['stay_logged_mode'] = 0;
		session_regenerate_id(true);
	}

	// Handle saved data/URL redirect if POST request made
	$location = 'index.php';
	if(isset($_SESSION['BT-saved-url'])){
		$location = $_SESSION['BT-saved-url'];
		unset($_SESSION['BT-saved-url']);
	}
	if(isset($_SESSION['BT-post-token'])){
		// The login was right, so we give a token because the previous one expired with the session
		$_SESSION['BT-post-token'] = new_token();
	}

	header('Location: '.$location);

} else { // On sort…
		// …et affiche la page d'auth
		afficher_html_head('Identification', "auth");
		echo '<div id="axe">'."\n";
		echo '<div id="pageauth">'."\n";
		echo '<h1>'.BLOGOTEXT_NAME.'</h1>'."\n";
		echo '<div id="auth">'."\n";
		echo '<form method="post" action="auth.php">'."\n";
		echo '<p><label for="user">'.ucfirst($GLOBALS['lang']['label_dp_identifiant']).'</label><input class="text" type="text"  autocomplete="off" id="user" name="nom_utilisateur" placeholder="John Doe" value="" /></p>'."\n";
		echo '<p><label for="password">'.ucfirst($GLOBALS['lang']['label_dp_motdepasse']).'</label><input class="text" id="password" type="password" placeholder="••••••••••••" name="mot_de_passe" value="" /></p>'."\n";
		echo '<p><input type="checkbox" id="stay_logged" name="stay_logged" checked class="checkbox" /><label for="stay_logged">'.$GLOBALS['lang']['label_stay_logged'].'</label></p>'."\n";
		echo '<button class="submit button-submit" type="submit" name="submit">'.$GLOBALS['lang']['connexion'].'</button>'."\n";
		echo '<input type="hidden" name="_verif_envoi" value="1" />'."\n";
		echo '</form>'."\n";
		echo '</div>'."\n";
}

function valider_form() {
	if (!password_verify($_POST['mot_de_passe'], USER_PWHASH) or $_POST['nom_utilisateur'] != USER_LOGIN) {
		return FALSE;
	}
	return TRUE;
}

echo "\n".'<script src="style/scripts/javascript.js"></script>'."\n";
footer();

