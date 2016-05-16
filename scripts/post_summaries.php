#!/usr/bin/php

<?php

global $_SERVER;
// Check which hostname server we run for, for example: mnm, emnm, etc.
$site_name = $argv[2];


// Post to Twitter/Jaiku the most voted and commented during last 24 hr
include('../config.php');
include(mnminclude.'external_post.php');

$my_id = SitesMgr::get_id($site_name);

if (! $my_id > 0) {
    syslog(LOG_INFO, basename(__FILE__)." site not found $site_name");
    echo "No site id found\n";
    die;
}

SitesMgr::__init($my_id);

syslog(LOG_INFO, "running ".basename(__FILE__)." for $site_name");

$info = SitesMgr::get_info();
//$properties = SitesMgr::get_extended_properties();

if (intval($argv[1]) > 0) {
	$hours = intval($argv[1]);
} else {
	$hours = 24;
}
// Get most voted link
$link_sqls[_('Más votada')] = "select vote_link_id as id, count(*) as n from sub_statuses, links, votes use index (vote_type_4) where id = ".SitesMgr::my_id()." AND link_id = link AND link_status = 'published' AND vote_link_id = link AND vote_type='links' and vote_date > date_sub(now(), interval $hours hour) and vote_user_id > 0 and vote_value > 0 group by vote_link_id order by n desc limit 1";

// Most commented
$link_sqls[_('Más comentada')] = "select comment_link_id as id, count(*) as n from sub_statuses, comments use index (comment_date) where id = ".SitesMgr::my_id()." AND sub_statuses.status in ('published', 'metapublished') AND comment_link_id = link AND comment_date > date_sub(now(), interval $hours hour) group by comment_link_id order by n desc limit 1";

if ($globals['click_counter'] && $hours > 20) {
	$link_sqls[_('Más leída')] = "select sub_statuses.link as id, counter as n from sub_statuses, link_clicks where sub_statuses.id = ".SitesMgr::my_id()." AND sub_statuses.status in ('published', 'metapublished') AND date > date_sub(now(), interval $hours hour) and link_clicks.id = sub_statuses.link order by n desc limit 1";
}



foreach ($link_sqls as $key => $sql) {
	$res = $db->get_row($sql);
	if (! $res) continue;
	$link = new Link;
	$link->id = $res->id;
	if ($link->read()) {
		$url = $link->get_permalink($info->sub);
		if ($globals['url_shortener']) {
			$short_url = $link->get_short_permalink();
		} else {
			//$short_url = fon_gs($link->get_permalink());
			$short_url = $url;
		}
		if ($hours < 72) {
			$intro = "$key ${hours}h";
		} else {
			$days = intval($hours/24);
			$intro = "$key ${days}d";
		}
		$text = "$intro: $link->title";

		//twitter_post($properties, $text, $short_url); 
		//facebook_post($properties, $link, $intro);
		twitter_post($globals, $text, $short_url); 
		facebook_post($globals, $link, $intro);

		echo "$text $short_url\n"; continue;
	}
}
?>
