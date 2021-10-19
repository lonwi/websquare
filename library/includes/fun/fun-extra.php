<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

if (!function_exists('websquare_remove_post_type_support')) {
	/**
	 * Remove support for a feature from a post type.
	 */
	function websquare_remove_post_type_support()
	{
		unregister_taxonomy_for_object_type('post_tag', 'post');
		unregister_taxonomy_for_object_type('category', 'post');
		remove_post_type_support('page', 'editor');
		remove_post_type_support('post', 'editor');
	}
}
add_action('init', 'websquare_remove_post_type_support');

if (!function_exists('websquare_upload_mimes')) {
	function websquare_upload_mimes($mime_types)
	{
		$mime_types['svg'] = 'image/svg+xml'; //Adding svg extension
		return $mime_types;
	}
}
add_filter('upload_mimes', 'websquare_upload_mimes', 1, 1);

if (!function_exists('websquare_pingback')) {
	/**
	 * Add a pingback url auto-discovery header for single posts of any post type.
	 */
	function websquare_pingback()
	{
		if (is_singular() && pings_open()) {
			echo '<link rel="pingback" href="' . esc_url(get_bloginfo('pingback_url')) . '">' . "\n";
		}
	}
}
add_action('wp_head', 'websquare_pingback');

if (!function_exists('websquare_mobile_web_app_meta')) {
	/**
	 * Add mobile-web-app meta.
	 */
	function websquare_mobile_web_app_meta()
	{
		echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
		echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
		echo '<meta name="apple-mobile-web-app-title" content="' . esc_attr(get_bloginfo('name')) . ' - ' . esc_attr(get_bloginfo('description')) . '">' . "\n";
	}
}
add_action('wp_head', 'websquare_mobile_web_app_meta');

if (!function_exists('websquare_insights_permalinks')) {
	function websquare_insights_permalinks($post_link, $post)
	{
		if (is_object($post) && $post->post_type == 'insights') {
			$terms = wp_get_object_terms($post->ID, 'insights_category');
			if ($terms) {
				return str_replace('%insights_category%', $terms[0]->slug, $post_link);
			}
		}
		return $post_link;
	}
}
add_filter('post_type_link', 'websquare_insights_permalinks', 1, 2);
