<?php
	include('config.php');
	include(mnminclude.'html1.php');
	do_header(_('FAQ') . ' | ' . _('mediatize'));
?>

<div id="singlewrap">
<div id="faq" class="faq">
<h1>Preguntas frecuentes</h1>
<br/>
<ul>
<li><h2>¿Qué es mediatize?</h2>
<p>Es una web que te permite enviar noticias o historias que serán revisadas por todos y serán promovidas, o no, a la página principal según los votos recibidos. Cuando un usuario envía una noticia ésta queda en la <a target="_blank" href="queue"><em>cola de pendientes</em></a> hasta que reúne los votos suficientes para ser promovida a la página principal. El código con el que se ha hecho la web es un fork de Menéame, por lo que en su wiki puede leerse más información de su funcionamiento: <a target="_blank" href="http://meneame.wikispaces.com/" title="wiki meneame">wiki del menéame</a>.
</p>
</li>

<li>
<h2>¿Hace falta registrarse?</h2>
<p>Sólo es necesario hacerlo para enviar historias y agregar comentarios.
</p>
</li>


<li>
<h2>¿Cómo promover las historias?</h2>
<p>Selecciona la opción <a target="_blank" href="queue"><em>nuevas</em></a> y te aparecerán las noticias no publicadas, ordenadas descendentemente por fecha de envío. Sólo tienes que votar aquellas que más te agradan o consideres importantes. Una vez superado unos umbrales de votos y <em>karma</em> serán promovidas a la página principal.</p>
<p>No te olvides de leer las <a target="_blank" href="legal">condiciones de uso</a>.</p>
</li>


<li>
<h2>¿Sólo cuenta el número de votos?</h2>
<p>No, cuentan también el <em>karma</em> y si es voto anónimo o no.
</p>
</li>


<li>
<h2>¿Cómo enviar historias?</h2>
<p>Debes <a target="_blank" href="register">registrarte</a> antes, es muy fácil y rápido. Luego seleccionas <a target="_blank" href="submit"><em>enviar historia</em></a>. En un proceso de tres pasos simples la historia será enviada a la <a target="_blank" href="queue">cola de pendientes</a>.
</p>
</li>


<li>
<h2>¿Qué tipos de historias hay que enviar?</h2>
<p>Las que tú desees, pero piensa que estarán sujetas a la revisión de los lectores que las votarán, o no. Aún así, el objetivo principal es que se traten de noticias y apuntes de blogs. Lo que <strong>no debes hacer es <em>spam</em></strong>, es decir enviar muchos enlaces de unas pocas fuentes. Intenta ser variado. Envía historias que puedan ser interesantes para muchos. No mires sólo tu ombligo, usa el <strong>sentido común y un mínimo de espíritu colaborativo y respeto hacia los demás</strong>.
</p>
</li>

<li>
<h2>¿Cómo funciona eso de los votos y el karma?</h2>
<p>En el wiki de Menéame está <a target="_blank" href="http://meneame.wikispaces.com/Karma">perfectamente explicado</a>.</p>
</li>

<li>
<h2>No puedo votar a comentarios o postits</h2>
<p>Hace falta un karma mínimo para votar comentarios y postits. Se pueden consultar los <a target="_blank" href="values">valores de los parámetros básicos</a> sobre karma y límites para más información sobre el tema.</p>
</li>


<li>
<h2>¿Cómo se seleccionan las historias que se publican en la portada?</h2>
<p>Lo hace un proceso que se ejecuta varias veces al día.</p>

<p>Primero calcula cuál es el karma mínimo que han de tener las noticias. Este valor depende de la media del karma de las noticias que fueron promovidas en las últimas dos semanas, más un coeficiente que depende del tiempo transcurrido desde la publicación de la última noticia. Este coeficiente decrece a medida que pasa el tiempo y se hace uno (1) cuando ha pasado una hora. Eso quiere decir que pasada una hora, cuando el coeficiente se hizo uno, cualquier noticia que tenga un karma igual o superior a la media será promovida. Esto tiene dos objetivos, por un lado se persigue que si la <em>calidad</em> es constante se promoverá una media de una noticia por hora, pero las que reciban más votos (se espera que sea incremental) serán publicadas antes.
</p>

