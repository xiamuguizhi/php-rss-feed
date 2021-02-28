<?php
// *** LICENSE ***
// oText is free software.
//
// By Fred Nassar (2006) and Timo Van Neerden (since 2010)
// See "LICENSE" file for info.
// *** LICENSE ***

// THIS FILE
//
// This file contains functions relative to search and list data posts.
// It also contains functions about files : creating, deleting files, etc.

function creer_dossier($dossier, $make_htaccess='') {
	if ( !is_dir($dossier) ) {
		if (mkdir($dossier, 0777) === TRUE) {
			fichier_index($dossier); // fichier index.html pour éviter qu'on puisse lister les fihciers du dossier
			if ($make_htaccess == 1) fichier_htaccess($dossier); // pour éviter qu'on puisse accéder aux fichiers du dossier directement
			return TRUE;
		} else {
			return FALSE;
		}
	}
	return TRUE; // si le dossier existe déjà.
}


function fichier_user() {
	$fichier_user = DIR_CONFIG.'user.ini';
	$content = '';
	if (strlen(trim($_POST['mdp'])) == 0) {
		$new_mdp = USER_PWHASH;
	} else {
		$new_mdp = password_hash($_POST['mdp'], PASSWORD_BCRYPT);
	}
	$content .= '; <?php die(); /*'."\n\n";
	$content .= '; This file contains user login + password hash.'."\n\n";

	$content .= 'USER_LOGIN = \''.addslashes(clean_txt(htmlspecialchars($_POST['identifiant']))).'\''."\n";
	$content .= 'USER_PWHASH = \''.$new_mdp.'\''."\n";

	if (file_put_contents($fichier_user, $content) === FALSE) {
		return FALSE;
	} else {
		return TRUE;
	}
}

function fichier_adv_conf() {
	$fichier_advconf = DIR_CONFIG.'config-advanced.ini';
	$conf='';
	$conf .= '; <?php die(); /*'."\n\n";
	$conf .= '; This file contains some more advanced configuration features.'."\n\n";
	$conf .= 'BLOG_UID = \''.sha1(uniqid(mt_rand(), true)).'\''."\n";
	$conf .= 'DISPLAY_PHP_ERRORS = -1;'."\n";
	$conf .= 'USE_IP_IN_SESSION = 0;'."\n\n\n";
	$conf .= '; */ ?>'."\n";

	if (file_put_contents($fichier_advconf, $conf) === FALSE) {
		return FALSE;
	} else {
		return TRUE;
	}
}

function fichier_prefs() {
	$fichier_prefs = DIR_CONFIG.'prefs.php';
	if(!empty($_POST['_verif_envoi'])) {
		$lang = (isset($_POST['langue']) and preg_match('#^[a-z]{2}$#', $_POST['langue'])) ? $_POST['langue'] : 'fr';
		$auteur = addslashes(clean_txt(htmlspecialchars($_POST['auteur'])));
		$email = addslashes(clean_txt(htmlspecialchars($_POST['email'])));
		$racine = addslashes(trim(htmlspecialchars($_POST['racine'])));
		$format_date = htmlspecialchars($_POST['format_date']);
		$format_heure = htmlspecialchars($_POST['format_heure']);
		$fuseau_horaire = addslashes(clean_txt(htmlspecialchars($_POST['fuseau_horaire'])));
	} else {
		$lang = (isset($_POST['langue']) and preg_match('#^[a-z]{2}$#', $_POST['langue'])) ? $_POST['langue'] : 'fr';
		$auteur = addslashes(clean_txt(htmlspecialchars(USER_LOGIN)));
		$email = 'mail@example.com';
		$racine = addslashes(clean_txt(trim(htmlspecialchars($_POST['racine']))));
		$format_date = '0';
		$format_heure = '0';
		$fuseau_horaire = 'UTC';
	}
	$prefs = "<?php\n";
	$prefs .= "\$GLOBALS['lang'] = '".$lang."';\n";
	$prefs .= "\$GLOBALS['auteur'] = '".$auteur."';\n";
	$prefs .= "\$GLOBALS['email'] = '".$email."';\n";
	$prefs .= "\$GLOBALS['racine'] = '".$racine."';\n";
	$prefs .= "\$GLOBALS['format_date'] = '".$format_date."';\n";
	$prefs .= "\$GLOBALS['format_heure'] = '".$format_heure."';\n";
	$prefs .= "\$GLOBALS['fuseau_horaire'] = '".$fuseau_horaire."';\n";
	$prefs .= "?>";
	if (file_put_contents($fichier_prefs, $prefs) === FALSE) {
		return FALSE;
	} else {
		return TRUE;
	}
}

