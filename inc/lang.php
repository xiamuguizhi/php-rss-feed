<?php
// *** LICENSE ***
// oText is free software.
//
// By Fred Nassar (2006) and Timo Van Neerden (since 2010)
// See "LICENSE" file for info.
// *** LICENSE ***

// available languages
$GLOBALS['langs'] = array(
	"fr" => 'Français',
	"en" => 'English',
	"cn" => '中文'	,
);

if (empty($GLOBALS['lang'])) $GLOBALS['lang'] = '';

switch ($GLOBALS['lang']) {
	case 'en':
		include_once('lang/en_EN.php');
		break;
	case 'fr':
	default:
		include_once('lang/fr_FR.php');
		break;
	case 'cn':
		include_once('lang/zh_CN.php');
		break;		
}