<p>El karma de cada noticia se calcula multiplicando el número de votos por el karma del autor del voto. Si es anónimo ese voto vale cuatro (4). Si es de un usuario registrado el valor es multiplicado por su karma.</p>

<p>Finalmente hay una restricción adicional para evitar <em>abusos</em> de los usuarios registrados: sólo pueden ser promovidas aquellas noticias que al menos tengan <em>N</em> votos.</p>
</li>

<li>
<h2>¿Qué es esa pestaña "descartadas" en la página de votación de pendientes (nuevas)?</h2>
<p>Cuando una noticia recibe más reportes de "problemas" que votos positivos, es movida a esta cola. Los usuarios pueden seguir votando y si consigue los votos suficientes volverá a la cola de pendientes normal.
</p>
</li>

<li>
<h2>¿Qué es "postits"?</h2>
<p>Una herramienta de comunicación entre los usuarios de mediatize y se organiza en pequeños apuntes, como los mini-post de un blog colectivo --de todos los usuarios de postits-- y a la vez individual. Puedes usarlo para cuestiones relacionadas con esta web o para explicar lo que quieras. Puedes escribir desde la web o el celular. Encontrarás <a target="_blank" href="http://meneame.wikispaces.com/N%C3%B3tame">más detalles en el wiki de Menéame</a>.</p>
</li>

<li>
<h2>¿Para qué sirve la opción chismosa?</h2>
<p>Muestra lo que sucede en mediatize en tiempo real. Si eres usuario registrado también puedes usarla para chatear.</p>
</li>

<li>
<h2><a name="we"></a>¿Quién está detrás del mediatize?</h2>
<p>Es un proyecto personal para crear una web con noticias propuestas por los mismos usuarios y publicadas (pasadas a portada) también por éllos.
Encontrarás los datos de <strong>contacto</strong> en <a target="_blank" href="legal#contact">la página de las condiciones legales</a>.
</p>
</li>

<li>
<h2>¿Cuáles son las diferencias fundamentales con Digg y otros servicios similares?</h2>
<ul>
<li>Se permiten votos anónimos.</li>
<li>La publicación de la noticia no sólo está basada en los votos, sino en el valor del karma de los usuarios que han votado.</li>
<li>El sistema está específicamente programado para interactuar vía <em>trackbacks</em> con los sistemas de <em>blogs</em> existentes. En la mayoría de los casos detecta automáticamente las direcciones de <em>trackback</em>.</li>
<li>Hay diversos RSS, casi para todos los gustos, incluso de búsquedas personalizadas.</li>
</ul>
</li>


<li>
<h2>¿Qué software se usa?</h2>
<p>El software está completamente desarrollado por Ricardo Galli, Benjamí Villoslada y colaboraciones de terceros, más las modificaciones hechas para mediatize.</p>
</li>

<li>
<h2>¿Será liberado el software?</h2>
<p>Ya está liberado. En el pie de todas las páginas encontrarás el enlace para descargarlo. Tiene la licencia <a target="_blank" href="http://www.affero.org/oagpl.html">Affero General Public License</a>.</p>
</li>

<li>
<h2>¿Dónde notificamos errores, problemas o sugerencias?</h2>
<p>Ver la <a target="_blank" href="legal#contact">sección de contacto</a> en la condiciones legales y de uso.
</p>
</li>

<li>
<h2>¿Cómo se pagan los gastos?</h2>
<p>AdSense cubre los gastos por el momento.</p>
</li>

</ul>

</div>
</div>

<?php

	do_footer_menu();
	do_footer();
