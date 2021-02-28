<?php
// gzip compression
if (extension_loaded('zlib') and ob_get_length() > 0) {
	ob_end_clean();
	ob_start("ob_gzhandler");
}
else {
	ob_start("ob_gzhandler");
}

header("Content-type: text/css; charset: UTF-8");

echo '@charset "utf-8";'."\n";

/* General styles (layout, forms, multi-pages elements…) */
readfile('style-style.css');

/* Custon UserCSS */
if (is_file('../../../config/custom-styles.css')) {
	readfile('../../../config/custom-styles.css');
}
