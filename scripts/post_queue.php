#!/usr/bin/php

<?php
// Post to Twitter the "top queued story in promote"
// Check which hostname server we run for, for example: mdtz

include('../config.php');
include(mnminclude.'external_post.php');

$site_name = $argv[1];
$my_id = SitesMgr::get_id($site_name);

if (! $my_id > 0) {
	syslog(LOG_INFO, basename(__FILE__)." site not found $site_name");
	echo "No site id found\n";
	die;
}

SitesMgr::__init($my_id);
$info = SitesMgr::get_info();
//$properties = SitesMgr::get_extended_properties();


$a_queue = new Annotation('top-queue-'.$site_name);
echo 'top-queue-'.$site_name."\n";
if(!$a_queue->read()) {
	exit;
}
$queue = explode(',', $a_queue->text);

$a_history = new Annotation('top-queue-history-'.$site_name);
if($a_history->read()) {
	$history = explode(',',$a_history->text);
} else {
	$history = array();
}

if (! in_array($queue[0], $history) ) {
	if( ! ($link = Link::from_db($queue[0])) ) {
		echo "Error reading link ". $queue[0] . "\n";
		exit;
	}
	$url = $link->get_permalink($info->sub);
	if ($globals['url_shortener']) {
		$short_url = $link->get_short_permalink();
	} else {
		$short_url = $url;
	}
	$intro = '#'._('pendiente');
	$text = "$intro $link->title";

	// Save the history
	array_push($history, intval($queue[0]));
	while(count($history) > 10) array_shift($history);
	$a_history->text = implode(',',$history);
	$a_history->store();

	//twitter_post($properties, $text, $url);
        //facebook_post($properties, $link, $intro);
	twitter_post($globals, $link, $url, $intro);
	facebook_post($globals, $link, $intro);
}

