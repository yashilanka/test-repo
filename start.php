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

// Check if the file is accessed only via index.php if not stop the script from running
if (!defined('LS_PREVENT_ACCESS')) {
die('You cannot access this file directly.');
}

// buffer flush
ob_start();

// Start the session
session_start();

if (isset($_SESSION['guest_userid']) && isset($_SESSION['convid'])) {
	ls_redirect(LS_rewrite::lsParseurl('chat', '', '', '', ''));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['start_chat'])) {
		$defaults = $_POST;
		
		// Errors in Array
		$errors = array();
		
		if (empty($defaults['name'])) {
		    $errors['name'] = $tl['error']['e'];
		}
		
		if (!empty($defaults['email']) && !filter_var($defaults['email'], FILTER_VALIDATE_EMAIL)) {
		    $errors['email'] = $tl['error']['e1'];
		}
				
		if (count($errors) > 0) {
			
			/* Outputtng the error messages */
			if ($_SERVER['HTTP_X_REQUESTED_WITH']) {
			
				header('Cache-Control: no-cache');
				echo '{"status":0, "errors":'.json_encode($errors).'}';
				exit;
				
			} else {
			
				$errors = $errors;
			}
			
		} else {
			
			$ipa = get_ip_address();
			$salt = rand(100, 999);
			$userid = $defaults['name'].$ipa.$salt;
			$_SESSION['guest_name'] = filter_var($defaults['name'], FILTER_SANITIZE_STRING);
			$_SESSION['guest_userid'] = $userid;
			
			if(!empty($defaults['email'])) {
				$_SESSION['guest_email'] = filter_var($defaults['email'], FILTER_SANITIZE_EMAIL);
			} else {
				$_SESSION['guest_email'] = $tl['general']['g12'];
			}
				if(isset($defaults['contactme'])) {
					$contactme = "yes";
				} else {
					$contactme = "no";
				}
			
			// add entry to sql
			$result = $lsdb->query('INSERT INTO '.DB_PREFIX.'sessions SET 
			userid = "'.smartsql($userid).'",
			name = "'.smartsql($_SESSION['guest_name']).'",
			email = "'.smartsql($_SESSION['guest_email']).'",
			initiated = "'.time().'",
			status = "open",
			contact = "'.$contactme.'"'); 
						
			if ($result) {
				
				$cid = $lsdb->ls_last_id();
				
				$_SESSION['convid'] = $cid;
				
				$lsdb->query('UPDATE '.DB_PREFIX.'sessions SET convid = "'.$cid.'" WHERE userid = "'.smartsql($_SESSION['guest_userid']).'"');
				
				$lsdb->query('INSERT INTO '.DB_PREFIX.'transcript SET 
				name = "Admin",
				message = "'.smartsql(LS_WELCOME_MESSAGE).'",
				convid = "'.$cid.'",
				time = NOW(),
				class = "admin"');
				
			}
			
			// Redirect page
			$gochat = LS_rewrite::lsParseurl('chat', '', '', '', '');
			
			/* Outputtng the error messages */
			if ($_SERVER['HTTP_X_REQUESTED_WITH']) {
			
				header('Cache-Control: no-cache');
				echo json_encode(array('login' => 1, 'link' => $gochat));
				exit;
				
			}
			
			ls_redirect($gochat);
			
		}
}	
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title><?php echo $tl["general"]["g"];?> - <?php echo LS_TITLE;?></title>
	<meta charset="utf-8">
	<meta name="author" content="Live Support Rhino" />
	<link rel="shortcut icon" href="<?php echo BASE_URL;?>favicon.ico" type="image/x-icon" />
	<link rel="stylesheet" href="<?php echo BASE_URL;?>css/style.css" type="text/css" media="screen" />
	<script type="text/javascript" src="<?php echo BASE_URL;?>js/jquery.js"></script>
	<script type="text/javascript" src="<?php echo BASE_URL;?>js/functions.js"></script>
	<script type="text/javascript" src="<?php echo BASE_URL;?>js/contact.js"></script>
	
	<!--[if lt IE 9]>
	<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	 <![endif]-->
	 
</head>
<body>

<!--- Container -->
		<div class="container">
			<h1 class="heading_solid"><img src="img/logo.png" alt="logo" /> <?php echo $tl["general"]["g"];?> - <?php echo LS_TITLE;?></h1>
		
		<p><?php echo LS_LOGIN_MESSAGE;?></p>
		
		<?php if ($errors) { ?>
		<div class="status-failure"><?php echo $errors["name"].$errors["email"];?></div>
		<?php } ?>
				
		<div id="contact-container" class="centered_container pale_blue login">
		
		<form id="cSubmit" method="post" action="<?php echo $_SERVER['REQUEST_URI'];?>" >
		
			<label for="name"><?php echo $tl["general"]["g4"];?></label>
				<input type="text" name="name" id="name" class="input_field thin" placeholder="<?php echo $tl["general"]["g4"];?>" />
				
			<label for="email"><?php echo $tl["general"]["g5"];?></label>
				<input type="text" name="email" id="email" class="input_field thin" placeholder="<?php echo $tl["general"]["g5"];?>"><br />
				
			<label for="contactme"><?php echo $tl["general"]["g9"];?></label>
				<input type="checkbox" name="contactme" id="contactme">
				
			<input type="submit" id="formsubmit" class="input_field submit" value="<?php echo $tl["general"]["g10"];?>" />
			
			<input type="hidden" name="start_chat" value="1" />
			
		</form>
		</div>
		
		<!-- Do not remove copyright, except you paid for it -->
		<div class="centered_container pale_blue copyright"><a href="http://www.livesupportrhino.com">Live Support powered by Rhino</a></div>
		
		</div>
		
		<script type="text/javascript">
			$("#name").focus();
			ls.main_url = "<?php echo BASE_URL;?>";
			ls.lsrequest_uri = "<?php echo LS_PARSE_REQUEST;?>";
			ls.ls_submit = "<?php echo $tl['general']['g10'];?>";
			ls.ls_submitwait = "<?php echo $tl['general']['g8'];?>";
		</script>
</body>
</html>
<?php ob_flush(); ?>