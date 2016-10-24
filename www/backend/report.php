<?php
// The source code packaged with this file is Free Software, Copyright (C) 2005 by
// Ricardo Galli <gallir at uib dot es>.
// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
// 		http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".

if (!defined('mnmpath')) {
	include(dirname(__FILE__) . '/../config.php');
	include(mnminclude . 'html1.php');
}

array_push($globals['cache-control'], 'no-cache');
http_cache();

if (!empty($_REQUEST['id']) && ($id = intval($_REQUEST['id'])) > 0 && $current_user->user_id > 0 && !empty($_REQUEST['type']) ) {
	$id = $_REQUEST['id'];
	$type = $_REQUEST['type'];
	if(!test_id_ok($id, $type)) die;
} else {
	die;
}

if ($_POST['process'] == 'newreport') {
	save_report($id, $type);
} elseif ($_POST['process'] == 'check_can_report') {

	if (!check_security_key($_POST['key'])) die;
	$res = check_report($id, $type);
	if (true === $res) {
		$data['html'] = '';
		$data['error'] = '';
	} else {
		$data['html'] = '';
		$data['error'] = $res;
	}

	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($data);
} else {
	print_edit_form($id, $type);
}

function check_report($id, $type)
{
	global $current_user, $globals;

	// Check if current user can report
	if (!Report::check_report_user_limit()) {
		return _('has superado el límite de reportes de comentarios<br>(máximo ' . $globals['max_reports_for_comments'] . ' reportes / 24 horas)');
	}

	// Check for min karma
	if (!Report::check_min_karma()) {
		return _('no dispones de karma suficiente para reportar comentarios');
	}

	// Check if user has already reported
	if (Report::already_reported($id, $type)) {
		return _('ya has reportado esto anteriormente.');
	}

	if(($error = extra_checks($id, $type)) !== true) {
		return $error;
	}

	return true;
}

function print_edit_form($id, $type)
{
	global $current_user, $site_key;
	$randkey = rand(1000000, 100000000);
	$key = md5($randkey . $site_key);
	echo Haanga::Load("report_new.html", compact('id', 'type', 'current_user', 'site_key', 'randkey', 'key'), true);
}


function check_save_report($id, $type)
{
	global $site_key, $current_user, $globals, $db;

	// Check key
	if (!$_POST['key'] || ($_POST['key'] != md5($_POST['randkey'] . $site_key))) {
		return _('petición incorrecta');
	}

	// Check user equals current user
	if ($current_user->user_id != $_POST['user_id']) {
		return _('petición incorrecta');
	}

	// Check that at least one valid option is selected (report reason)
	if (!$_POST['report_reason'] || !Report::is_valid_reason($_POST['report_reason'])) {
		return _('debes seleccionar una opción');
	}

	// Check if current user can report
	if (!Report::check_report_user_limit()) {
		return _('has superado el límite de reportes<br>(máximo ' . $globals['max_reports'] . ' comentarios / 24 horas)');
	}

	// Check for min karma
	if (!Report::check_min_karma()) {
		return _('no dispones de karma suficiente para reportar comentarios');
	}

	// Check if user has already reported
	if (Report::already_reported($id, $type)) {
		return _('ya has reportado esto anteriormente.');
	}

	if(($error = extra_checks($id, $type)) !== true) {
		return $error;
	}

	// save report
	$report = new Report();
	$report->reason = $_POST['report_reason'];
	$report->reporter_id = $current_user->user_id;
	$report->type = $type;
	$report->ref_id = $id;

	// Check report state

	$sql = "SELECT report_status from reports where report_type='$type' and report_ref_id={$report->ref_id} and report_status <>'" . Report::REPORT_STATUS_PENDING . "'";
	$report_status = $db->get_var($sql);

	if ($report_status) {
		$report->status = $report_status;
	}

	return $report->store();
}

function save_report($id, $type)
{
	$res = check_save_report($id, $type);

	if (true === $res) {
		$data['html'] = '';
		$data['error'] = '';
	} else {
		$data['html'] = '';
		$data['error'] = $res;
	}

	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($data);
}

function test_id_ok($id, $type) {

	switch ($type) {
		case Report::REPORT_TYPE_LINK:
			return (Link::from_db($id));
		case Report::REPORT_TYPE_COMMENT:
			return (Comment::from_db($id));
		case Report::REPORT_TYPE_POST:
			return (Post::from_db($id));
	}
	return false;
}


function extra_checks($id, $type) {

	global $globals, $current_user;

	if($type == Report::REPORT_TYPE_LINK) {
		if(!($link = Link::from_db($id)))
			return _('no se encontró la historia');

		// Check if user reports his own link! :p
		if ($current_user->user_id == $link->author) {
			return _('no puedes reportar tu propia historia');
		}

		// Check votes closed
		if ($link->date < $globals['now'] - $globals['time_enabled_votes']) {
			return _('noticia cerrada');
		}
	}

	if($type == Report::REPORT_TYPE_COMMENT) {
                if(!($comment = Comment::from_db($id)))
                        return _('no se encontró el comentario');

		// Check if user votes his own comment! :p
		if ($current_user->user_id == $comment->author) {
			return _('no puedes reportar tu propio comentario');
		}

		// Check comments closed
		if ($comment->date < $globals['now'] - $globals['time_enabled_comments']) {
			return _('comentarios cerrados');
		}

		// Check that is not an admin comment
		if ($comment->type == 'admin') {
			return _('este comentario no se puede reportar');
		}
	}

	if($type == Report::REPORT_TYPE_POST) {
		if(!($post = Post::from_db($id)))
			return _('no se encontró el postit');

		// Check if user votes his own comment! :p
		if ($current_user->user_id == $post->author) {
			return _('no puedes reportar tu propio comentario');
		}

		// Check that is not an admin comment
		if ($post->admin) {
			return _('este postit no se puede reportar');
		}

		// Check votes closed for the post
		if ($post->date < $globals['now'] - $globals['time_enabled_votes']) {
			return _('votos cerrados, ya no se puede reportar');
		}
	}

	return true;
}

