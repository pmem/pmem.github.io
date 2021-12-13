<?php

/*-------------------------------------------------

	Form Processor Plugin
	by SemiColonWeb

---------------------------------------------------*/


/*-------------------------------------------------
	PHPMailer Initialization
---------------------------------------------------*/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';


/*-------------------------------------------------
	Receiver's Email
---------------------------------------------------*/

$toemails = array();

$toemails[] = array(
				'email' => 'your-email@website.com', // Your Email Address
				'name' => 'Your Name' // Your Name
			);


/*-------------------------------------------------
	Sender's Email
---------------------------------------------------*/

$fromemail = array(
				'email' => 'no-reply@website.com', // Company's Email Address (preferably currently used Domain Name)
				'name' => 'Company Name' // Company Name
			);


/*-------------------------------------------------
	reCaptcha
---------------------------------------------------*/

// Add this only if you use reCaptcha with your Contact Forms
$recaptcha_secret = ''; // Your reCaptcha Secret


/*-------------------------------------------------
	PHPMailer Initialization
---------------------------------------------------*/

$mail = new PHPMailer();

/* Add your SMTP Codes after this Line */


// End of SMTP


/*-------------------------------------------------
	Form Messages
---------------------------------------------------*/

$message = array(
	'success'			=> 'We have <strong>successfully</strong> received your Message and will get Back to you as soon as possible.',
	'error'				=> 'Email <strong>could not</strong> be sent due to some Unexpected Error. Please Try Again later.',
	'error_bot'			=> 'Bot Detected! Form could not be processed! Please Try Again!',
	'error_unexpected'	=> 'An <strong>unexpected error</strong> occured. Please Try Again later.',
	'recaptcha_invalid'	=> 'Captcha not Validated! Please Try Again!',
	'recaptcha_error'	=> 'Captcha not Submitted! Please Try Again.'
);


/*-------------------------------------------------
	Blocked Words from Forms
---------------------------------------------------*/

$spam_keywords = array(
	'viagra',
	'cialis',
	'levitra'
);


/*-------------------------------------------------
	Form Processor
---------------------------------------------------*/

