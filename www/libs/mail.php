<?php
// The source code packaged with this file is Free Software, Copyright (C) 2005 by
// Ricardo Galli <gallir at uib dot es>.
// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
//      http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".

error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT);
set_include_path("." . PATH_SEPARATOR . "/usr/share/pear" . PATH_SEPARATOR . get_include_path());
//set_include_path("." . PATH_SEPARATOR . ($UserDir = dirname($_SERVER['DOCUMENT_ROOT'])) . "/pear/share/pear" . PATH_SEPARATOR . get_include_path());
//set_include_path(get_include_path());
require_once "Mail.php";
require_once "Mail/mime.php";
//$pear_user_config = $UserDir . "/.pearrc";

// Send a mail
function send_mail($to, $subject, $message) {
	global $globals;

	if (! check_email($to)) return false;

	if (! empty($globals['email_domain'])) $domain = $globals['email_domain'];
	else $domain = get_server_name();

	$subject = "$domain: $subject";

	return send_pear_mail($to, $domain, $subject, $message);
}

// Send recovery mail
function send_recover_mail ($user) {
	global $site_key, $globals;

	if (! check_email($user->email)) return false;

	$now = time();

	if (! empty($globals['email_domain'])) $domain = $globals['email_domain'];
	else $domain = get_server_name();

	$key = md5($user->id.$user->pass.$now.$site_key.get_server_name());
	$url = 'https://'.get_server_name().$globals['base_url'].'profile?login='.$user->username.'&t='.$now.'&k='.$key;
	$to      = $user->email;
	$subject = _('Recuperación o verificación de clave de '). get_server_name();

	$url_recover = 'https://'.get_server_name().$globals['base_url'].'login?op=recover';

	$message  = '<html lang="es"><head><meta charset="utf-8"/></head><body><p>Hola '.$to.":</p>\n\n";
	$message .= "<p>Para poder acceder sin la clave, conéctate a la siguiente dirección en menos de 15 minutos:<br>\n";
	$message .= '<a href="'.$url.'">'.$url."</a></p>\n\n";
	$message .= "<p>Pasado este tiempo puedes volver a solicitar acceso en:<br>\n";
	$message .= '<a href="'.$url_recover.'">'.$url_recover."</a></p>\n\n";
	$message .= "<p>Una vez en tu perfil, puedes cambiar la clave de acceso.</p>\n";
	$message .= '<p>Este mensaje ha sido enviado bajo solicitud desde la dirección IP: '.$globals['user_ip']."<p>\n\n";
	$message .= "<p>--\n <br>el equipo de mediatize<p><br></body></html>";
	
	if(send_pear_mail($to, $domain, $subject, $message)) {
		echo '<p><strong>' ._('Correo enviado, mira tu buzón, allí están las instrucciones. Mira también en la carpeta de spam.') . '</strong></p>';
		return true;
	} else {
		echo '<p><strong>' ._('Error en el envío de correo. Prueba más tarde o avisa al administrador.') . '</strong></p>';
		return false;
	}
}

// Uses Mail pear module to send mails
function send_pear_mail($to, $domain, $subject, $message) {
	global $globals;

	if( isset($globals['email_external']) && $globals['email_external'] === true && (empty($globals['email_server']) || empty($globals['email_port']) || empty($globals['email_user']) || empty($globals['email_passwd'])) ) {
		syslog(LOG_ERR, "Error enviando correo: algunas variables vacías para el envío de correo con servidor externo, funcion send_pear_mail");
		return false;
	}

	$from_user = "Avisos: $domain";
	$from_email = "no-responder@$domain";
	$reply_to = $from_email;
	$mailer = $domain;

	$text = clean_string($message);

	$headers = array ('From' => "$from_user <$from_email>",
                          'To' => $to,
                          'Subject' => $subject,
                          'Reply-To' => $reply_to,
                          'Return-Path' => $from_mail,
                          'X-Mailer' => $mailer,
	);

	$mimeoptions = array ('eol' => "\n",
			      'head_charset' => "UTF-8",
			      'text_charset' => "UTF-8",
			      'html_charset' => "UTF-8",
	);

	// Creating the Mime message
	$mime = new Mail_mime($mimeoptions);

	// Setting the body of the email
	$mime->setTXTBody($text);
	$mime->setHTMLBody($message);

	$body = $mime->get();
	$headers = $mime->headers($headers);

	// Sending the email
	if( isset($globals['email_external']) && $globals['email_external'] === true ) {
		$smtp =& Mail::factory('smtp', array('host' => $globals['email_server'], 'port' => $globals['email_port'], 'auth' => true, 'username' => $globals['email_user'], 'password' => $globals['email_passwd']));
	} else {
		$smtp =& Mail::factory('mail', array());
	}
	$mail = $smtp->send($to, $headers, $body);

	if (PEAR::isError($mail)) {
		syslog(LOG_ERR, "Error enviando correo: " . $mail->getMessage());
		return false;
	} else {
		return true;
	}
}

