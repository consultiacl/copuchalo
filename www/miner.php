<?php

include('config.php');
include(mnminclude.'html1.php');

do_header(_('Colabora con Mediatize minando la criptomoneda "monero"'), '', false, false, '', false, false);

echo '<div id="singlewrap" class="col-sm-10">';
echo '<div class="topheading th-no-margin"><h2>Colabora con Mediatize minando la criptomoneda monero</h2></div>';

Haanga::Load('miner.html', compact('globals'));

echo '</div>';

do_footer();

