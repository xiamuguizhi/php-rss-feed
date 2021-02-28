<?php
// *** LICENSE ***
// oText is free software.
//
// By Fred Nassar (2006) and Timo Van Neerden (since 2010)
// See "LICENSE" file for info.
// *** LICENSE ***

/*  Creates a new BlogoText base.
    if file does not exists, it is created, as well as the tables.
    if file does exists, tables are checked and created if not exists
*/
function create_tables() {
	if (file_exists(DIR_CONFIG.'mysql.php')) {
		include(DIR_CONFIG.'mysql.php');
	}
	$auto_increment = (DBMS == 'mysql') ? 'AUTO_INCREMENT' : ''; // SQLite doesn't need this, but MySQL does.
	$index_limit_size = (DBMS == 'mysql') ? '(15)' : ''; // MySQL needs a limit for indexes on TEXT fields.
	$if_not_exists = (DBMS == 'sqlite') ? 'IF NOT EXISTS' : ''; // MySQL doesn’t know this statement for INDEXES

	/* here bt_ID is a GUID, from the feed, not only a 'YmdHis' date string.*/
	$dbase_structure['rss'] = "CREATE TABLE IF NOT EXISTS rss
		(
			ID INTEGER PRIMARY KEY $auto_increment,
			bt_id TEXT,
			bt_date BIGINT,
			bt_title TEXT,
			bt_link TEXT,
			bt_feed TEXT,
			bt_content TEXT,
			bt_statut TINYINT,
			bt_bookmarked TINYINT,
			bt_folder TEXT
		); CREATE INDEX $if_not_exists dateidR ON rss ( bt_date, bt_id$index_limit_size );";

	/*
	* SQLite
	*
	*/
	switch (DBMS) {
		case 'sqlite':
				if (!is_file(SQL_DB)) {
					if (!creer_dossier(DIR_DATABASES)) {
						die('Impossible de creer le dossier databases (chmod?)');
					}
				}
				$file = SQL_DB;
				// open tables
				try {
					$db_handle = new PDO('sqlite:'.$file);
					$db_handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$db_handle->query("PRAGMA temp_store=MEMORY; PRAGMA synchronous=OFF; PRAGMA journal_mode=WAL;");
					$wanted_tables = array_keys($dbase_structure);
					foreach ($wanted_tables as $table_name) {
							$results = $db_handle->exec($dbase_structure[$table_name]);
					}
				} catch (Exception $e) {
					die('Erreur 1: '.$e->getMessage());
				}
			break;

		/*
		* MySQL
		*
		*/
		case 'mysql':
				try {

					$options_pdo[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
					$db_handle = new PDO('mysql:host='.MYSQL_HOST.';dbname='.MYSQL_DB.";charset=utf8;sql_mode=PIPES_AS_CONCAT;", MYSQL_LOGIN, MYSQL_PASS, $options_pdo);
					// check each wanted table
					$wanted_tables = array_keys($dbase_structure);
					foreach ($wanted_tables as $table_name) {
							$results = $db_handle->query($dbase_structure[$table_name]."DEFAULT CHARSET=utf8");
							$results->closeCursor();
					}
				} catch (Exception $e) {
					die('Erreur 2: '.$e->getMessage());
				}
			break;
	}

	return $db_handle;
}


/* Open a base */
function open_base() {
	$handle = create_tables();
	$handle->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
	return $handle;
}


/* lists elements with search criterias given in $array. Returns an array containing the data */
function liste_elements($query, $array, $data_type='') {
	try {
		$req = $GLOBALS['db_handle']->prepare($query);
		$req->execute($array);
		$return = array();
		while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
			$return[] = $row;
		}
		return $return;
	} catch (Exception $e) {
		die('Erreur 89208 : '.$e->getMessage() . "\n<br/>".$query);
	}
}

/* same as above, but return the amount of entries */
function liste_elements_count($query, $array) {
	try {
		$req = $GLOBALS['db_handle']->prepare($query);
		$req->execute($array);
		$result = $req->fetch();
		return $result['nbr'];
	} catch (Exception $e) {
		die('Erreur 0003: '.$e->getMessage());
	}
}

// returns or prints an entry of some element of some table (very basic)
function get_entry($table, $entry, $id, $retour_mode) {
	$query = "SELECT $entry FROM $table WHERE bt_id=?";
	try {
		$req = $GLOBALS['db_handle']->prepare($query);
		$req->execute(array($id));
		$result = $req->fetch();
		//echo '<pre>';print_r($result);
	} catch (Exception $e) {
		die('Erreur : '.$e->getMessage());
	}

	if ($retour_mode == 'return' and !empty($result[$entry])) {
		return $result[$entry];
	}
	if ($retour_mode == 'echo' and !empty($result[$entry])) {
		echo $result[$entry];
	}
	return '';
}


