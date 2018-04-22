<?php
include('../config.php');
header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: public, max-age=864000');
header("Expires: " . gmdate("r", $globals['now'] + 864000));
header('Last-Modified: ' .  gmdate('D, d M Y H:i:s', max(filemtime('main.css'), filemtime('handheld.css'))) . ' GMT');

Haanga::Load('css/colorbox.css');
Haanga::Load('css/main.css');

/* Include handheld classes for mobile/tablets */
if ($globals['mobile']) {
	Haanga::Load('css/mobile.css');
} else {
	echo "@media (max-width: 767px) {";
	Haanga::Load('css/handheld.css');
	echo "}";
}

