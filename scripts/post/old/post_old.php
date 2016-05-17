#!/usr/bin/php

<?php

include(dirname(__FILE__).'/../../config.php');
include(mnminclude.'external_post.php');

global $globals;

$link = Link::from_db(49);

$image = false;
if ($link->has_thumb()) {
	$media = $link->get_media();
	if ($media && file_exists($media->pathname())) {
		$image = $media->pathname();
	}
}

if (! empty($globals['facebook_token']) && ! empty($globals['facebook_key']) && ! empty($globals['facebook_secret'])) {
	$r = false;
	$tries = 0;
	while (! $r && $tries < 4) {
		$r = facebook_post($globals, $link);
		$tries++;
		echo "Intentos face: " . $tries . "\n";
		if (! $r) sleep(4);
	}
}