// POST ARTICLE
/*
 * On post of an article (always on admin sides)
 * gets posted informations and turn them into
 * an array
 *
 */

/* FOR COMMENTS : RETUNS nb_com per author */
function nb_entries_as($table, $what) {
	$result = array();
	$query = "SELECT count($what) AS nb, $what FROM $table GROUP BY $what ORDER BY nb DESC";
	try {
		$result = $GLOBALS['db_handle']->query($query)->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	} catch (Exception $e) {
		die('Erreur 0349 : '.$e->getMessage());
	}
}

/* FOR TAGS (articles & notes) */
function list_all_tags($table, $statut) {
	try {
		if ($statut !== FALSE) {
			$res = $GLOBALS['db_handle']->query("SELECT bt_tags FROM $table WHERE bt_statut = $statut");
		} else {
			$res = $GLOBALS['db_handle']->query("SELECT bt_tags FROM $table");
		}
		$liste_tags = '';
		// met tous les tags de tous les articles bout à bout
		while ($entry = $res->fetch()) {
			if (trim($entry['bt_tags']) != '') {
				$liste_tags .= $entry['bt_tags'].',';
			}
		}
		$res->closeCursor();
		$liste_tags = rtrim($liste_tags, ',');
	} catch (Exception $e) {
		die('Erreur 4354768 : '.$e->getMessage());
	}

	$liste_tags = str_replace(array(', ', ' ,'), ',', $liste_tags);
	$tab_tags = explode(',', $liste_tags);
	sort($tab_tags);
	unset($tab_tags['']);
	return array_count_values($tab_tags);
}


/* Enregistre le flux dans une BDD.
   $flux est un Array avec les données dedans.
	$flux ne contient que les entrées qui doivent être enregistrées
	 (la recherche de doublons est fait en amont)
*/
function bdd_rss($flux, $what) {
	if ($what == 'enregistrer-nouveau') {
		try {
			$GLOBALS['db_handle']->beginTransaction();
			foreach ($flux as $post) {
				$req = $GLOBALS['db_handle']->prepare('INSERT INTO rss
				(  bt_id,
					bt_date,
					bt_title,
					bt_link,
					bt_feed,
					bt_content,
					bt_statut,
					bt_bookmarked,
					bt_folder
				)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
				$req->execute(array(
					$post['bt_id'],
					$post['bt_date'],
					$post['bt_title'],
					$post['bt_link'],
					$post['bt_feed'],
					$post['bt_content'],
					$post['bt_statut'],
					$post['bt_bookmarked'],
					$post['bt_folder']
				));
			}
			$GLOBALS['db_handle']->commit();
			return TRUE;
		} catch (Exception $e) {
			return 'Erreur 5867-rss-add-sql : '.$e->getMessage();
		}
	}
}

/* FOR RSS : RETUNS list of GUID in whole DB */
function rss_list_guid() {
	$result = array();
	$query = "SELECT bt_id FROM rss";
	try {
		$result = $GLOBALS['db_handle']->query($query)->fetchAll(PDO::FETCH_COLUMN, 0);
		return $result;
	} catch (Exception $e) {
		die('Erreur 0329-rss-get_guid : '.$e->getMessage());
	}
}

/* FOR RSS : RETUNS nb of articles per feed */
function rss_count_feed() {
	$result = $return = array();
	//$query = "SELECT bt_feed, SUM(bt_statut) AS nbrun, SUM(bt_bookmarked) AS nbfav, SUM(CASE WHEN bt_date >= ".date('Ymd').'000000'." AND bt_statut = 1 THEN 1 ELSE 0 END) AS nbtoday FROM rss GROUP BY bt_feed";

	$query = "SELECT bt_feed, SUM(bt_statut) AS nbrun FROM rss GROUP BY bt_feed";
	try {
		$result = $GLOBALS['db_handle']->query($query)->fetchAll(PDO::FETCH_ASSOC);

		foreach($result as $i => $res) {
			$return[$res['bt_feed']] = $res['nbrun'];
		}
		return $return;
	} catch (Exception $e) {
		die('Erreur 0329-rss-count_per_feed : '.$e->getMessage());
	}
}

