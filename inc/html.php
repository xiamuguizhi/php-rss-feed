<?php
// *** LICENSE ***
// oText is free software.
//
// By Fred Nassar (2006) and Timo Van Neerden (since 2010)
// See "LICENSE" file for info.
// *** LICENSE ***


function afficher_html_head($titre, $page_css_class) {
	$html = '<!DOCTYPE html>'."\n";
	$html .= '<html lang="'.$GLOBALS['lang']['id'].'">'."\n";
	$html .= '<head>'."\n";
	$html .= "\t".'<meta charset="UTF-8" />'."\n";
	$html .= "\t".'<title>'.$titre.' | '.BLOGOTEXT_NAME.'</title>'."\n";
	$html .= "\t".'<meta name="viewport" content="initial-scale=1.0, user-scalable=yes" />'."\n";
	$html .= "\t".'<link type="text/css" rel="stylesheet" href="style/styles/style.css.php" />'."\n";
	$html .= "\t".'<link rel="manifest" href="manifest.json" />'."\n";
	$html .= '</head>'."\n";
	$html .= '<body id="body" class="'.$page_css_class.'">'."\n";
	echo $html;
}

function footer($begin_time='') {
	$msg = '';
	if ($begin_time != '') {
		$dt = round((microtime(TRUE) - $begin_time),6);
		$msg = ' - '.$GLOBALS['lang']['rendered'].' '.$dt.' s '.$GLOBALS['lang']['using'].' '.DBMS;
	}

	$html = '</div>'."\n";
	$html .= '</div>'."\n";
	//$html .= '<footer id="footer"><a href="'.BLOGOTEXT_SITE.'">'.BLOGOTEXT_NAME.' '.BLOGOTEXT_VERSION.'</a>'.$msg.'</footer>'."\n";
	$html .= '</body>'."\n";
	$html .= '</html>';
	echo $html;
}

/// menu haut panneau admin /////////
function afficher_topnav($titre, $html_sub_menu) {
	$tab = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_BASENAME);
	if (strlen($titre) == 0) $titre = BLOGOTEXT_NAME;

	$html = '<header id="header">'."\n";

	$html .= "\t".'<div id="top">'."\n";

	// page title
	$html .=  "\t\t".'<h1 id="titre-page"><a href="'.$tab.'">'.$titre.'</a></h1>'."\n";

	// search form
	if (in_array($tab, array('feed.php'))) {
		$html .= moteur_recherche();
	}

	// app navs
	$html .= "\t\t".'<div id="nav">'."\n";
	$html .= "\t\t\t".'<ul>'."\n";
	$html .= "\t\t\t\t".'<li><a href="index.php" id="lien-index">'.ucfirst($GLOBALS['lang']['label_feeds']).'</a></li>'."\n";
	$html .= "\t\t\t".'</ul>'."\n";
	$html .= "\t\t".'</div>'."\n";

	// notif icons
	$html .= get_notifications();

	// account nav
	$html .= "\t\t".'<div id="nav-acc">'."\n";
	$html .= "\t\t\t".'<ul>'."\n";
	$html .= "\t\t\t\t".'<li><a href="preferences.php" id="lien-preferences">'.$GLOBALS['lang']['preferences'].'</a></li>'."\n";
	$html .= "\t\t\t\t".'<li><a href="logout.php" id="lien-deconnexion">'.$GLOBALS['lang']['deconnexion'].'</a></li>'."\n";
	$html .= "\t\t\t".'</ul>'."\n";
	$html .= "\t\t".'</div>'."\n";


	$html .= "\t".'</div>'."\n";

	// Sub-menu-bar (for RSS, notes, agenda…)
	$html .= $html_sub_menu;

	// Popup node
	if (isset($_GET['msg']) and array_key_exists($_GET['msg'], $GLOBALS['lang']) ) {
		$message = $GLOBALS['lang'][$_GET['msg']];
		$message .= (isset($_GET['nbnew'])) ? htmlspecialchars($_GET['nbnew']).' '.$GLOBALS['lang']['rss_nouveau_flux'] : ''; // nb new RSS
		$html .= '<div class="confirmation">'.$message.'</div>'."\n";

	} elseif (isset($_GET['errmsg']) and array_key_exists($_GET['errmsg'], $GLOBALS['lang'])) {
		$message = $GLOBALS['lang'][$_GET['errmsg']];
		$html .= '<div class="no_confirmation">'.$message.'</div>'."\n";
	}

	$html .= '</header>'."\n";

	echo $html;
}



