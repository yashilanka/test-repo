<?php

/*======================================================================*\
|| #################################################################### ||
|| # Rhino 1.1.6                                                      # ||
|| # ---------------------------------------------------------------- # ||
|| # Copyright 2012 Rhino All Rights Reserved.                        # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| #   ---------------- Rhino IS NOT FREE SOFTWARE ----------------   # ||
|| #                 http://www.livesupportrhino.com                  # ||
|| #################################################################### ||
\*======================================================================*/

// prevent direct php access
define('LS_PREVENT_ACCESS', 1);

if (!file_exists('config.php')) {
    die('[index.php] config.php not exist');
}
require_once 'config.php';

$page = ($tempp ? filter_var($tempp, FILTER_SANITIZE_STRING) : '');
$page1 = ($tempp1 ? filter_var($tempp1, FILTER_SANITIZE_STRING) : '');
$page2 = ($tempp2 ? filter_var($tempp2, FILTER_SANITIZE_STRING) : '');
$page3 = ($tempp3 ? filter_var($tempp3, FILTER_SANITIZE_STRING) : '');
$page4 = ($tempp4 ? filter_var($tempp4, FILTER_SANITIZE_STRING) : '');

// Import the language file
if (file_exists(APP_PATH.'lang/'.LS_LANG.'.ini')) {
    $tl = parse_ini_file(APP_PATH.'lang/'.LS_LANG.'.ini', true);
} else {
    $tl = parse_ini_file(APP_PATH.'lang/en.ini', true);
}

// If Referer Zero go to the session url
if (!isset($_SERVER['HTTP_REFERER'])) {
	if ($_SESSION['ls_lastURL']) {
    	$_SERVER['HTTP_REFERER'] = $_SESSION['ls_lastURL'];
    } else {
    	$_SERVER['HTTP_REFERER'] = BASE_URL;
    }
}

// Lang and pages file for template
define('LS_SITELANG', LS_LANG);

// Assign Pages to template
define('LS_PAGINATE_ADMIN', 0);

// Define the avatarpath in the settings
define(LS_FILEPATH_BASE, BASE_URL.basename(LS_FILEPATH));

// Define the real request
$realrequest = 'index.php?p='.$page;
define('LS_PARSE_REQUEST', $realrequest);

// Set the check page to 0
$LS_CHECK_PAGE = 0;
	
	// let's do the dirty work
	if ($page == 'start') {
	
		if (online_operators()) {
			
			require_once 'start.php';
			$LS_CHECK_PAGE = 1;
			$PAGE_SHOWTITLE = 1;
			
		} else {
			
			require_once 'contact.php';
			$LS_CHECK_PAGE = 1;
			$PAGE_SHOWTITLE = 1;
		}
	}
	// Start the chat
	if ($page == 'chat') {
		require_once 'chat.php';
		$LS_CHECK_PAGE = 1;
		$PAGE_SHOWTITLE = 1;
	}
	// Stop the chat
	if ($page == 'stop') {
		require_once 'stop.php';
		$LS_CHECK_PAGE = 1;
		$PAGE_SHOWTITLE = 1;
	}
	// Stop the chat
	if ($page == 'feedback') {
		require_once 'feedback.php';
		$LS_CHECK_PAGE = 1;
		$PAGE_SHOWTITLE = 1;
	}
	// Get the button
	if ($page == 'b') {
	    require_once 'button.php';
	    $LS_CHECK_PAGE = 1;
	    $PAGE_SHOWTITLE = 1;
	}
    // Get the 404 page
   	if ($page == '404') {
   	    $PAGE_TITLE = '404 ';
   	    require_once '404.php';
   	    $LS_CHECK_PAGE = 1;
   	    $PAGE_SHOWTITLE = 1;
   	}

// if page not found
if ($LS_CHECK_PAGE == 0) {
    ls_redirect(LS_rewrite::lsParseurl('404', '', '', '', ''));
}

// Finally close all db connections
$lsdb->ls_close();
?>