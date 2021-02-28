<?php
// *** LICENSE ***
// oText is free software.
//
// By Fred Nassar (2006) and Timo Van Neerden (since 2010)
// See "LICENSE" file for info.
// *** LICENSE ***

/// formulaires GENERIQUES //////////

function form_select($id, $choix, $defaut, $label) {
	$form = '';
	if (!empty($label)) {
		$form .= '<label for="'.$id.'">'.$label.'</label>'."\n";
	}
	$form .= "\t".'<select id="'.$id.'" name="'.$id.'">'."\n";
	foreach ($choix as $valeur => $mot) {
		$form .= "\t\t".'<option value="'.$valeur.'"'.(($defaut == $valeur) ? ' selected="selected" ' : '').'>'.$mot.'</option>'."\n";
	}
	$form .= "\t".'</select>'."\n";
	$form .= "\n";
	return $form;
}

function hidden_input($nom, $valeur, $id=0) {
	$id = ($id === 0) ? '' : ' id="'.$nom.'"';
	$form = '<input type="hidden" name="'.$nom.'"'.$id.' value="'.$valeur.'" />'."\n";
	return $form;
}

/// formulaires PREFERENCES //////////

function select_yes_no($name, $defaut, $label) {
	$choix = array(
		'1' => $GLOBALS['lang']['oui'],
		'0' => $GLOBALS['lang']['non']
	);
	$form = '<label for="'.$name.'" >'.$label.'</label>'."\n";
	$form .= '<select id="'.$name.'" name="'.$name.'">'."\n" ;
	foreach ($choix as $option => $label) {
		$form .= "\t".'<option value="'.htmlentities($option).'"'.(($option == $defaut) ? ' selected="selected" ' : '').'>'.htmlentities($label).'</option>'."\n";
	}
	$form .= '</select>'."\n";
	return $form;
}

function form_checkbox($name, $checked, $label) {
	$checked = ($checked) ? "checked " : '';
	$form = '<input type="checkbox" id="'.$name.'" name="'.$name.'" '.$checked.' class="checkbox-toggle" />'."\n" ;
	$form .= '<label for="'.$name.'" >'.$label.'</label>'."\n";
	return $form;
}

function form_fuseau_horaire($defaut) {
	$all_timezones = timezone_identifiers_list();
	$liste_fuseau = array();
	$cities = array();
	foreach($all_timezones as $tz) {
		$spos = strpos($tz, '/');
		if ($spos !== FALSE) {
			$liste_fuseau[substr($tz, 0, $spos)][$tz] = substr($tz, $spos+1);
		} elseif ($tz == 'UTC') {
			$liste_fuseau['UTC'][$tz] = $tz;
		}
	}
	$form = '<label>'.$GLOBALS['lang']['pref_fuseau_horaire'].'</label>'."\n";
	$form .= '<select name="fuseau_horaire">'."\n";
	foreach ($liste_fuseau as $continent => $tzs) {
		$form .= "\t".'<optgroup label="'.ucfirst(strtolower($continent)).'">'."\n";
		foreach ($tzs as $tz => $city) {
			$form .= "\t\t".'<option value="'.htmlentities($tz).'"';
			$form .= ($defaut == $tz) ? ' selected="selected"' : '';
				$timeoffset = date_offset_get(date_create('now', timezone_open($tz)) );
				$formated_toffset = '(UTC'.(($timeoffset < 0) ? '–' : '+').str2(floor((abs($timeoffset)/3600))) .':'.str2(floor((abs($timeoffset)%3600)/60)) .')';
			$form .= '>'.$formated_toffset.' '.htmlentities($city).'</option>'."\n";
		}
		$form .= "\t".'</optgroup>'."\n";
	}
	$form .= '</select>'."\n";
	return $form;
}