function get_notifications() {
	$html = '';
	$lis = '';
	$hasNotifs = 0;

	// get last RSS posts
	if (isset($_COOKIE['lastAccessRss']) and is_numeric($_COOKIE['lastAccessRss'])) {
		$query = 'SELECT count(ID) AS nbr FROM rss WHERE bt_date >=?';
		$array = array(date('YmdHis', $_COOKIE['lastAccessRss']));
		$nb_new = liste_elements_count($query, $array);
		if ($nb_new > 0) {
			$hasNotifs += $nb_new;
			$lis .= "\t\t\t".'<li><a href="index.php">'.$nb_new .' '.$GLOBALS['lang']['rss_nouveau_flux'].'</a></li>'."\n";
		}
	}

	$html .= "\t\t".'<div id="notif-icon" data-nb-notifs="'.$hasNotifs.'">'."\n";
	$html .= "\t\t\t".'<ul>'."\n";

	$lis .= ($lis) ? '' : "\t\t\t\t".'<li>'.$GLOBALS['lang']['note_no_notifs'].'</li>'."\n";

	$html .= $lis;

	$html .= "\t\t\t".'</ul>'."\n";
	$html .= "\t\t".'</div>'."\n";

	return $html;
}


function erreurs($erreurs) {
	$html = '';
	if ($erreurs) {
		$html .= '<div id="erreurs">'.'<strong>'.$GLOBALS['lang']['erreurs'].'</strong> :' ;
		$html .= '<ul><li>';
		$html .= implode('</li><li>', $erreurs);
		$html .= '</li></ul></div>'."\n";
	}
	return $html;
}


function moteur_recherche() {
	$requete='';
	if (isset($_GET['q'])) {
		$requete = htmlspecialchars(stripslashes($_GET['q']));
	}
	$return  = "\t\t".'<form action="?" method="get" id="search">'."\n";
	$return .= "\t\t\t".'<input id="q" name="q" type="search" size="20" value="'.$requete.'" placeholder="'.$GLOBALS['lang']['placeholder_search'].'" accesskey="f" />'."\n";
	$return .= "\t\t\t".'<label id="label_q" for="q">'.$GLOBALS['lang']['rechercher'].'</label>'."\n";
	$return .= "\t\t\t".'<button id="input-rechercher" type="submit">'.$GLOBALS['lang']['rechercher'].'</button>'."\n";
	if (isset($_GET['mode']))
	$return .= "\t\t\t".'<input id="mode" name="mode" type="hidden" value="'.htmlspecialchars(stripslashes($_GET['mode'])).'"/>'."\n";
	$return .= "\t\t".'</form>'."\n";
	return $return;
}

function liste_tags($billet, $html_link) {
	$mode = ($billet['bt_type'] == 'article') ? '' : '&amp;mode=links';
	$liste = '';
	if (!empty($billet['bt_tags'])) {
		$tag_list = explode(', ', $billet['bt_tags']);
		// remove diacritics, so that "ééé" does not passe after "zzz" and re-indexes
		foreach ($tag_list as $i => $tag) {
			$tag_list[$i] = array('t' => trim($tag), 'tt' => diacritique(trim($tag)));
		}
		$tag_list = array_reverse(tri_selon_sous_cle($tag_list, 'tt'));

		foreach($tag_list as $tag) {
			$tag = trim($tag['t']);
			if ($html_link == 1) {
				$liste .= '<a href="?tag='.urlencode($tag).$mode.'" rel="tag">'.$tag.'</a>';
			} else {
				$liste .= $tag.' ';
			}
		}
	}
	return $liste;
}



// returns a list of days containing at least one post for a given month
function table_list_date($date, $table) {
	$return = array();
	$column = ($table == 'articles') ? 'bt_date' : 'bt_id';
	$and_date = 'AND '.$column.' <= '.date('YmdHis');

	$query = "SELECT DISTINCT SUBSTR($column, 7, 2) AS date FROM $table WHERE bt_statut = 1 AND $column LIKE '$date%' $and_date";

	try {
		$req = $GLOBALS['db_handle']->query($query);
		while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
			$return[] = $row['date'];
		}
		return $return;
	} catch (Exception $e) {
		die('Erreur 21436 : '.$e->getMessage());
	}
}

