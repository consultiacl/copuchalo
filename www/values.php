<?php
// Developed by @Hass, 2009
include('config.php');
include(mnminclude.'html1.php');

/*
if (!$current_user->admin) {
	do_error(_('acceso prohibido'), 403);
	die();
}
*/

function print_time($secs) {
	if ( $secs < 60 ) return $secs . ' ' . _("segundos");
	elseif ( $secs == 60 ) return "1" . ' ' . _("minuto");
	elseif ( $secs % 60 == 0 && $secs < 3600) return ($secs / 60) . ' ' ._("minutos");
	elseif ( $secs == 3600) return "1" . ' ' . _("hora");
	elseif ( $secs % 3600 == 0 && $secs < 86400) return ($secs / 3600) . ' ' ._("horas");
	elseif ( $secs == 86400) return "1" . ' ' . _("día");
	elseif ( $secs % 86400 == 0 ) return ($secs / 86400) . ' ' ._("días");
	else return $secs . ' ' . _("segundos");
}

do_header(_('Información sobre valores de karma y límites') . ' | ' . _('mediatize'));
echo '<div id="singlewrap">'."\n";


echo '
<div style="text-align:center">
	<br/>
	<h2>'._("Información sobre valores de karma y límites").'</h2>
	<br/>
</div>
';



echo '<fieldset id="karma"><legend>'._('karma').'</legend>';
echo _("El karma de un usuario puede ir de").' '.$globals['min_karma'].' '._("a").' '.$globals['max_karma'].'<br/>
				<br/>
				'._("El karma base va de").' '.$globals['karma_base'] .' '._("a").' '.$globals['karma_base_max'] .'. '. _("Aumentando a una velocidad de 1 por año, a contar desde la fecha de la primera noticia publicada en portada de ese usuario") .'<br/>
				<br/>
				'._("Un usuario normal obtiene el estado 'special' cuando su karma").' > '.$globals['special_karma_gain'].' '._("y lo pierde cuando su karma"). ' &lt; '.$globals['special_karma_loss'] . '<br/>
				<br/>
				';

if($globals['min_karma_for_links']) {
				echo _("Karma mínimo para enviar historias") . ': ' . $globals['min_karma_for_links'] . '<br/>
				<br/>
				';
}

if($globals['min_karma_for_comments']) {
				echo _("Karma mínimo para enviar comentarios") . ': '. $globals['min_karma_for_comments'] . '<br/>
				<br/>
				';
}

if($globals['min_karma_for_report_comments']) {
	echo _("Karma mínimo para reportar comentarios") . ': '. $globals['min_karma_for_report_comments'] . '<br/>
				<br/>
				';
}

if($globals['min_karma_for_comment_votes']) {
				echo _("Karma mínimo para votar comentarios") . ': ' . $globals['min_karma_for_comment_votes'] . '<br/>
				<br/>
				';
}

if($globals['min_karma_for_posts']) {
				echo _("Karma mínimo para enviar postits") . ': ' . $globals['min_karma_for_posts'] . '<br/>
				<br/>
				';
}

if($globals['min_karma_for_sneaker']) {
				echo _("Karma mínimo para hablar en la fisgona") . ': ' . $globals['min_karma_for_sneaker'] . '<br/>
				<br/>
				';
}

echo				_("Karma instantáneo ganado por un usuario en el momento que se publica uno de sus envíos") . ': ' . $globals['instant_karma_per_published'] . '<br/>
				<br/>
				'._("Karma instantáneo perdido por un usuario en el momento que se de-publica uno de sus envíos") . ': ' . $globals['instant_karma_per_depublished'] . '<br/>
				<br/>
				'._("Karma instantaneo perdido por un usuario en el momento que se descarta uno de sus envíos") . ': ' . $globals['instant_karma_per_discard'] . '<br/>
				<br/>
				'._("Karma que se añade al cálculo diario de karma por cada envío publicado") . ': ' . $globals['karma_points_per_published'] . ' (' . _("hasta un máximo de") . ' ' . $globals['karma_points_per_published_max'] . ')<br/>
		</fieldset>';



