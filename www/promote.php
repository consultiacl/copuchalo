<?php
// The source code packaged with this file is Free Software, Copyright (C) 2005 by
// Ricardo Galli <gallir at uib dot es>.
// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
//		http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".

include('config.php');
include(mnminclude.'html1.php');

$globals['ads'] = false;
do_header(_('promote') . ' | ' . _('mediatize'), '', false, false, '', false, false);

echo '<div id="singlewrap" class="col-sm-10">';
echo '<div class="topheading th-no-margin"><h2>Estado sistema de promoción</h2></div>';

$site_id = SitesMgr::my_id();

$annotation = new Annotation("promote-$site_id");
$annotation->text = $output;
if($annotation->read()) {
	echo $annotation->text;
}

echo '</div>'."\n";

do_footer_menu();
do_footer();

