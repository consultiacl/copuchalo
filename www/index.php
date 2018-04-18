<?php
// The Meneame source code is Free Software, Copyright (C) 2005-2009 by
// Ricardo Galli <gallir at gmail dot com> and Menéame Comunicacions S.L.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.

// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
// 		http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".

include_once('config.php');
include(mnminclude.'html1.php');

meta_get_current();

$page_size = $globals['page_size'];

$page = get_current_page();
$offset=($page-1)*$page_size;
$globals['ads_section'] = 'portada';

$pagetitle = $globals['site_name'];
if ($page > 1) {
	$pagetitle .= " ($page)";
}


$order_by = "ORDER BY sub_date DESC ";

$from = '';
switch ($globals['meta']) {
	case '_subs':
		if ($current_user->user_id && $current_user->has_subs) {
			$from_time = '"'.date("Y-m-d H:00:00", $globals['now'] - $globals['time_enabled_comments']).'"';
			$where = "id in ($current_user->subs) AND status='published' AND id = origen and date > $from_time";
			$rows = -1;
			Link::$original_status = true; // Show status in original sub
			$tab_option = 7; // Show "personal" as default
			break;
		}
		// NOTE: If the user has no subscriptions it will fall into next: _*
	case '_*':
		$from_time = '"'.date("Y-m-d H:00:00", $globals['now'] - $globals['time_enabled_comments']).'"';
		$from = ", subs";
		$where = "sub_statuses.status='published' AND sub_statuses.id = sub_statuses.origen and sub_statuses.date > $from_time and sub_statuses.origen = subs.id and subs.owner > 0";
		$rows = -1;
		$order_by = "ORDER BY date DESC ";
		Link::$original_status = true; // Show status in original sub
		$tab_option = 8;
		break;
	case '_friends':
		if (! $current_user->user_id > 0) do_error(_('debe autentificarse'), 401); // Check authenticated users
		$from_time = '"'.date("Y-m-d H:00:00", $globals['now'] - 86400*4).'"';
		$from = ", friends, links";
		$where = "sub_statuses.id = ". SitesMgr::my_id() ." AND date > $from_time and status='published' and friend_type='manual' and friend_from = $current_user->user_id and friend_to=link_author and friend_value > 0 and link_id = link";
		$rows = -1;
		$tab_option = 1; // Friends
		break;
	default:
		$tab_option = 0; // All
		$rows = Link::count('published');
		$where = "sub_statuses.id = ". SitesMgr::my_id() ." AND status='published' ";
}


do_header($pagetitle, _('portada tema'), false, $tab_option);

if ($tab_option == 0) {
	Haanga::Load('site_search_box.html'); // Search box for search engines
}

echo '<div>';
echo '<div id="newswrap" class="col-sm-9">';
echo '<div class="row">';

do_banner_top_news();

if (! $globals['mobile'] && $page == 1 && empty($globals['meta']) && ($top = Link::top())) {
	$vars = array('self' => $top, 'globals' => $globals);
	Haanga::Load("link_top.html", $vars);
}


// *** Sorting in a subselect only works with mysql:
//     http://stackoverflow.com/questions/26372511/mysql-order-by-inside-subquery
//     https://mariadb.atlassian.net/browse/MDEV-3926
// Old optimizacions from Galli are not correct for other databases like MariaDB: https://gallir.wordpress.com/2011/02/02/optimizando-obsesivamente-las-consultas-al-mysql/ 

//$order_by = "ORDER BY date DESC ";
//$order_by = "ORDER BY sub_date DESC ";

if (!$rows) $rows = $db->get_var("SELECT SQL_CACHE count(*) FROM sub_statuses $from WHERE $where");

// We use a "INNER JOIN" in order to avoid "order by" with filesorting. It was very bad for high pages
//$sql = "SELECT".Link::SQL."INNER JOIN (SELECT link FROM sub_statuses $from WHERE $where $order_by LIMIT $offset,$page_size) as ids ON (ids.link = link_id)";
$sql = "SELECT".Link::SQL."INNER JOIN (SELECT link FROM sub_statuses $from WHERE $where) as ids ON (ids.link = link_id) $order_by LIMIT $offset,$page_size";

//syslog(LOG_INFO, "site_id: ".$db->get_var("SELECT @site_id"));
//syslog(LOG_INFO, "user_id: ".$db->get_var("SELECT @user_id"));
//syslog(LOG_INFO, "enabled_votes: ".$db->get_var("SELECT @enabled_votes"));
//syslog(LOG_INFO, "ip_int: ".$db->get_var("SELECT @ip_int"));

//syslog(LOG_INFO, "meta: ".$globals['meta']);
//syslog(LOG_INFO, "sql1: SELECT SQL_CACHE count(*) FROM sub_statuses $from WHERE $where");
//syslog(LOG_INFO, $sql);

$globals['site_id'] = SitesMgr::my_id();

// Search for sponsored link
if (!empty($globals['sponsored_link_uri'])) $sponsored_link = Link::from_db($globals['sponsored_link_uri'], 'uri');

$links = $db->object_iterator($sql, "Link");
if ($links) {
	$counter = 0;
	foreach($links as $link) {
		//syslog(LOG_INFO, "LINK: $link->date, $link->id, $link->title");

		// Skip print link if is already printed as top highlight
		//if( isset($top) && ($top->id == $link->id) ) {
		//	continue;
		//}

		$link->max_len = 600;
		$link->print_summary('frontpage');
		$counter++;
		Haanga::Safe_Load('private/ad-interlinks.html', compact('counter', 'page_size', 'sponsored_link'));
		//Haanga::Safe_Load('private/ad-interlinks.html', compact('counter', 'page_size', 'sponsored_link'));
		//$link->print_summary();$counter++;
	}
}

echo '</div>';
do_pages($rows, $page_size);
echo '</div>';



/*** SIDEBAR ****/
echo '<div id="sidebar" class="col-sm-3">';
do_sub_message_right();
do_banner_right();
if ($globals['show_popular_published']) {
	do_active_stories();
}
// do_banner_promotions();
if ($globals['show_popular_published']) {
	do_most_clicked_stories();
	do_best_stories();
}
do_banner_promotions();
// do_best_sites();
do_most_clicked_sites();
if ($page < 2) {
	do_best_comments();
}
do_last_subs('published');
do_vertical_tags('published');
// do_last_blogs();
echo '</div>';
/*** END SIDEBAR ***/

echo '</div>';


do_footer();
exit(0);

