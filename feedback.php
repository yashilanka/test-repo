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

if (empty($_SESSION['guest_userid'])) {
	ls_redirect(LS_rewrite::lsParseurl('start', '', '', '', ''));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_feedback'])) {
		$defaults = $_POST;
		
		// session
		$_SESSION['ls_fb_sent'] = -1;
		
		// Errors in Array
		$errors = array();
		
		if ($defaults['email'] != '' && !filter_var($defaults['email'], FILTER_VALIDATE_EMAIL)) {
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
		
			if (!empty($defaults['convid']) && is_numeric($defaults['convid'])) {
			
				// check to see if conversation is to be stored
				$result = $lsdb->query('SELECT convid, name, email, contact FROM '.DB_PREFIX.'sessions WHERE convid = "'.smartsql($defaults['convid']).'"');
				
				if ($lsdb->affected_rows > 0) {
				
					$row = $result->fetch_assoc();
			
					$lsdb->query('UPDATE '.DB_PREFIX.'sessions SET status = "closed", ended = "'.time().'"  WHERE convid = "'.$row['convid'].'"');
					
					$result = $lsdb->query('INSERT INTO '.DB_PREFIX.'transcript SET 
					name = "'.smartsql($_SESSION['guest_name']).'",
					message = "'.smartsql($tl['general']['g16']).'",
					user = "'.smartsql($_SESSION['guest_userid']).'",
					convid = "'.$row['convid'].'",
					time = NOW(),
					class = "notice"');
							
					archive_conversation($row['convid'], $row['name'], $row['email']);
				
				}
			}
		
			$listform = $tl["general"]["g27"].': '.$defaults['name'].'<br />';
			if ($defaults['message']) {
				$listform .= $tl["general"]["g24"].': '.$defaults['message'].'<br />';
			} else {
				$listform .= $tl["general"]["g24"].': '.$tl["general"]["g12"].'<br />';
			}
			$listform .= $tl["general"]["g29"].': '.$defaults['fbvote'].'/5';
			
			$result1 = $lsdb->query('SELECT user FROM '.DB_PREFIX.'transcript WHERE convid = "'.smartsql($defaults['convid']).'" AND class = "admin" AND user != "" LIMIT 1');
			if ($lsdb->affected_rows > 0) {
				$row1 = $result1->fetch_assoc();
				$operator = explode("::", $row1['user']);
			} else {
				$operator = 0;
			}
			
			$name = filter_var($defaults['name'], FILTER_SANITIZE_STRING);
			$email = filter_var($defaults['email'], FILTER_SANITIZE_EMAIL);
			$message = filter_var($defaults['message'], FILTER_SANITIZE_STRING);
			
			// Now get the support time
			$result2 = $lsdb->query('SELECT initiated, ended FROM '.DB_PREFIX.'sessions WHERE convid = "'.smartsql($defaults['convid']).'"');
			$row2 = $result2->fetch_assoc();
			
			$total_supporttime = $row2['ended'] - $row2['initiated'];
			
			// Write stuff into the user stats
			$lsdb->query('INSERT INTO '.DB_PREFIX.'user_stats SET
			userid = "'.smartsql($operator[0]).'",
			vote = "'.smartsql($defaults['fbvote']).'",
			name = "'.smartsql($name).'",
			email = "'.smartsql($email).'",
			comment = "'.smartsql($message).'",
			support_time = "'.$total_supporttime.'",
			time = NOW()');
		
		
			$mail = new PHPMailer(); // defaults to using php "mail()"
			if ($email) {
				$mail->SetFrom($email, utf8_decode($name));
			} else { 
				$mail->SetFrom(LS_EMAIL, "no-reply");
			}
			$mail->AddAddress(LS_EMAIL, utf8_decode(LS_TITLE));
			$mail->Subject = utf8_decode($tl["general"]["g24"]);
			$mail->MsgHTML(utf8_decode($listform));
			
			if ($mail->Send()) {
				
				// Ajax Request
				if ($_SERVER['HTTP_X_REQUESTED_WITH']) {
					
					$thankyou = LS_THANKYOU_FEEDBACK.'<p><a href="javascript:window.close();"><span class="red">'.$tl["general"]["g3"].'</span></a></p>';
				
					header('Cache-Control: no-cache');
					echo json_encode(array('status' => 1, 'html' => $thankyou));
					session_destroy();
					exit;
					
				} else {
				
					session_destroy();
				
			        $_SESSION['ls_fb_sent'] = 1;
			        jak_redirect($_SERVER['HTTP_REFERER']);
			        exit;
			    
			    }
			}
		}
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title><?php echo $tl["general"]["g24"];?> - <?php echo LS_TITLE;?></title>
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
			
			<div id="window-close" class="logout"><a href="<?php echo LS_rewrite::lsParseurl('stop', $_SESSION['convid'], '', '', '');?>" class="red small_text"><?php echo $tl["general"]["g3"];?></a></div>
			
		<?php if ($errors) { ?>
			<div class="status-failure"><?php echo $errors["name"].$errors["email"];?></div>
			<?php } ?>
		
				
		<?php if ($_SESSION['ls_msg_sent'] == 1) { ?>
			
			<p class="status-ok"><?php echo LS_THANKYOU_FEEDBACK;?></p>
			<p><a href="javascript:window.close();"><span class="red"><?php echo $tl["general"]["g3"];?></span></a></p>
		
		<?php } else { ?>
		
		<div id="thank-you"></div>
			
		<p id="feedback-message"><?php echo LS_FEEDBACK_MESSAGE;?></p>
			
		<div id="contact-container" class="centered_container pale_blue contact">
				
		<!--- Chat Rating -->
		<form id="cSubmit" action="<?php echo $_SERVER['REQUEST_URI'];?>" method="post">
		
					<label for="message"><?php echo $tl["general"]["g23"];?></label>
					<div class="rate-result">Rating: <span id="stars-cap"></span></div>
						<div id="starify">
							<label for="vote1"><input type="radio" name="fbvote" id="vote1" value="1" title="Poor" /> Poor</label>
							<label for="vote2"><input type="radio" name="fbvote" id="vote2" value="2" title="Fair" /> Fair</label>
							<label for="vote3"><input type="radio" name="fbvote" id="vote3" value="3" title="Average" checked="checked" /> Average</label>
							<label for="vote4"><input type="radio" name="fbvote" id="vote4" value="4" title="Good" /> Good</label>
							<label for="vote5"><input type="radio" name="fbvote" id="vote5" value="5" title="Excellent" /> Excellent</label>
						</div>
						
					<div class="clear"></div>
					
					<label for="name"><?php echo $tl["general"]["g4"].$tl["general"]["g26"];?></label>
						<input type="text" name="name" id="name" class="input_field thin" value="<?php echo $_SESSION['guest_name'];?>" />
						
					<label for="email"><?php echo $tl["general"]["g5"].$tl["general"]["g26"];?></label>
						<input type="text" name="email" id="email" class="input_field thin" />
						
					<label for="message"><?php echo $tl["general"]["g24"].$tl["general"]["g26"];?></label>
						<textarea name="message" id="message" class="input_field" rows="4" cols="40"></textarea>
						
					<input type="hidden" name="send_feedback" value="1" />
					<input type="hidden" name="convid" value="<?php echo $page1;?>" />
		
					<input type="submit" id="formsubmit" class="input_field submit" value="<?php echo $tl["general"]["g25"];?>" />
				
			</form>
			
			</div>
			<?php } ?>
			
		<!-- Do not remove copyright, except you paid for it -->
		<div class="centered_container pale_blue copyright"><a href="http://www.livesupportrhino.com">Live Support powered by Rhino</a></div>
		
		</div>
		
		<script type="text/javascript">
				$(function(){
					$("#starify").children().not(":input").hide();
					
					// Create stars from :radio boxes
					$("#starify").stars({
						cancelShow: false,
						captionEl: $("#stars-cap")
					});
				});
				
			$("#name").focus();
			ls.main_url = "<?php echo BASE_URL;?>";
			ls.lsrequest_uri = "<?php echo LS_PARSE_REQUEST;?>";
			ls.ls_submit = "<?php echo $tl['general']['g10'];?>";
			ls.ls_submitwait = "<?php echo $tl['general']['g8'];?>";
		</script>
</body>
</html>

<?php ob_flush(); ?>