echo '
		<fieldset id="comments">
			<legend>'._('comentarios').'</legend>
				'._("Tiempo para editar un comentario") . ': ' . print_time( $globals['comment_edit_time'] ) . '<br/>
				<br/>
				'._("Karma a partir del cual se destacan los comentarios") . ': ' . $globals['comment_highlight_karma'] . '<br/>
				<br/>
				'._("Karma a partir del cual se ocultan los comentarios") . ': ' . $globals['comment_hidden_karma'] . '<br/>
				<br/>
				'._("Límite de comentarios por historia") . ': ' . $globals['max_comments'] . '<br/>
				<br/>
				'._("Tiempo que permanecen abiertos los comentarios en historias en portada") . ': ' . print_time($globals['time_enabled_comments']) . '<br/>
				<br/>
				'._("Tiempo que permanecen abiertos los comentarios en historias pendientes") . ': ' . print_time($globals['time_enabled_comments_status']['queued']) . '<br/>
				<br/>
				'._("Tiempo que permanecen abiertos los comentarios en historias descartados") . ': ' . print_time($globals['time_enabled_comments_status']['discard']) . '<br/>
				<br/>
				'._("Tiempo que permanecen abiertos los comentarios en historias autodescartados") . ': ' . print_time($globals['time_enabled_comments_status']['autodiscard']) . '<br/>
				<br/>
				'._("Tiempo que permanecen abiertos los comentarios en historias descartados por abuso") . ': ' . print_time($globals['time_enabled_comments_status']['abuse']) . '<br/>
				<br/>
				'._("Número máximo de reportes de comentarios en las últimas 24 horas") . ': ' . $globals['max_reports_for_comments'] . '<br/>
				<br/>				
				'._("Tiempo que debe pasar desde el registro para que un nuevo usuario pueda comentar") . ': ' . print_time($globals['min_time_for_comments']) . '<br/>
		</fieldset>';






echo '
		<fieldset id="votes">
			<legend>'._('votos abiertos').'</legend>
				'._("Tiempo que permanecen abiertos los votos") . ': ' . print_time($globals['time_enabled_votes']) . '<br/>
		</fieldset>';






