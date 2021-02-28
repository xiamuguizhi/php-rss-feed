<?php
// *** LICENSE ***
// oText is free software.
//
// By Fred Nassar (2006) and Timo Van Neerden (since 2010)
// See "LICENSE" file for info.
// *** LICENSE ***

function redirection($url) {
	header('Location: '.$url);
	exit;
}

/// DECODAGES //////////

function decode_id($id) {
	$retour = array(
		'y' => substr($id, 0, 4),
		'm' => substr($id, 4, 2),
		'd' => substr($id, 6, 2),
		'h' => substr($id, 8, 2),
		'i' => substr($id, 10, 2),
		's' => substr($id, 12, 2)
		);
	return $retour;
}

// Returns the URL of a blog post (with it’s id and title)

function get_blogpath($id, $titre) {

	if (strlen($id) === 14 and preg_match('#\d{14}#', $id)) {
		$date = decode_id($id);
		$path = '?d='.$date['y'].'/'.$date['m'].'/'.$date['d'].'/'.$date['h'].'/'.$date['i'].'/'.$date['s'].'-'.titre_url($titre);
	}
	else {
		$path = '?d='.$id.'--'.trim(diacritique($titre), '-');
	}
	return $path;
}

/*function get_blogpath($id, $titre) {

	if (strlen($id) === 14 and preg_match('#\d{14}#', $id)) {
		$date = decode_id($id);
		$path = $GLOBALS['racine'].'?d='.$date['y'].'/'.$date['m'].'/'.$date['d'].'/'.$date['h'].'/'.$date['i'].'/'.$date['s'].'-'.titre_url($titre);
	}
	else {
		$path = $GLOBALS['racine'].'?d='.$id.'--'.trim(diacritique($titre), '-');
	}
	return $path;
}*/
function article_anchor($id) {
	$anchor = 'id'.substr(md5($id), 0, 6);
	return $anchor;
}

function traiter_tags($tags) {
	$tags_array = explode(',' , trim($tags, ','));
	$tags_array = array_unique(array_map('trim', $tags_array));
	sort($tags_array);
	return implode(', ' , $tags_array);
}

// tri un tableau non pas comme "sort()" sur l’ID, mais selon une sous clé d’un tableau.
function tri_selon_sous_cle($table, $cle) {
	foreach ($table as $key => $item) {
		 $ss_cles[$key] = $item[$cle];
	}
	if (isset($ss_cles)) {
		array_multisort($ss_cles, SORT_DESC, $table);
	}
	return $table;
}

function get_ip() {
	return (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? htmlspecialchars($_SERVER['HTTP_X_FORWARDED_FOR']) : htmlspecialchars($_SERVER['REMOTE_ADDR']);
}

function check_session() {
	if (USE_IP_IN_SESSION == 1) {
		$ip = get_ip();
	} else {
		$ip = date('m');
	}
	ini_set('session.cookie_httponly', TRUE);
	session_set_cookie_params(365*24*60*60); // set new expiration time to the browser
	@session_start([
		'cookie_lifetime' => 86400*365,
		'cookie_secure'   => isHTTPS(),
		'cookie_httponly' => true,
	]);

	// generate hash for cookie
	$newUID = hash('sha256', USER_PWHASH.USER_LOGIN.md5($_SERVER['HTTP_USER_AGENT'].$ip));

	// check old cookie  with newUID
	if (isset($_COOKIE['BT-admin-stay-logged']) and $_COOKIE['BT-admin-stay-logged'] == $newUID) {
		$_SESSION['user_id'] = md5($newUID);
		session_regenerate_id(true);  // Send cookie
		// Still logged in, return
		return TRUE;
	} else {
		return FALSE;
	}

	// no "stay-logged" cookie : check session.
	if ( (!isset($_SESSION['user_id'])) or ($_SESSION['user_id'] != USER_LOGIN.hash('sha256', USER_PWHASH.$_SERVER['HTTP_USER_AGENT'].$ip)) ) {
		return FALSE;
	} else {
		return TRUE;
	}
}


// This will look if session expired and kill it, otherwise restore it
function operate_session() {
	if (check_session() === FALSE) { // session is not good
		fermer_session(); // destroy it
	} else {
		// Restore data lost if possible
		foreach($_SESSION as $key => $value){
			if(substr($key, 0, 8) === 'BT-post-'){
				$_POST[substr($key, 8)] = $value;
				unset($_SESSION[$key]);
			}
		}
		return TRUE;
	}
}

function fermer_session() {
	unset($_SESSION['nom_utilisateur'], $_SESSION['user_id'], $_SESSION['tokens']);
	setcookie('BT-admin-stay-logged', null, time()-3600, null, null, isHTTPS(), true);

	session_destroy(); // destroy session
	// Saving server-side the possible lost data (writing article for example)
	session_start([
		'cookie_secure'   => isHTTPS(),
		'cookie_httponly' => true,
	]);
	session_regenerate_id(true); // change l'ID au cas ou
	foreach($_POST as $key => $value){
		$_SESSION['BT-post-'.$key] = $value;
	}

	if (strrpos($_SERVER['REQUEST_URI'], '/logout.php') != strlen($_SERVER['REQUEST_URI']) - strlen('/logout.php')) {
		$_SESSION['BT-saved-url'] = $_SERVER['REQUEST_URI'];
	}
	redirection('auth.php');
	exit();
}

// Code from Shaarli. Generate an unique sess_id, usable only once.
function new_token() {
	$rnd = sha1(uniqid('',true).mt_rand());  // We generate a random string.
	$_SESSION['tokens'][$rnd]=1;  // Store it on the server side.
	return $rnd;
}

// Tells if a token is ok. Using this function will destroy the token.
// true=token is ok.
function check_token($token) {
	if (isset($_SESSION['tokens'][$token])) {
		unset($_SESSION['tokens'][$token]); // Token is used: destroy it.
		return true; // Token is ok.
	}
	return false; // Wrong token, or already used.
}


/**
 * remove params from url
 * 
 * @param string $param
 * @return string url
 */
function remove_url_param($param) {
	if (isset($_GET[$param])) {
		return str_replace(
					array(
						'&'.$param.'='.$_GET[$param],
						'?'.$param.'='.$_GET[$param],
						'?&amp;',
						'?&',
						'?',
					),
					array('','?','?','?',''),
					'?'.$_SERVER['QUERY_STRING']
				);
	} elseif (isset($_SERVER['QUERY_STRING'])) {
		return $_SERVER['QUERY_STRING'];
	}
	return '';
}


/* search query parsing (operators, exact matching, etc) */
function parse_search($q) {
	if (preg_match('#^\s?"[^"]*"\s?$#', $q)) { // exact match
		$array_q = array('%'.str_replace('"', '', $q).'%');
	}
	else { // multiple words matchs
		$array_q = explode(' ', trim($q));
		foreach ($array_q as $i => $entry) {
			$array_q[$i] = '%'.$entry.'%';
		}
	}
	// uniq + reindex
	return array_values(array_unique($array_q));
}

/* for testing/dev purpose: shows a variable. */
function debug($data) {
	header('Content-Type: text/html; charset=utf-8');
	echo '<pre>';
	print_r($data);
	die;
}

function debuglog($data) {
	file_put_contents('log.log', print_r($data, true));
}

/* remove the folders "." and ".." from the list of files returned by scandir(). */
function rm_dots_dir($array) {
	if (($key = array_search('..', $array)) !== FALSE) { unset($array[$key]); }
	if (($key = array_search('.', $array)) !== FALSE) { unset($array[$key]); }
	return ($array);
}

function isHTTPS() {
	return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
}