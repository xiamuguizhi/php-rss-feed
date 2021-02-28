<?php
// *** LICENSE ***
// oText is free software.
//
// By Fred Nassar (2006) and Timo Van Neerden (since 2010)
// See "LICENSE" file for info.
// *** LICENSE ***

// remove slashes if necessary
function clean_txt($text) {
	if (!get_magic_quotes_gpc()) {
		return trim($text);
	} else {
		return trim(stripslashes($text));
	}
}

function protect($text) {
	return htmlspecialchars(clean_txt($text));
}

function diacritique($texte) {
	$texte = strip_tags($texte);
	$texte = html_entity_decode($texte, ENT_QUOTES, 'UTF-8'); // &eacute => é ; é => é ; (uniformize)
	$texte = htmlentities($texte, ENT_QUOTES, 'UTF-8'); // é => &eacute;
	$texte = preg_replace('#&([a-z]{1,2})(acute|grave|circ|uml|cedil|tilde|ring|slash|caron|lig);#', '$1', $texte); // &eacute => e
	$texte = preg_replace('#(\t|\n|\r)#', ' ' , $texte); // \n, \r => spaces
	$texte = preg_replace('#&[\w\#]*;#U', '', $texte); // remove other entities like &quote, &nbsp.
	$texte = preg_replace('#[^\w -]#U', '', $texte); // keep only ciffers, letters, spaces, hyphens.
	$texte = strtolower($texte); // to lower case
	$texte = preg_replace('#[ ]+#', '-', $texte); // spaces => hyphens
	return $texte;
}

function rel2abs_admin($article) {
	// transforms SRCSET to SRC (too complicated to regex the hell out of that)
	$article = preg_replace('#(srcset=(\'|")?([^="\'\s]+))#i','src=$2$3$2 data-$0', $article);

	// if relative URI in path, make absolute paths (since /admin/ panel is 1 lv deeper) for href/src.
	$article = preg_replace('#(src|href)=\"(?!(/|[a-z]+://))#i','$1="../', $article);

	//debug(preg_last_error());
	return $article;
}



// for href (for security reasons, the data is cleaned before injecting in html)
function clean_href($matches) {
	return '<a href="'.addslashes($matches[2]).'">'.$matches[1].'</a>';
}


function date_formate($d, $format_force='') {
	$retour ='';
	$date= decode_id($d);
	$jour_l = jour_en_lettres($date['d'], $date['m'], $date['y']);
	$mois_l = mois_en_lettres($date['m']);
		$format = array (
			'0' => $date['d'].'/'.$date['m'].'/'.$date['y'],            // 14/01/1983
			'1' => $date['m'].'/'.$date['d'].'/'.$date['y'],            // 01/14/1983
			'2' => $date['d'].' '.$mois_l.' '.$date['y'],               // 14 janvier 1983
			'3' => $jour_l.' '.$date['d'].' '.$mois_l.' '.$date['y'],   // vendredi 14 janvier 1983
			'4' => $jour_l.' '.$date['d'].' '.$mois_l,                  // vendredi 14 janvier
			'5' => $mois_l.' '.$date['d'].', '.$date['y'],              // janvier 14, 1983
			'6' => $jour_l.', '.$mois_l.' '.$date['d'].', '.$date['y'], // vendredi, janvier 14, 1983
			'7' => $date['y'].'-'.$date['m'].'-'.$date['d'],            // 1983-01-14
			'8' => substr($jour_l,0,3).'. '.$date['d'].' '.$mois_l,     // ven. 14 janvier
		);

	if ($format_force != '') {
		$retour = $format[$format_force];
	} else {
		$retour = $format[$GLOBALS['format_date']];
	}
	return ucfirst($retour);
}

function heure_formate($d, $format_force='') {
	$date = decode_id($d);
	$timestamp = mktime($date['h'], $date['i'], $date['s'], $date['m'], $date['d'], $date['y']);
	$format = array (
		'0' => date('H\:i\:s',$timestamp),	// 23:56:04
		'1' => date('H\:i',$timestamp),		// 23:56
		'2' => date('h\:i\:s A',$timestamp),	// 11:56:04 PM
		'3' => date('h\:i A',$timestamp),		// 11:56 PM
	);

	if ($format_force != '') {
		$retour = $format[$format_force];
	} else {
		$retour = $format[$GLOBALS['format_heure']];
	}
	return $retour;
}

function date_formate_iso($d) {
	$date = decode_id($d);
	return date('c', mktime($date['h'], $date['i'], $date['s'], $date['m'], $date['d'], $date['y']));
}

// From a filesize (like "20M"), returns a size in bytes.
function return_bytes($val) {
	$val = trim($val);
	$prefix = strtolower($val[strlen($val)-1]);
	$val = (int)$val;
	switch($prefix) {
		case 'g': $val *= 1024;
		case 'm': $val *= 1024;
		case 'k': $val *= 1024;
	}
	return $val;
}

// from a filesize in bytes, returns computed size in kiB, MiB, GiB…
function taille_formate($taille) {
	$prefixe = array (
		'0' => $GLOBALS['lang']['byte_symbol'],   // 2^00 o
		'1' => 'ki'.$GLOBALS['lang']['byte_symbol'], // 2^10 o
		'2' => 'Mi'.$GLOBALS['lang']['byte_symbol'], // 2^20 o
		'3' => 'Gi'.$GLOBALS['lang']['byte_symbol'],
		'4' => 'Ti'.$GLOBALS['lang']['byte_symbol'],
	);
	$dix = 0;
	while ($taille / (pow(2, 10*$dix)) > 1024) {
		$dix++;
	}
	$taille = $taille / (pow(2, 10*$dix));
	if ($dix != 0) {
		$taille = sprintf("%.1f", $taille);
	}

	return $taille.' '.$prefixe[$dix];
}

function en_lettres($captchavalue) {
	return $GLOBALS['lang']['chiffres'][strval($captchavalue)];
}

function jour_en_lettres($jour, $mois, $annee, $abbrv=0) {
	$date = date('w', mktime(0, 0, 0, $mois, $jour, $annee));
	$date = ($date == '0') ? '7' : $date;
	if ($abbrv == 1) {
		return $GLOBALS['lang']['days_abbr'][$date-1];
	} else {
		return $GLOBALS['lang']['days_fullname'][$date-1];
	}
}

function mois_en_lettres($numero, $abbrv=0) {
	if ($abbrv == 1) {
		return $GLOBALS['lang']['months_abbr'][$numero-1];
	}
	else {
		return $GLOBALS['lang']['months_fullname'][$numero-1];
	}
}

function nombre_objets($nb, $type) {
	switch ($nb) {
		case 0 : return $GLOBALS['lang']['note_no_'.$type];
		case 1 : return $nb.' '.$GLOBALS['lang']['label_'.$type];
		default: return $nb.' '.$GLOBALS['lang']['label_'.$type.'s'];
	}
}

function str2($nb) {
	return str_pad($nb, 2, "0", STR_PAD_LEFT);
}
function str4($nb) {
	return str_pad($nb, 4, "0", STR_PAD_LEFT);
}