echo '
		<fieldset id="links">
			<legend>'._('envíos').'</legend>
				'._("Límite de envíos global para usuarios con karma") . ' &lt;= ' . $globals['limit_3_minutes_karma'] . ' ('. _('se han enviado demasiadas historias en los últimos 3 minutos') ."): " . $globals['limit_3_minutes'] . ' ' . _("historias cada 3 minutos") . '<br/>
				<br/>
				'._("Límite de envíos global para usuarios con karma") . ' > ' . $globals['limit_3_minutes_karma'] .' ('. _('se han enviado demasiadas historias en los últimos 3 minutos') ."): " . ($globals['limit_3_minutes'] * 1.5) . ' ' . _("votos cada 3 minutos") . '<br/>
				<br/>
				'._("Límite de envíos por usuario en las últimas 24 horas") . ' (' . _('debes esperar, tienes demasiadas noticias en cola de las últimas 24 horas') . "): " .$globals['limit_user_24_hours'] . ' ' ._("envíos") .'<br/>
				<br/>
				'._("Límite de envíos por usuario al mismo sitio en las últimas 24 horas") . ' (' . _('ya has enviado un enlace al mismo sitio hace poco tiempo') . "): " .$globals['limit_same_site_24_hours'] . ' ' ._("envíos") .'<br/>
				<br/>
				'._("Tiempo que tarda un borrador en eliminarse automáticamente") . ': ' . print_time($globals['draft_time']) . '<br/>
				<br/>
				'._("Máximo de borradores por usuario") . ' (' . _('has hecho demasiados intentos, debes esperar o continuar con ellos desde la') . " " . _('cola de descartadas') . '): ' . $globals['draft_limit'] . '<br/>
				<br/>
				'._("Porcentaje de karma de historia que sufre un «depublish» y vuelve a pendientes"). ': ' . intval(100 / $globals['depublish_karma_divisor']) . '<br/>
		</fieldset>';





echo '
		<fieldset  id="register">
			<legend>'._('registros').'</legend>
				'._("Los nombres de usuario deben ser de 3 o más caracteres y comenzar por una letra") . '<br/>
				<br/>
				'._("Las contraseñas deben ser de 8 o más caracteres e incluir mayúsculas, minúsculas y números") .'<br/>
				<br/>
				'._("Registros desde la misma IP") . ': ' . _("para registrar otro usuario desde la misma dirección debes esperar 24 horas") . '<br/>
				<br/>
				'._("Registros desde la misma subred") . ' (xxx.yyy.zzz.*): ' . _("para registrar otro usuario desde la misma red debes esperar 6 horas") . '<br/>
				<br/>
				'._("Registros desde la misma subred") . ' (xxx.yyy.*.*): ' . _("para registrar otro usuario desde la misma red debes esperar una hora") . ')<br/>
		</fieldset>';




echo '
		<fieldset id="posts">
			<legend>'._('postits').'</legend>
				'._("Karma a partir del cual se destacan los postits") . ': ' . $globals['post_highlight_karma'] . '<br/>
				<br/>
				'._("Karma a partir del cual se ocultan los postits") . ': ' . $globals['post_hide_karma'] . '<br/>
				<br/>
				'._("Tiempo de espera entre postits") . ': ' . print_time($globals['posts_period']) . '<br/>
				<br/>
				'._("Tiempo de edición de postits") . ': ' . print_time($globals['posts_edit_time']) . '<br/>
				<br/>
				'._("Tiempo de edición de postits siendo admin") . ': ' .print_time($globals['posts_edit_time_admin']) . '<br/>
				<br/>
				'._("El karma que gana o pierde un usuario por los votos a sus postits es") . ' ' . ($globals['comment_votes_multiplier'] / $globals['post_votes_multiplier']) . ' ' . _("veces menor al karma que hubiese conseguido si esos mismos votos fuesen a sus comentarios") . '<br/>
		</fieldset>';

echo '
		<fieldset id="images">
			<legend>'._('imágenes en comentarios y postits').'</legend>
				'._("Karma mínimo") . ': ' . $globals['media_min_karma'] . '<br/>
				<br/>
				'._("Tamaño máximo en bytes") . ': ' . $globals['media_max_size'] . '<br/>
				<br/>
				'._("Imágenes por día") . ': ' . $globals['media_max_upload_per_day'] . '<br/>
				<br/>
				'._("Bytes totales por día") . ': ' . $globals['media_max_bytes_per_day'] . '<br/>
		</fieldset>';



echo '
		<fieldset id="formulas">
			<legend>'._('fórmulas').'</legend>'
				 ._("Se considera «nuevo usuario» a los usuarios que no hayan enviado ninguna historia o se hayan registrado hace menos de "). print_time($globals['new_user_time']) . '<br/>
				<br/>';
if ($globals['min_user_votes']) {

$total_links = (int) $db->get_var("select count(*) from links where link_date > date_sub(now(), interval 24 hour) and link_status = 'queued'");


echo '
				'._("Un «nuevo usuario» con karma &lt; "). $globals['new_user_karma'] ._(" y sin envíos deberá votar ") . $globals['min_user_votes'] . _(" votos antes de poder enviar").'<br/>
				<br/>
				'._("Un «nuevo usuario» con karma &lt; "). $globals['new_user_karma'] . _(" y con envíos deberá votar (cifra dinámica) ") . min(4, intval($total_links/20)) . _(" * (1 + nº de envíos del usuario en las últimas 24 h. que no estén en estado «discard») votos para poder enviar") . '<br/>
				<br/>';
}

echo '
				'._("Un «nuevo usuario» solo podrá enviar ") . $globals['new_user_links_limit'] . ' ' .  _("historias cada") . ' ' .print_time($globals['new_user_links_interval']) . ' ('._('debes esperar, ya se enviaron varias con el mismo usuario o dirección IP'). ')<br/>
		</fieldset>';


if(($result = $db->get_results("select user_login, user_level from users where user_level in ('god','admin','blogger') order by user_level"))) {
	echo '<fieldset id="admins">
		<legend>'._('usuarios con privilegios').'</legend>
			<style type="text/css">
			table {
				border-collapse:separate;
			}
			th {
				text-align: center;
			}
			td {
				border:1px solid black;
				padding:5px;
			}
			</style>
		<table>
			<thead>
    			<tr>
			<th>Usuario</th>
			<th>Nivel</th>
			</tr>
			</thead>
			<tbody>';
		foreach($result as $idx=>$object) {
			echo "<tr><td>".$object->user_login."</td><td>".$object->user_level."</td></tr>";
		}
	echo '</tbody></table></fieldset>';
}

echo '</div>';

do_footer_menu();
do_footer();

