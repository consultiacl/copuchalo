#!/usr/bin/php

<?php
include('../config.php');
include('utils.php');
include(mnminclude.'external_post.php');

$now = time();
$max_date = "date_sub(now(), interval 15 minute)";
$min_date = "date_sub(now(), interval 24 hour)";

echo "STARTING delete non validated users\n";
// Delete not validated users
$db->query("delete from users where user_date < date_sub(now(), interval 12 hour) and user_date > date_sub(now(), interval 24 hour) and user_validated_date is null");

echo "STARTING delete old bad links\n";
// Delete old bad links
$minutes = intval($globals['draft_time'] / 60);

$ids = $db->get_col("select link_id from links where link_status='discard' and link_date > date_sub(now(), interval 24 hour) and link_date < date_sub(now(), interval $minutes minute) and link_votes = 0 order by link_id asc");

if ($ids) {
	$ids_str = implode(',', $ids);
	echo "Deleting $ids_str\n";
	$db->query("delete from links where link_id in ($ids_str)");
}