function fichier_mysql($sgdb) {
	$fichier_mysql = DIR_CONFIG.'mysql.ini';

	$data = '';
	if ($sgdb !== FALSE) {
		$data .= '; <?php die(); /*'."\n\n";
		$data .= '; This file contains MySQL credentials and configuration.'."\n\n";
		$data .= 'MYSQL_LOGIN = \''.htmlentities($_POST['mysql_user'], ENT_QUOTES).'\''."\n";
		$data .= 'MYSQL_PASS = \''.htmlentities($_POST['mysql_passwd'], ENT_QUOTES).'\''."\n";
		$data .= 'MYSQL_DB = \''.htmlentities($_POST['mysql_db'], ENT_QUOTES).'\''."\n";
		$data .= 'MYSQL_HOST = \''.htmlentities($_POST['mysql_host'], ENT_QUOTES).'\''."\n\n";
		$data .= 'DBMS = \''.$sgdb.'\''."\n";
	}

	if (file_put_contents($fichier_mysql, $data) === FALSE) {
		return FALSE;
	} else {
		return TRUE;
	}
}

function fichier_index($dossier) {
	$content = '<html>'."\n";
	$content .= "\t".'<head>'."\n";
	$content .= "\t\t".'<title>Access denied</title>'."\n";
	$content .= "\t".'</head>'."\n";
	$content .= "\t".'<body>'."\n";
	$content .= "\t\t".'<a href="/">Retour a la racine du site</a>'."\n";
	$content .= "\t".'</body>'."\n";
	$content .= '</html>';
	$index_html = $dossier.'/index.html';

	if (file_put_contents($index_html, $content) === FALSE) {
		return FALSE;
	} else {
		return TRUE;
	}
}

function fichier_htaccess($dossier) {
	$content = '<Files *>'."\n";
	$content .= 'Order allow,deny'."\n";
	$content .= 'Deny from all'."\n";
	$content .= '</Files>'."\n";
	$htaccess = $dossier.'/.htaccess';

	if (file_put_contents($htaccess, $content) === FALSE) {
		return FALSE;
	} else {
		return TRUE;
	}
}

function open_serialzd_file($fichier) {
	$liste  = (file_exists($fichier)) ? unserialize(base64_decode(substr(file_get_contents($fichier), strlen('<?php /* '), -strlen(' */')))) : array();
	return $liste;
}

// $feeds is an array of URLs: Array( [http://…] => md5(), [http://…] => md5(), …)
// Returns the same array: Array([http://…] [[headers]=> 'string', [body]=> 'string'], …)
function request_external_files($feeds, $timeout, $echo_progress=false) {

	// uses chunks (smaller arrays) of 30 feeds because Curl has problems with too big "multi" requests (or server might has a limit).
	$chunks = array_chunk($feeds, 30, true);

	$results = array();
	$total_feed = count($feeds);
	if ($echo_progress === true) {
		echo '0/'.$total_feed.' '; ob_flush(); flush(); // for Ajax
	}

	foreach ($chunks as $chunk) {

			set_time_limit(30);
			$curl_arr = array();
			$master = curl_multi_init();
			$total_feed_chunk = count($chunk)+count($results);

			// init each url
			foreach ($chunk as $url => $feed_url_hash) {

				$curl_arr[$url] = curl_init(trim($url));
				curl_setopt_array($curl_arr[$url], array(
						CURLOPT_RETURNTRANSFER => TRUE, // force Curl to return data instead of displaying it
						CURLOPT_FOLLOWLOCATION => TRUE, // follow 302 ans 301 redirects
						CURLOPT_CONNECTTIMEOUT => 0, // 0 = indefinately ; no connection-timeout (ruled out by "set_time_limit" hereabove)
						CURLOPT_TIMEOUT => $timeout, // downloading timeout
						CURLOPT_USERAGENT => BLOGOTEXT_UA, // User-agent (uses the UA of browser)
						CURLOPT_SSL_VERIFYPEER => FALSE, // ignore SSL errors
						CURLOPT_SSL_VERIFYHOST => FALSE, // ignore SSL errors
						CURLOPT_ENCODING => "gzip", // take into account gziped pages
						CURLOPT_VERBOSE => 0,
						CURLOPT_HEADER => 1, // also return header
					));
				curl_multi_add_handle($master, $curl_arr[$url]);
			}

			// exec connexions
			$running = $oldrunning = 0;
			$utime = microtime(true);
			do {
				curl_multi_exec($master, $running);

				// echoes the nb of feeds remaining
				if ($echo_progress === true) {
					if ($utime + 0.5 < microtime(true)) { // only echos every 0.5 secondes max
						echo ($total_feed_chunk-$running).'/'.$total_feed.' '; ob_flush(); flush();
						$utime = microtime(true);
					}
				}
				usleep(1001);
			} while ($running > 0);

			// multi select contents
			foreach ($chunk as $url => $feed_url_hash) {
				$response = curl_multi_getcontent($curl_arr[$url]);
				$header_size = curl_getinfo($curl_arr[$url], CURLINFO_HEADER_SIZE);
				$results[$feed_url_hash]['url'] = curl_getinfo($curl_arr[$url], CURLINFO_EFFECTIVE_URL);
				$results[$feed_url_hash]['headers'] = http_parse_headers(mb_strtolower(substr($response, 0, $header_size)));
				$results[$feed_url_hash]['body'] = mb_convert_encoding(trim(substr($response, $header_size)), 'UTF-8');

			}
			// Ferme les gestionnaires
			curl_multi_close($master);

	}

	return $results;
}


