<?php
foreach(glob(get_template_directory()."/library/includes/ext/ext-*/init.php") as $file) require_once $file;
foreach(glob(get_template_directory()."/library/includes/fun/fun-*.php") as $file) require_once $file;
foreach(glob(get_template_directory()."/library/includes/cpt/cpt-*.php") as $file) require_once $file;
foreach(glob(get_template_directory()."/library/includes/tax/tax-*.php") as $file) require_once $file;
foreach(glob(get_template_directory()."/library/includes/cmb/cmb-*.php") as $file) require_once $file;

add_action( 'init', 'cpt_insights');
add_action( 'init', 'tax_insights');

?>
