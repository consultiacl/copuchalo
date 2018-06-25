<?php

$globals['extra_css'][] = 'admin.css';
$globals['extra_js'][] = '../admin/js/admin.js';
$globals['ads'] = false;

if (!$current_user->admin) {
	Haanga::Load("admin/no_access.html");
	do_footer();
	die;
}

function do_admin_tabs($tab_selected = false)
{
	global $db;

	$users['enabled']  = (int) $db->get_var("SELECT count(*) FROM users WHERE user_level in ('normal', 'special')");
	$users['disabled'] = (int) $db->get_var("SELECT count(*) FROM users WHERE user_level in ('autodisabled', 'disabled')");
	$users['admin']    = (int) $db->get_var("SELECT count(*) FROM users WHERE user_level in ('blogger', 'admin', 'god')");
	$users['total']    = $users['enabled'] + $users['disabled'] + $users['admin'];

	$tabs = [
		"hostname" => 'bans.php?tab=hostname',
		"punished_hostname" => 'bans.php?tab=punished_hostname',
		"email" => 'bans.php?tab=email',
		"ip" => 'bans.php?tab=ip',
		"words" => 'bans.php?tab=words',
		"proxy" => 'bans.php?tab=proxy',
		"noaccess" => 'bans.php?tab=noaccess',
		"admin_logs" => 'logs.php',
		"reports" => 'reports.php',
		"pinned story" => 'pinned.php'
	];

	Haanga::Load("admin/tabs.html", compact('tabs', 'tab_selected', 'users'));
}