/* retrieve all the feeds, returns the amount of new elements */
function refresh_rss($feeds) {
	$new_feed_elems = array();
	$guid_in_db = rss_list_guid();
	$count_new = 0;
	$total_feed = count($feeds);

	//foreach ($GLOBALS['liste_flux'] as $i => $feed) {
	foreach ($feeds as $i => $feed) {
		$urls[$feed['link']] = $i;
	}

	$retrieved_elements = retrieve_new_feeds($urls);

	if (!$retrieved_elements) return 0;

	foreach ($retrieved_elements as $feed_id => $feed_elmts) {
	//	if ($feed_elmts === FALSE) {
	//		continue;
	//	} else {
			foreach($feed_elmts['items'] as $key => $item) {
				// if item in DB or item older that last rss-update, discard item.
				if ( (in_array($item['bt_id'], $guid_in_db)) or ($item['bt_date'] <= $feeds[$feed_id]['time']) ) {
					unset($feed_elmts['items'][$key]);
				// else, init item 
				} else {
					$feed_elmts['items'][$key]['bt_statut'] = 1;
					$feed_elmts['items'][$key]['bt_bookmarked'] = 0;

				}
				// we save the date of the last element on that feed
				// we do not use the time of last retreiving, because it might not be correct due to different time-zones with the feeds date.
				if ($item['bt_date'] > $GLOBALS['liste_flux'][$feed_id]['time']) {
					$GLOBALS['liste_flux'][$feed_id]['time'] = $item['bt_date'];
				}
			}
			// keep the remaining posts
			if (!empty($feed_elmts['items'])) {
				$new_feed_elems = array_merge($new_feed_elems, $feed_elmts['items']);
			}
	//	}
	}

	// if list of new elements is !empty, save new elements
	if (!empty($new_feed_elems)) {
		$count_new = count($new_feed_elems);
		$ret = bdd_rss($new_feed_elems, 'enregistrer-nouveau');
		if ($ret !== TRUE) {
			echo $ret;
		}
	}

	// recount unread elements (they are put in that array for caching ans performance purpose).
	$feeds_nb = rss_count_feed();
	foreach ($GLOBALS['liste_flux'] as $hash => $item) {
		$GLOBALS['liste_flux'][$hash]['nbrun'] = 0;
		if (isset($feeds_nb[$hash])) {
			$GLOBALS['liste_flux'][$hash]['nbrun'] = $feeds_nb[$hash];
		}
	}

	// save last success time in the feed list
	file_put_contents(FEEDS_DB, '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_flux']))).' */');

	return $new_feed_elems;
}



