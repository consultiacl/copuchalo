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
	$url = 'http://'.get_server_name().$globals['base_url'].'profile?login='.$user->username.'&t='.$now.'&k='.$key;
	$to      = $user->email;
	$subject = _('Recuperación o verificación de contraseña de '). get_server_name();
	$message = $to . ': '._('para poder acceder sin la clave, conéctate a la siguiente dirección en menos de 15 minutos:') . "</br>\n" . '<a href="'.$url.'" target="_blank">'.$url."</a></br></br>\n";
	$url_recover = "http://".get_server_name().$globals['base_url']."login?op=recover";
	$message .= _('Pasado este tiempo puedes volver a solicitar acceso en: ') . "</br>\n" . '<a href="'.$url_recover.'" target="_blank">'.$url_recover."</a></br></br>\n\n";
	$message .= _('Una vez en tu perfil, puedes cambiar la clave de acceso.') . "</br></br>\n";
	$message .= _('Este mensaje ha sido enviado bajo solicitud desde la dirección: ') . $globals['user_ip'] . "</br>\n\n";
	$message .= "-- </br>\n  " . _('el equipo de copúchalo');
	
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

	if (empty($globals['email_server']) || empty($globals['email_port']) || empty($globals['email_user']) || empty($globals['email_passwd'])) {
		syslog(LOG_ERR, "Error enviando correo: algunas variables vacías, funcion send_pear_mail");
		return false;
	}

	$host = $globals['email_server'];
	$port = $globals['email_port'];
	$username = $globals['email_user'];
	$password = $globals['email_passwd'];

	$from_user = "Avisos: $domain";
	$from_email = "no-responder@$domain";
	$reply_to = $from_email;
	$mailer = $domain;

	$message = wordwrap($message, 70);
	$from_user = "=?UTF-8?B?".base64_encode($from_user)."?=";
	$subject = "=?UTF-8?B?".base64_encode($subject)."?=";

	$headers = array ('From' => "$from_user <$from_email>",
        	          'To' => $to,
                	  'Subject' => $subject,
	                  'Reply-To' => $reply_to,
        	          'MIME-Version' => "1.0",
                	  'X-Mailer' => $mailer,
	                  'Content-type' => "text/html; charset=UTF-8");

	$smtp = Mail::factory('smtp', array ('host' => $host, 'port' => $port, 'auth' => true, 'username' => $username, 'password' => $password));  //, 'debug' => true));
	$mail = $smtp->send($to, $headers, $message);

	if (PEAR::isError($mail)) {
		syslog(LOG_ERR, "Error enviando correo: ".$mail->getMessage());
		return false;
	} else {
	        return true;
	}
}

