#!/usr/bin/php

<?php
// This file post the indicated link to ever twitter o facebook account
// Argument required: hostname, link_id, status

if (count($argv) < 3) {
	syslog(LOG_INFO, "Usage: ".basename(__FILE__)." site_name link_id status");
	echo "Usage: ".basename(__FILE__)." site_name link_id status\n";
	die;
}

$site_name = $argv[1];
$link_id = (int) $argv[2];
$status = $argv[3];

include(dirname(__FILE__).'/../../config.php');
include(mnminclude.'external_post.php');

$my_id = SitesMgr::get_id($site_name);

if (! $my_id > 0) {
	syslog(LOG_INFO, "post_link.php, site not found $site_name");
	echo "No site id found\n";
	die;
}

SitesMgr::__init($my_id);

$link = Link::from_db($link_id);
if (! $link) {
	syslog(LOG_INFO, "post_link.php, link not found $link_id");
	echo "Link $link_id not found\n";
	die;
}

do_posts($link);


function do_posts($link) {
	global $globals;

	$info = SitesMgr::get_info();

	syslog(LOG_INFO, "posting $link->uri");

	$url = $link->get_permalink($info->sub);
	echo "Posting $url: ".$globals['server_name']."\n"; 

	// NEW format
	$image = false;
	if ($link->has_thumb()) {
		$media = $link->get_media();
		if ($media && file_exists($media->pathname())) {
			$image = $media->pathname();
		}
	}

	if ($globals['url_shortener']) {
		$short_url = $link->get_short_permalink();
	} else {
		$short_url = $url;
	}

	$r = false;
	$tries = 0;
	while (! $r && $tries < 4) {
		$r = facebook_post($globals, $link);
		$tries++;
		if (! $r) sleep(4);
	}
}