function retrieve_new_feeds($feedlinks) {
	if (!$feeds = request_external_files($feedlinks, 25, true)) { // timeout = 25s
		return FALSE;
	}

	$return = array();
	foreach ($feeds as $feed_id => $response) {
		$GLOBALS['liste_flux'][$feed_id]['iserror'] = 0;
		if (!empty($response['body'])) {
			$new_md5 = hash('md5', $response['body']);
			// if Feed has changed : parse it (otherwise, do nothing : no need)
			if ($GLOBALS['liste_flux'][$feed_id]['checksum'] != $new_md5) {
				$data_array = feed2array($response['body']);
				if ($data_array !== FALSE and is_array($data_array['items'])) {
					// attach feed -ID & -folder to the individual items
					foreach ($data_array['items'] as $i => $item) {
						$data_array['items'][$i]['bt_feed'] = $feed_id;
						$data_array['items'][$i]['bt_folder'] = $GLOBALS['liste_flux'][$feed_id]['folder'];
					}

					$return[$feed_id] = $data_array;
					$GLOBALS['liste_flux'][$feed_id]['checksum'] = $new_md5;
					$GLOBALS['liste_flux'][$feed_id]['iserror'] = 0;
				// array IS false : XML error
				} elseif ($data_array === FALSE) {
					if (isset($GLOBALS['liste_flux'][$feed_id])) { // if it was not set, it would be an error on "feed-add" (this error is handled further)
						$GLOBALS['liste_flux'][$feed_id]['iserror'] = 'XML Parsing Error';
					}
				}
			}
		}
		else {
			$GLOBALS['liste_flux'][$feed_id]['iserror'] = 'Empty HTTP Response.';
		}
	}

	if (!empty($return)) return $return;
	return FALSE;
}


# Based upon Feed-2-array, by bronco@warriordudimanche.net
function feed2array($feed_content) {
	$flux = array('infos'=>array(),'items'=>array());

	if (preg_match('#<rss(.*)</rss>#si', $feed_content)) { $flux['infos']['type'] = 'RSS'; } //RSS ?
	elseif (preg_match('#<feed(.*)</feed>#si', $feed_content)) { $flux['infos']['type'] = 'ATOM'; } //ATOM ?
	else { return false; } // the feed isn't rss nor atom

	try {
		if (@$feed_obj = new SimpleXMLElement($feed_content, LIBXML_NOCDATA)) {
			$flux['infos']['version']=$feed_obj->attributes()->version;
			if (!empty($feed_obj->channel->title)) { $flux['infos']['title'] = (string)$feed_obj->channel->title; }
			if (!empty($feed_obj->title)) {          $flux['infos']['title'] = (string)$feed_obj->title; }
			if (!empty($feed_obj->channel->link)) {  $flux['infos']['link'] = (string)$feed_obj->channel->link; }
			if (!empty($feed_obj->link)) {           $flux['infos']['link'] = (string)$feed_obj->link; }

			if (!empty($feed_obj->channel->item)){ $items = $feed_obj->channel->item; }
			if (!empty($feed_obj->entry)){ $items = $feed_obj->entry; }

			if (empty($items)) { return $flux; }

			foreach ($items as $item) {
				$c = count($flux['items']);
				// title
				if (!empty($item->title)) { $flux['items'][$c]['bt_title'] = html_entity_decode((string)$item->title, ENT_QUOTES | ENT_HTML5, 'UTF-8');}
				if (empty($flux['items'][$c]['bt_title'])) {
					$flux['items'][$c]['bt_title'] = "-";
				}

				// link
				if (!empty($item->link['href'])) {  $flux['items'][$c]['bt_link'] = (string)$item->link['href']; }
				if (!empty($item->link)) {          $flux['items'][$c]['bt_link'] = (string)$item->link; }

				// id
				if (!empty($item->id)) { $flux['items'][$c]['bt_id'] = (string)$item->id; }
				if (!empty($item->guid)) {   $flux['items'][$c]['bt_id'] = (string)$item->guid; }
				if (empty($flux['items'][$c]['bt_id'])) {
					$flux['items'][$c]['bt_id'] = microtime();
				}
				$flux['items'][$c]['bt_id'] = hash('md5', $flux['items'][$c]['bt_id']);

				// date
				if (!empty($item->updated)) {   $flux['items'][$c]['bt_date'] = date('YmdHis', strtotime((string)$item->updated)); }
				if (!empty($item->pubDate)) {   $flux['items'][$c]['bt_date'] = date('YmdHis', strtotime((string)$item->pubDate)); }
				if (!empty($item->published)) { $flux['items'][$c]['bt_date'] = date('YmdHis', strtotime((string)$item->published)); }
				if (empty($flux['items'][$c]['bt_date'])) {
					$flux['items'][$c]['bt_date'] = date('YmdHis');
				}

				// content
				if (!empty($item->description)) {   $flux['items'][$c]['bt_content'] = (string)$item->description; }
				if (!empty($item->content)) {       $flux['items'][$c]['bt_content'] = (string)$item->content; }
				if (!empty($item->children('content', true)->encoded)) { $flux['items'][$c]['bt_content'] = (string)$item->children('content', true)->encoded; }

				// SPECIAL CASES
				//  for Youtube
				if (isset($flux['items'][$c]['bt_link']) and strpos(parse_url($flux['items'][$c]['bt_link'], PHP_URL_HOST), 'youtube.com') !== FALSE) {
					$content = (string)$item->children('media', true)->group->description;
					$yt_video_id = (string)$item->children('yt', true)->videoId;
					if (!empty($yt_video_id) ) { $flux['items'][$c]['bt_content'] = '<div class="youtube-iframe-container"><iframe width="1120" height="630" src="https://www.youtube.com/embed/'.$yt_video_id.'?rel=0"></iframe></div>'; }
					if (!empty($content) ) { $flux['items'][$c]['bt_content'] .= nl2br((string) $content); }
				}


				if (empty($flux['items'][$c]['bt_content'])) $flux['items'][$c]['bt_content'] = '-';

			}
		} else {
			return false;
		}

		return $flux;

	} catch (Exception $e) {
		echo $e-> getMessage();
		echo ' '.$feed_content." \n";
		return false;
	}
}

