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


$sites = SitesMgr::get_active_sites();

foreach ($sites as $site) {
	echo "START SITE: $site\n";
	SitesMgr::__init($site);
	$site_info = SitesMgr::get_info($site_id);
	if ($site_info->owner == 0) { // Only depublish in main subs
		depublish($site);
	}
	if ($site_info->sub) { // Only discard in the subs
		discard($site);
	}
}

punish_comments();

// END

function discard($site_id) {
	global $db, $globals, $max_date, $min_date;



	echo "STARTING discard for $site_id\n";
	$site_info = SitesMgr::get_info($site_id);

	// Discard links
	// $negatives = $db->get_col("select SQL_NO_CACHE link_id from links, sub_statuses where id = $site_id and date > $min_date and status = 'queued' and link_id = link and link_karma < 0 and (link_date < $max_date or link_karma < -100) and (link_karma < -link_votes*2 or (link_negatives > 20 and link_negatives > link_votes/2)) and (link_negatives > 20 or (link_negatives > 4 and link_negatives > link_votes+3) ) order by link_id asc");
	// Simlified version 
	$negatives = $db->get_col("select SQL_NO_CACHE link_id from links, sub_statuses where id = $site_id and date > $min_date and status = 'queued' and link_id = link and link_karma < -20 and link_negatives > 5 and link_negatives > link_votes + 2 and (link_date < $max_date or link_karma < -100) order by link_id asc");

	//$db->debug();
	if( !$negatives) {
		echo "no negatives to analyze\n";
		return;
	}

	foreach ($negatives as $id) {
		$l = Link::from_db($id);

		$l->status = 'discard';
		$l->store();
		echo "Discard id: $l->id\n"; 

		if ($site_info->sub) { // Double check
			$user = new User($l->author);
			if ($user->read) {
				$user->add_karma(-$globals['instant_karma_per_discard'], _('Noticia descartada'));
				echo "$user->username: $user->karma\n";
			}
		}

		// Add the discard to log/event
		Log::insert('link_discard', $l->id, $l->author);
		echo  "$l->id: $l->karma ($l->votes, $l->negatives)\n";
	}
}

function punish_comments($hours = 2) {
	global $globals, $db;

	echo "STARTING punish_comments\n";

	$log = new Annotation('punish-comment');
	if ($log->read() && $log->time > time() - 3600*$hours) {
		echo "Comments already verified at: " . get_date_time($log->time) . "\n";
		return false;
	}

	if ($globals['min_karma_for_comments'] > 0) $min_karma =  $globals['min_karma_for_comments'];
	else $min_karma =  4.5;

	$votes_from = time() - $hours * 3600; // 'date_sub(now(), interval 6 hour)';
	$comments_from =  time() - 2 * $hours * 3600; //'date_sub(now(), interval 12 hour)';


	echo "Starting karma_comments...\n";

	$users = "SELECT SQL_NO_CACHE distinct comment_user_id as user_id from comments, users where comment_date > from_unixtime($comments_from) and comment_karma < -70 and comment_user_id = user_id and user_level != 'disabled' and user_karma >= $min_karma";
	$result = $db->get_results($users);

	$log->store();
	if (! $result) return;

	foreach ($result as $dbuser) {
		$user = new User($dbuser->user_id);
		printf ("%07d  %s\n", $user->id, $user->username);
		$punish = 0;

		$comment_votes_count = (int) $db->get_var("SELECT SQL_NO_CACHE count(*) from votes, comments where comment_user_id = $user->id and comment_date > from_unixtime($comments_from) and vote_type='comments' and vote_link_id = comment_id and  vote_date > from_unixtime($votes_from) and vote_user_id != $user->id");
		if ($comment_votes_count > 5)  {
			$votes_karma = (int) $db->get_var("SELECT SQL_NO_CACHE sum(vote_value) from votes, comments where comment_user_id = $user->id and comment_date > from_unixtime($comments_from) and vote_type='comments' and vote_link_id = comment_id and vote_date > from_unixtime($votes_from) and vote_user_id != $user->id");
			if ($votes_karma < 50) {
			 	$distinct_votes_count = (int) $db->get_var("SELECT SQL_NO_CACHE count(distinct comment_id) from votes, comments where comment_user_id = $user->id and comment_date > from_unixtime($comments_from) and vote_type='comments' and vote_link_id = comment_id and  vote_date > from_unixtime($votes_from) and vote_user_id != $user->id");
				$comments_count = (int) $db->get_var("SELECT SQL_NO_CACHE count(*) from comments where comment_user_id = $user->id and comment_date > from_unixtime($comments_from)");
				$comment_coeff =  min($comments_count/10, 1) * min($distinct_votes_count/($comments_count*0.75), 1);
				$punish = max(-2, round($votes_karma * $comment_coeff * 1/1000,2));
			}
		}
		if ($punish < -0.1) {
			echo "comments: $comments_count votes distinct: $distinct_votes_count karma: $votes_karma coef: $comment_coeff -> $punish\n";
			$user->add_karma($punish, _('Penalización por comentarios'));
			echo(_('Penalización por negativos en comentarios').": $punish, nuevo karma: $user->karma\n");
			$log->append(_('Penalización')." $user->username: $punish, nuevo karma: $user->karma\n");
		}
		$db->barrier();
	}
}


