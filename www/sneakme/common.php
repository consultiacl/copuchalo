<?php
// The source code packaged with this file is Free Software, Copyright (C) 2011 by
// Menéame and Ricardo Galli <gallir at gallir dot com>.
// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
// 		http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".

function get_posts_menu($tab_selected, $username) {
	global $globals, $current_user;

	if ($tab_selected != 4 && $current_user->user_id > 0) {
		$username = $current_user->user_login;
	}

	switch ($tab_selected) {
		case 2:
			$id = _('popular');
			break;
		case 3:
			$id = _('mapa');
			break;
		case 4:
			$id = $username;
			break;
		case 5:
			$id = _('privados');
			break;
		default:
			$id = _('todos');
			break;

	}

	$items = array();

	$items[] = new MenuOption(_('todos'), post_get_base_url(''), $id, _('todos los posts'));
	$items[] = new MenuOption(_('popular'), post_get_base_url('_best'), $id, _('posts populares'));
	if ($globals['google_maps_api']) {
		$items[] = new MenuOption(_('mapa'), post_get_base_url('_geo'), $id, _('mapa animado'));
	}
	if (! empty($username)) {
		$items[] = new MenuOption($username, post_get_base_url($username), $id, $username, 'username');
	}

	if ($current_user->user_id > 0 ) {
		$items[] = new MenuOption(_('privados'), post_get_base_url('_priv'), $id, _('mensajes privados'));
	}

	return $items;
}

