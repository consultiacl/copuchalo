#!/usr/bin/php
<?php

// Check which hostname server we run for, for example: mnm, emnm, etc.
$site_name = $argv[2];

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

//$info = SitesMgr::get_info();
//$properties = SitesMgr::get_extended_properties();

if (intval($argv[1]) > 0) {
	$hours = intval($argv[1]);
} else {
	$hours = 1;
}

$key = "post_best_comment_$my_id";
$previous = Annotation::get_text($key);
if ($previous) {
	$extra = "AND comment_id not in ($previous)";
}

$now = intval(time()/60) * 60;
$coef = 0.8;
$min_karma = $globals['comment_highlight_karma'] * 2;
$min_value = $globals['comment_highlight_karma'];
$min_length = 400;

$sql_value =   "comment_karma*(1-($now-unix_timestamp(comment_date))*$coef/($hours*3600)) as value";
$where_value = "comment_karma*(1-($now-unix_timestamp(comment_date))*$coef/($hours*3600)) > $min_value";

$sql = "select comment_id, karma, $sql_value from comments, sub_statuses where id = $my_id AND status in ('published') AND comment_date > date_sub(now(), interval $hours hour) and LENGTH(comment_content) > $min_length and comment_karma > $min_karma AND $where_value AND comment_link_id = link $extra order by value desc limit 1";

$res = $db->get_row($sql);
if (! $res) {
	exit(0);
}

$comment = Comment::from_db($res->comment_id);
if (! $comment) {
	exit(2);
}

$image = false;
if ($comment->media_size > 0) {
	$media = new Upload('comment', $comment->id);
	if ($media->read()) {
		$image = $media->pathname();
		$maxlen -= 24;
	}
}


$url = $globals[scheme].'//'.get_server_name().$comment->get_relative_individual_permalink();
syslog(LOG_INFO, "posting comment $url");

//  Store in cache
if ($previous) {
	$ids = explode(',',$previous);
	if (count($ids) > 10) {
		array_shift($ids);
	}
} else {
	$ids = array();
}

$ids[] = $comment->id;
$previous = implode(',', $ids);
Annotation::store_text($key, $previous, time() + 86400);

//twitter_post($properties, '&#x1f4ac; '.$comment->content, $url, $image);
twitter_post($globals, '&#x1f4ac; '.$comment->content, $url, $image);