function depublish($site_id) {
	// send back to queue links with too many negatives
	global $db, $globals;

	$days = 4;

	echo "STARTING depublish for $site_id\n";
	$site_info = SitesMgr::get_info($site_id);



	$links = $db->get_col("select SQL_NO_CACHE link_id as id from links, sub_statuses where id = $site_id and status = 'published' and date > date_sub(now(), interval $days day) and date < date_sub(now(), interval 14 minute) and link = link_id and link_negatives > link_votes / 5");

	if ($links) {
		$votes_clicks = $db->get_col("select SQL_NO_CACHE link_votes/counter from links, sub_statuses, link_clicks where sub_statuses.id = $site_id and status = 'published' and date > date_sub(now(), interval $days day) and link = link_id and link_clicks.id = link");
		sort($votes_clicks);

		foreach ($links as $link) {
			$l = Link::from_db($link);
			$vc = $l->votes/$l->clicks;
			$prob = cdf($votes_clicks, $vc);

			// Count only those votes with karma > 6 to avoid abuses with new accounts with new accounts
			$negatives = (int) $db->get_var("select SQL_NO_CACHE sum(user_karma) from votes, users where vote_type='links' and vote_link_id=$l->id and vote_date > from_unixtime($l->date) and vote_date > date_sub(now(), interval 24 hour) and vote_value < 0 and vote_user_id > 0 and user_id = vote_user_id and user_karma > " . $globals['depublish_negative_karma']);
			$positives = (int) $db->get_var("select SQL_NO_CACHE sum(user_karma) from votes, users where vote_type='links' and vote_link_id=$l->id and vote_date > from_unixtime($l->date) and vote_value > 0 and vote_date > date_sub(now(), interval 24 hour) and vote_user_id > 0 and user_id = vote_user_id and user_karma > " . $globals['depublish_positive_karma']);
			
			echo "Candidate $l->uri\n  karma: $l->sub_karma ($l->karma) negative karma: $negatives positive karma: $positives\n";
			// Adjust positives to the probability of votes/clicks
			$c = (1 + (1 - $prob) * 0.5);
			$positives = $positives * $c;
			echo "  probability: $prob New positives: $positives ($c)\n";

			if ($negatives > 10 && $negatives > $c * $l->sub_karma/6 && $l->negatives > $c * $l->votes/6 && $l->negatives > 5
				&& ($negatives > $positives || ($negatives > $c * $l->sub_karma/2 && $negatives > $positives/2) )) {
				echo "Queued again: $l->id negative karma: $negatives positive karma: $positives\n";
				$karma_old = $l->sub_karma;
				$karma_new = intval($l->sub_karma/ $globals['depublish_karma_divisor'] );

				$l->status = 'queued';
				$l->sub_karma = $l->karma = $karma_new;

				$db->query("update links set link_status='queued', link_date = link_sent_date, link_karma=$karma_new where link_id = $l->id");
				SitesMgr::deploy($l);

				// Add an annotation to show it in the logs
				$l->karma_old = $karma_old;
				$l->karma = $karma_new;
				$l->annotation = _('Retirada de portada');
				$l->save_annotation('link-karma');
				Log::insert('link_depublished', $l->id, $l->author);

				if (! $site_info->sub) {
					// Add the discard to log/event
					$user = new User($l->author);
					if ($user->read) {
						echo "$user->username: $user->karma\n";
						$user->add_karma(-$globals['instant_karma_per_depublished'], _('Retirada de portada'));
					}

					// Increase karma to users that voted negative
					$ids = $db->get_col("select vote_user_id from votes where vote_type = 'links' and vote_link_id = $l->id and vote_user_id > 0 and vote_value < 0");

					foreach ($ids as $id) {
						$u = new User($id);
						if ($u->read) {
							// Avoid abuse of users voting negative just to get more karma
							$voted = $db->get_var("select count(*) from logs where log_type = 'user_depublished_vote' and log_user_id = $id and log_date > date_sub(now(), interval 48 hour)");
							if ($voted < 5) {
								$u->add_karma(0.20, _('Negativo a retirada de portada'));
								Log::insert('user_depublished_vote', $l->id, $id);
							}
						}
					}
				}

/***********
 * TODO: call for every site (as in promote)
				if ($globals['twitter_token'] || $globals['jaiku_user']) {
					if ($globals['url_shortener']) {
						$short_url = $l->get_short_permalink();
					} else {
						$short_url = fon_gs($l->get_permalink());
					}
					$text = _('Retirada de portada') . ': ' . $l->title;
					if ($globals['twitter_user'] && $globals['twitter_token']) {
						twitter_post($text, $short_url);
					}
					if ($globals['jaiku_user'] && $globals['jaiku_key']) {
						jaiku_post($text, $short_url);
					}
				}
*******/
			}
		}
	}
}
