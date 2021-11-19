<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

if (!function_exists('websquare_after_setup_theme')) {

	function websquare_after_setup_theme()
	{
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 */
		load_theme_textdomain('websquare', get_template_directory() . '/languages');

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support('title-tag');

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support('html5', array('comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'style', 'script'));

		// Adding Thumbnail basic support.
		add_theme_support('post-thumbnails');

		// Set up the WordPress Theme logo feature.
		add_theme_support('custom-logo');

		// Add support for responsive embedded content.
		add_theme_support('responsive-embeds');
	}
}

add_action('after_setup_theme', 'websquare_after_setup_theme');

if (!function_exists('websquare_enqueue_scripts')) {
	/**
	 * Load theme's JavaScript and CSS sources.
	 */
	function websquare_enqueue_scripts()
	{
		$the_theme     = wp_get_theme();
		$theme_version = $the_theme->get('Version');
		$minified = WP_DEBUG === true ? '' : '.min';

		$css_version = $theme_version . '.' . filemtime(get_template_directory() . '/library/assets/css/theme'.$minified.'.css');
		$js_version = $theme_version . '.' . filemtime(get_template_directory() . '/library/assets/js/theme'.$minified.'.js');

		wp_enqueue_style('websquare-styles', get_template_directory_uri() . '/library/assets/css/theme'.$minified.'.css', array(), $css_version);
		wp_enqueue_script('jquery');

		wp_enqueue_script('websquare-scripts', get_template_directory_uri() . '/library/assets/js/theme'.$minified.'.js', array(), $js_version, true);
		// if (is_singular() && comments_open() && get_option('thread_comments')) {
		// 	wp_enqueue_script('comment-reply');
		// }
	}
}

add_action('wp_enqueue_scripts', 'websquare_enqueue_scripts');