// returns dates of the previous and next visible posts
function prev_next_posts($year, $month, $table) {
	$column = ($table == 'articles') ? 'bt_date' : 'bt_id';
	$and_date = 'AND '.$column.' <= '.date('YmdHis');

	$date = new DateTime();
	$date->setDate($year, $month, 1)->setTime(0, 0, 0);
	$date_min = $date->format('YmdHis');
	$date->modify('+1 month');
	$date_max = $date->format('YmdHis');

	$query = "SELECT
		(SELECT SUBSTR($column, 0, 7) FROM $table WHERE bt_statut = 1 AND $column < $date_min ORDER BY $column DESC LIMIT 1),
		(SELECT SUBSTR($column, 0, 7) FROM $table WHERE bt_statut = 1 AND $column > $date_max $and_date ORDER BY $column ASC LIMIT 1)";

	try {
		$req = $GLOBALS['db_handle']->query($query);
		return array_values($req->fetch(PDO::FETCH_ASSOC));
	} catch (Exception $e) {
		die('Erreur 21436 : '.$e->getMessage());
	}
}


function php_lang_to_js() {
	$frontend_str = array();
	$frontend_str['maxFilesSize'] = min(return_bytes(ini_get('upload_max_filesize')), return_bytes(ini_get('post_max_size')));
	$frontend_str['rssJsAlertNewLink'] = $GLOBALS['lang']['rss_jsalert_new_link'];
	$frontend_str['rssJsAlertNewLinkFolder'] = $GLOBALS['lang']['rss_jsalert_new_link_folder'];
	$frontend_str['confirmFeedClean'] = $GLOBALS['lang']['confirm_feed_clean'];
	$frontend_str['confirmFeedSaved'] = $GLOBALS['lang']['confirm_feeds_edit'];
	$frontend_str['confirmCommentSuppr'] = $GLOBALS['lang']['confirm_comment_suppr'];
	$frontend_str['confirmNotesSaved'] = $GLOBALS['lang']['confirm_note_enregistree'];
	$frontend_str['confirmContactsSaved'] = $GLOBALS['lang']['confirm_contacts_saved'];
	$frontend_str['confirmEventsSaved'] = $GLOBALS['lang']['confirm_agenda_updated'];
	$frontend_str['activer'] = $GLOBALS['lang']['activer'];
	$frontend_str['desactiver'] = $GLOBALS['lang']['desactiver'];
	$frontend_str['supprimer'] = $GLOBALS['lang']['supprimer'];
	$frontend_str['epingler'] = $GLOBALS['lang']['epingler'];
	$frontend_str['archiver'] = $GLOBALS['lang']['archiver'];
	$frontend_str['errorPhpAjax'] = $GLOBALS['lang']['error_phpajax'];
	$frontend_str['errorCommentSuppr'] = $GLOBALS['lang']['error_comment_suppr'];
	$frontend_str['errorCommentValid'] = $GLOBALS['lang']['error_comment_valid'];
	$frontend_str['questionQuitPage'] = $GLOBALS['lang']['question_quit_page'];
	$frontend_str['questionSupprComment'] = $GLOBALS['lang']['question_suppr_comment'];
	$frontend_str['questionSupprArticle'] = $GLOBALS['lang']['question_suppr_article'];
	$frontend_str['questionSupprFichier'] = $GLOBALS['lang']['question_suppr_fichier'];
	$frontend_str['questionSupprFlux'] = $GLOBALS['lang']['question_suppr_feed'];
	$frontend_str['questionSupprNote'] = $GLOBALS['lang']['question_suppr_note'];
	$frontend_str['questionSupprEvent'] = $GLOBALS['lang']['question_suppr_event'];
	$frontend_str['questionSupprContact'] = $GLOBALS['lang']['question_suppr_contact'];
	$frontend_str['notesLabelTitle'] = $GLOBALS['lang']['label_titre'];
	$frontend_str['emptyTitle'] = $GLOBALS['lang']['label_sans_titre'];

	$sc = json_encode($frontend_str, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	$sc = '<script id="jsonLang" type="application/json">'."\n".$sc."\n".'</script>'."\n";
	return $sc;
}