/* From the data out of DB, creates JSON, to send to browser
*
* @rss_entries : array of entries, as returned by `function liste_elements()`
* @enclose_in_scripts_tag : if `true`, returns the string in a <script> tag
*
*/
function send_rss_json($rss_entries, $enclose_in_script_tag) {
	// send all the entries data in a JSON format

	$out = '{'."\n";
	$out .= '"sites":'.'[';
	foreach ($GLOBALS['liste_flux'] as $i => $feed) {
		$out .= '{'.
			'"id":'.json_encode($i).', '.
			'"link":'.json_encode($feed['link'], JSON_UNESCAPED_UNICODE).', '.
			'"title":'.json_encode($feed['title'], JSON_UNESCAPED_UNICODE).', '.
			'"checksum":'.json_encode($feed['checksum']).', '.
			'"time":'.json_encode($feed['time']).', '.
			'"folder":'.json_encode($feed['folder'], JSON_UNESCAPED_UNICODE).', '.
			'"iserror":'.json_encode($feed['iserror'], JSON_UNESCAPED_UNICODE).', '.
			'"nbrun":'.$feed['nbrun'].''.
		'},';
	}
	$out = rtrim(trim($out), ','); // trim out the last ',' (causes JSON error);
	$out .= '],';

	// RSS entries
	$out .= '"posts":'.'[';
	foreach ($rss_entries as $i => $entry) {
		if (isset($GLOBALS['liste_flux'][$entry['bt_feed']])) {
			$out .= '{'.
				'"id":'.json_encode($entry['bt_id']).', '.
				'"datetime":'.json_encode($entry['bt_date']).', '.
				'"feedhash":'.json_encode($entry['bt_feed']).', '.
				'"statut":'.$entry['bt_statut'].', '.
				'"fav":'.$entry['bt_bookmarked'].', '.
				'"title":'.json_encode($entry['bt_title'], JSON_UNESCAPED_UNICODE).', '.
				'"link":'.json_encode($entry['bt_link']).', '.
				'"sitename":'.json_encode($GLOBALS['liste_flux'][$entry['bt_feed']]['title'], JSON_UNESCAPED_UNICODE).', '.
				'"content":'.json_encode($entry['bt_content'], JSON_UNESCAPED_UNICODE).', '.
				'"folder":'.json_encode($GLOBALS['liste_flux'][$entry['bt_feed']]['folder']).''.
			'},';
		}
	}
	$out = rtrim(trim($out), ','); // trim out the last ',' (causes JSON error);
	$out .= ']';
	$out .= '}';

	if ($enclose_in_script_tag) {
		$out = '<script id="json_rss" type="application/json" defer>'.$out.'</script>'."\n";
	}
	return $out;
}


if (!function_exists('http_parse_headers')) {
	function http_parse_headers($raw_headers) {
		$headers = array();

		$array_headers = (is_array($raw_headers) ? $raw_headers : explode("\n", $raw_headers));

		foreach ($array_headers as $i => $h) {
			$h = explode(':', $h, 2);

			if (isset($h[1])) {
				$headers[$h[0]] = trim($h[1]);
			}
		}
		return $headers;
	}
}
