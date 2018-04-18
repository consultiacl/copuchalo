<?php
// The source code packaged with this file is Free Software, Copyright (C) 2005 by
// Ricardo Galli <gallir at uib dot es>.
// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
// 		http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".

include('config.php');
include(mnminclude.'html1.php');

$min_pts = 10;
$max_pts = 44;
$limit = 200;
$line_height = $max_pts * 0.75;

$range_names  = array(_('24 horas'), _('48 horas'), _('una semana'), _('un mes'), _('un año'), _('todas'));
$range_values = array(1, 2, 7, 30, 365, 0);


$site_id = SitesMgr::my_id();

if(($from = check_integer('range')) >= 0 && $from < count($range_values) && $range_values[$from] > 0 ) {
	// we use this to allow sql caching
	$from_time = '"'.date("Y-m-d H:00:00", time() - 86400 * $range_values[$from]).'"';
	$from_where = "FROM blogs, links, sub_statuses WHERE id = $site_id and date > $from_time and status = 'published' and link = link_id and link_blog = blog_id";
} else {
	$from_where = "FROM blogs, links, sub_statuses WHERE id = $site_id and status = 'published' and link = link_id and link_blog = blog_id";
}
$from_where .= " GROUP BY blog_id";

$max = max($db->get_var("select count(*) as count $from_where order by count desc limit 1"), 2);
//echo "MAX= $max\n";

$coef = ($max_pts - $min_pts)/($max-1);


do_header(_('nube de sitios web') . ' | ' . _('mediatize'), 'cloudsites', false, false, '', false, true);

echo '<div>';
echo '<div id="newswrap" class="col-sm-9">';
echo '<div class="row">';
echo '<div class="topheading th-cloudsites"><h2>Los sitios más enlazados</h2></div>';

echo '<div class="cloudsites" style="line-height: '.$line_height.'pt;">';
$res = $db->get_results("select blog_url, count(*) as count $from_where order by count desc limit $limit");
if ($res) {
	foreach ($res as $item) {
		$blogs[$item->blog_url] = $item->count;
	}
	ksort($blogs);
	foreach ($blogs as $url => $count) {
		$text = preg_replace('/http:\/\//', '', $url);
		$text = preg_replace('/^www\./', '', $text);
		$text = preg_replace('/\/$/', '', $text);
		$size = intval($min_pts + ($count-1)*$coef);
		echo '<span style="font-size: '.$size.'pt"><a href="'.$url.'">'.$text.'</a></span>&nbsp;&nbsp; ';
	}
}

echo '</div></div></div>';

/*** SIDEBAR ****/
echo '<div id="sidebar" class="col-sm-3">';
do_banner_right();
do_vertical_tags('published');
echo '</div>';
/*** END SIDEBAR ***/

echo '</div>';

do_footer();