if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {

	$prefix		= !empty( $_POST['prefix'] ) ? $_POST['prefix'] : '';
	$submits	= $_POST;
	$botpassed	= false;


	$message_form					= !empty( $submits['message'] ) ? $submits['message'] : array();
	$message['success']				= !empty( $message_form['success'] ) ? $message_form['success'] : $message['success'];
	$message['error']				= !empty( $message_form['error'] ) ? $message_form['error'] : $message['error'];
	$message['error_bot']			= !empty( $message_form['error_bot'] ) ? $message_form['error_bot'] : $message['error_bot'];
	$message['error_unexpected']	= !empty( $message_form['error_unexpected'] ) ? $message_form['error_unexpected'] : $message['error_unexpected'];
	$message['recaptcha_invalid']	= !empty( $message_form['recaptcha_invalid'] ) ? $message_form['recaptcha_invalid'] : $message['recaptcha_invalid'];
	$message['recaptcha_error']		= !empty( $message_form['recaptcha_error'] ) ? $message_form['recaptcha_error'] : $message['recaptcha_error'];


	/*-------------------------------------------------
		Bot Protection
	---------------------------------------------------*/

	if( isset( $submits[ $prefix . 'botcheck' ] ) ) {
		$botpassed = true;
	}

	if( !empty( $submits[ $prefix . 'botcheck' ] ) ) {
		$botpassed = false;
	}

	if( $botpassed == false ) {
		echo '{ "alert": "error", "message": "' . $message['error_bot'] . '" }';
		exit;
	}


	/*-------------------------------------------------
		SPAM Protection
	---------------------------------------------------*/

	function spam_keyword_check( $submitted, $spamwords ) {
		if( is_array( $submitted ) ) {
			return false;
		}
		if( !is_array( $spamwords ) ) $spamwords = array( $spamwords );
		foreach( $spamwords as $spamstring ) {
			if( ( $position = stripos( $submitted, $spamstring ) ) !== false ) return $position;
		}
		return false;
	}

	foreach( $submits as $spam_submit ) {
		if( spam_keyword_check( $spam_submit, $spam_keywords ) ) {
			// A successful message is displayed to the submitter that makes him think that the Form has been sent so that he cannot modify the keywords to prevent SPAM
			echo '{ "alert": "success", "message": "' . $message['success'] . '" }';
			exit;
		}
	}


	/*-------------------------------------------------
		reCaptcha
	---------------------------------------------------*/

	if( isset( $submits['g-recaptcha-response'] ) ) {

		$recaptcha_data = array(
			'secret' => $recaptcha_secret,
			'response' => $submits['g-recaptcha-response']
		);

		$recap_verify = curl_init();
		curl_setopt( $recap_verify, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify" );
		curl_setopt( $recap_verify, CURLOPT_POST, true );
		curl_setopt( $recap_verify, CURLOPT_POSTFIELDS, http_build_query( $recaptcha_data ) );
		curl_setopt( $recap_verify, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $recap_verify, CURLOPT_RETURNTRANSFER, true );
		$recap_response = curl_exec( $recap_verify );

		$g_response = json_decode( $recap_response );

		if ( $g_response->success !== true ) {
			echo '{ "alert": "error", "message": "' . $message['recaptcha_invalid'] . '" }';
			exit;
		}
	}

	$template	= !empty( $submits['template'] ) ? $submits['template'] : 'html';
	$html_title	= !empty( $submits['html_title'] ) ? $submits['html_title'] : 'Form Response';
	$forcerecap	= ( !empty( $submits['force_recaptcha'] ) && $submits['force_recaptcha'] != 'false' ) ? true : false;
	$replyto	= !empty( $submits['replyto'] ) ? explode( ',', $submits['replyto'] ) : false;

	if( $forcerecap ) {
		if( !isset( $submits['g-recaptcha-response'] ) ) {
			echo '{ "alert": "error", "message": "' . $message['recaptcha_error'] . '" }';
			exit;
		}
	}

	/*-------------------------------------------------
		Auto-Responders
	---------------------------------------------------*/

	$autores	= ( !empty( $submits['autoresponder'] ) && $submits['autoresponder'] != 'false' ) ? true : false;
	$ar_subject	= !empty( $submits['ar_subject'] ) ? $submits['ar_subject'] : 'Thanks for your Email';
	$ar_title	= !empty( $submits['ar_title'] ) ? $submits['ar_title'] : 'Its so good to hear from You!';
	$ar_message	= !empty( $submits['ar_message'] ) ? $submits['ar_message'] : 'Autoresponder Message';

	preg_match_all('#\{(.*?)\}#', $ar_message, $ar_matches);
	if( !empty( $ar_matches[1] ) ) {
		foreach( $ar_matches[1] as $ar_key => $ar_value ) {
			$ar_message = str_replace( '{' . $ar_value . '}' , $submits[ $ar_value ], $ar_message );
		}
	}

	$ar_footer	= !empty( $submits['ar_footer'] ) ? $submits['ar_footer'] : 'Copyrights &copy; ' . date('Y') . ' <strong>SemiColonWeb</strong>. All Rights Reserved.';

	$mail->Subject = !empty( $submits['subject'] ) ? $submits['subject'] : 'Form Response from your Website';
	$mail->SetFrom( $fromemail['email'] , $fromemail['name'] );

	if( !empty( $replyto ) ) {
		if( count( $replyto ) > 1 ) {
			$replyto_e = $submits[ $replyto[0] ];
			$replyto_n = $submits[ $replyto[1] ];
			$mail->AddReplyTo( $replyto_e , $replyto_n );
		} elseif( count( $replyto ) == 1 ) {
			$replyto_e = $submits[ $replyto[0] ];
			$mail->AddReplyTo( $replyto_e );
		}
	}

	foreach( $toemails as $toemail ) {
		$mail->AddAddress( $toemail['email'] , $toemail['name'] );
	}

	$unsets = array( 'prefix', 'subject', 'replyto', 'template', 'html_title', 'message', 'autoresponder', 'ar_subject', 'ar_title', 'ar_message', 'ar_footer', $prefix . 'botcheck', 'g-recaptcha-response', 'force_recaptcha', $prefix . 'submit' );

	foreach( $unsets as $unset ) {
		unset( $submits[ $unset ] );
	}

	$fields = array();

	foreach( $submits as $name => $value ) {

		if( empty( $value ) ) continue;

		$name = str_replace( $prefix , '', $name );
		$name = mb_convert_case( $name, MB_CASE_TITLE, "UTF-8" );

		if( is_array( $value ) ) {
			$value = implode( ', ', $value );
		}

		$fields[$name] = nl2br( $value );

	}

	$files = $_FILES;

	foreach( $files as $file => $filevalue ) {

		if( is_array( $filevalue['name'] ) ) {

			$filecount = count( $filevalue['name'] );

			for( $f = 0; $f < $filecount; $f++ ) {
				if ( isset( $_FILES[ $file ] ) && $_FILES[ $file ]['error'][ $f ] == UPLOAD_ERR_OK ) {
					$mail->IsHTML(true);
					$mail->AddAttachment( $_FILES[ $file ]['tmp_name'][ $f ], $_FILES[ $file ]['name'][ $f ] );
				}
			}

		} else {

			if ( isset( $_FILES[ $file ] ) && $_FILES[ $file ]['error'] == UPLOAD_ERR_OK ) {
				$mail->IsHTML(true);
				$mail->AddAttachment( $_FILES[ $file ]['tmp_name'], $_FILES[ $file ]['name'] );
			}

		}

	}

	$response = array();

	foreach( $fields as $fieldname => $fieldvalue ) {
		if( $template == 'text' ) {
			$response[] = $fieldname . ': ' . $fieldvalue;
		} else {
			$fieldname = '<tr>
								<td style="font-size: 16px; line-height: 24px; font-weight: bold; padding: 0 0 5px 0;" align="left">' . $fieldname . '</td>
							</tr>';
			$fieldvalue = '<tr>
								<td style="font-size: 16px; line-height: 24px; color: #777777; padding: 0 15px 30px 0;" align="left">' . $fieldvalue . '</td>
							</tr>';
			$response[] = $fieldname . $fieldvalue;
		}
	}

	$referrer = $_SERVER['HTTP_REFERER'] ? '<br><br><br>This Form was submitted from: ' . $_SERVER['HTTP_REFERER'] : '';

	$html_before = '<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" bgcolor="#eeeeee" style="width: 100%; height: 100%; padding: 50px 0 50px 0;">
				<tr>
					<td align="center" valign="top">
						<table border="0" cellpadding="0" cellspacing="0" width="84%" bgcolor="#ffffff" style="width: 84%;">
							<tr>
								<td align="center" valign="top">
									';

	$html_after = '</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>';

	if( $template == 'text' ) {
		$body = implode( "<br>", $response ) . $referrer;
	} else {
		$html = $html_before . '<!-- / Header -->
									<table border="0" cellpadding="0" cellspacing="0" width="84%" style="width: 84%;">
										<tr>
											<td style="padding: 30px 0 30px 0; border-bottom: solid 1px #eeeeee; font-size: 30px; font-weight: bold; text-decoration: none; color: #000000;" align="left">
												' . $html_title . '
											</td>
										</tr>
									</table>

									<!-- / Sub-Header -->
									<table border="0" cellpadding="0" cellspacing="0" width="84%" style="width: 84%; padding: 60px 0 30px 0;"">
										' . implode( '', $response ) . '
									</table>

									<!-- / Footer -->
									<table border="0" cellpadding="0" cellspacing="0" width="100%" align="center">
										<tr>
											<td align="center">
												<table border="0" cellpadding="0" cellspacing="0" width="84%" align="center" style="border-top: 1px solid #eeeeee; width: 84%;">
													<tr>
														<td style="color: #d5d5d5; text-align: center; font-size: 12px; padding: 30px 0 30px 0; line-height: 22px;">' . strip_tags( $referrer ) . '</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
									' . $html_after;

		$body = $html;
	}

	if( $autores && !empty( $replyto_e ) ) {
		$autoresponder = new PHPMailer();

		/* Add your Auto-Responder SMTP Codes after this Line */


		// End of Auto-Responder SMTP

		$autoresponder->SetFrom( $fromemail['email'] , $fromemail['name'] );
		if( !empty( $replyto_n ) ) {
			$autoresponder->AddAddress( $replyto_e , $replyto_n );
		} else {
			$autoresponder->AddAddress( $replyto_e );
		}
		$autoresponder->Subject = $ar_subject;

		$ar_body = $html_before . '<!-- / Header -->
					<table border="0" cellpadding="0" cellspacing="0" width="84%" style="width: 84%;">
						<tr>
							<td style="padding: 30px 0 30px 0; border-bottom: solid 1px #eeeeee; font-size: 30px; font-weight: bold; text-decoration: none; color: #000000;" align="left">
								' . $ar_title . '
							</td>
						</tr>
					</table>

					<!-- / Sub-Header -->
					<table border="0" cellpadding="0" cellspacing="0" width="84%" style="width: 84%; padding: 60px 0 30px 0;"">
						<tr>
							<td style="font-size: 16px; line-height: 26px; color: #777777; padding: 0 15px 30px 0;" align="left">' . $ar_message . '</td>
						</tr>
					</table>

					<!-- / Footer -->
					<table border="0" cellpadding="0" cellspacing="0" width="100%" align="center">
						<tr>
							<td align="center">
								<table border="0" cellpadding="0" cellspacing="0" width="84%" align="center" style="border-top: 1px solid #eeeeee; width: 84%;">
									<tr>
										<td style="color: #d5d5d5; text-align: center; font-size: 12px; padding: 30px 0 30px 0; line-height: 22px;">' . $ar_footer . '</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
					' . $html_after;

		$autoresponder->MsgHTML( $ar_body );
	}

	$mail->MsgHTML( $body );
	$mail->CharSet = "UTF-8";
	$sendEmail = $mail->Send();

	if( $sendEmail == true ):

		if( $autores && !empty( $replyto_e ) ) {
			$send_arEmail = $autoresponder->Send();
		}

		echo '{ "alert": "success", "message": "' . $message['success'] . '" }';
	else:
		echo '{ "alert": "error", "message": "' . $message['error'] . '<br><br><strong>Reason:</strong><br>' . $mail->ErrorInfo . '" }';
	endif;

} else {
	echo '{ "alert": "error", "message": "' . $message['error_unexpected'] . '" }';
}

?>