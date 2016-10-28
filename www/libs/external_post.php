<?php
// The source code packaged with this file is Free Software, Copyright (C) 2009 by
// Ricardo Galli <gallir at uib dot es>.
// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
//		http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".

function twitter_post($auth, $link, $link_url) {

	if (empty($auth['twitter_token']) || empty($auth['twitter_token_secret']) || empty($auth['twitter_consumer_key']) ||  empty($auth['twitter_consumer_secret'])) {
		syslog(LOG_NOTICE, "twitter_token, twitter_token_secret, twitter_consumer_key or twitter_consumer_secret not defined");
		return false;
	}

	// add the codebird library
	require_once('codebird/codebird.php');

	try{
		Codebird::setConsumerKey($auth['twitter_consumer_key'], $auth['twitter_consumer_secret']);
		$cb = Codebird::getInstance();
		$cb->setToken($auth['twitter_token'], $auth['twitter_token_secret']);

 		$maxlen = 140 - 24; // minus the url length

		// Get image from link
		if ($link->has_thumb() && !empty($link->media_url)) {
                	$thumb = $link->media_url;
		} else {
                	$thumb = get_avatar_url($link->author, $link->avatar, 80);
		}

		// Text to tweet (link title)
		$text_tweet = $link->title;

		if($thumb) {
			// if an image is attached, you loose 23 chars
			$maxlen -= 23;
			$msg = mb_substr(text_to_summary(html_entity_decode($text_tweet), $maxlen), 0, $maxlen);
			$message = $msg . ' ' . $link_url;

			//build an array of images to send to twitter
			$reply = $cb->media_upload(array(
				'media' => $thumb
			));
			//upload the file to your twitter account
			$mediaID = $reply->media_id_string;

			//build the data needed to send to twitter, including the tweet and the image id
			$params = array(
				'status' => $message,
				'media_ids' => $mediaID
			);
		} else {
			$msg = mb_substr(text_to_summary(html_entity_decode($text_tweet), $maxlen), 0, $maxlen);
			$message = $msg . ' ' . $link_url;

			$params = array(
				'status' => $message
			);
		}

		//post the tweet with codebird
		$reply = $cb->statuses_update($params);
	} catch (Exception $e) {
                syslog(LOG_INFO, "Twitter caught exception: " . $e->getMessage() . " in " . basename(__FILE__) . "\n");
                echo "Twitter post failed: $msg " . mb_strlen($msg) . "\n";
                return false;
        }

	if ($reply->httpstatus != '200')
	{
		syslog(LOG_INFO, "Error publishing to Twitter: ".$reply->errors[0]->message." (code: ".$reply->errors[0]->code.")");
		return false;
	} else {
		syslog(LOG_INFO, "Published to Twitter: $message" . (($thumb) ? " with image $thumb" : ""));
		return true;
	}
}

function fon_gs($url) {
	$gs_url = 'http://fon.gs/create.php?url='.urlencode($url);
	$res = get_url($gs_url);
	if ($res && $res['content'] && preg_match('/^OK/', $res['content'])) {
		$array = explode(' ', $res['content']);
		return $array[1];
	} else {
		return $url;
	}
}

function pubsub_post() {
	require_once(mnminclude.'pubsubhubbub/publisher.php');
	global $globals;

	if (! $globals['pubsub']) return false;
	$rss = 'http://'.get_server_name().$globals['base_url'].'rss';
	$p = new Publisher($globals['pubsub']);
	if ($p->publish_update($rss)) {
		syslog(LOG_NOTICE, "posted to pubsub ($rss)");
	} else {
		syslog(LOG_NOTICE, "failed to post to pubsub ($rss)");
	}
}

function facebook_post($auth, $link, $text = '') {

	if (empty($auth['facebook_token']) || empty($auth['facebook_key']) || empty($auth['facebook_secret']) || empty($auth['facebook_page_id'])) {
		syslog(LOG_NOTICE, "facebook_token, facebook_key, facebook_secret or facebook_page_id not defined");
		return false;
	}

	require_once __DIR__ . '/Facebook/autoload.php';

	// mediatize APP
	$fb = new Facebook\Facebook([
		'app_id' => $auth['facebook_key'],
		'app_secret' => $auth['facebook_secret'],
		'default_graph_version' => 'v2.5',
		'default_access_token' => $auth['facebook_token']
	]);

	if ($link->has_thumb() && !empty($link->media_url)) {
		$thumb = $link->media_url;
	} else {
		$thumb = get_avatar_url($link->author, $link->avatar, 80);
	}

	$permalink = $link->get_permalink();

	$data = [
		'link' => $permalink,
		'picture' => $thumb,
	];

	if($text != '') {
		$data['message'] = $text;
	}

	try {
		$response = $fb->post('/'.$auth['facebook_page_id'].'/feed', $data);
	} catch(Facebook\Exceptions\FacebookResponseException $e) {
		syslog(LOG_INFO, "Graph returned an error: " . $e->getMessage() . " in " . basename(__FILE__) . "\n");
		return false;
	} catch(Facebook\Exceptions\FacebookSDKException $e) {
		syslog(LOG_INFO, "Facebook SDK returned an error: " . $e->getMessage() . " in " . basename(__FILE__) . "\n");
		return false;
	}

	syslog(LOG_INFO, "Published to FB: $permalink with picture: $thumb");
	return true;
}

function telegram_post($auth, $message) {

	if ( empty($auth['telegram_token']) || empty($auth['telegram_channel']) ) {
		syslog(LOG_NOTICE, "telegram_token or telegram_channel not defined");
		return false;
	}

	$botToken = $auth['telegram_token'];
	$chat_id = $auth['telegram_channel'];
	$bot_url = "https://api.telegram.org/bot$botToken/";
	$url = $bot_url . "sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($message);
	$result = file_get_contents($url);

	if($result === false) {
		return false;
	} else {
		syslog(LOG_INFO, "Published to Telegram: $message");
		return true;
	}
}

