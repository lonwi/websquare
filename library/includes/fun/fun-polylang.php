<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

add_action('init', 'websquare_enable_sessions');

if (!function_exists('websquare_enable_sessions')) {
	/**
	 * Make sure that sessions are enabled.
	 */
	function websquare_enable_sessions()
	{
		if (!session_id()) {
			session_start();
		}
	}
}

// add_action('template_redirect', 'websquare_language_switch');

if (!function_exists('websquare_language_switch')) {
	function websquare_language_switch()
	{
		if (function_exists('pll_languages_list')) {
			global $wp;
			$current_url = home_url(add_query_arg(array(), $wp->request));
			if (isset($_GET['lang'])) {
				$_SESSION['language'] = $_GET['lang'];
				wp_redirect($current_url);
				exit();
			} else {
				if (!isset($_SESSION['language'])) {
					$selected_language = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
					$accepted_languages = ['ar', 'en'];
					$selected_language = in_array($selected_language, $accepted_languages) ? $selected_language : 'en';
					$_SESSION['language'] = $selected_language;
				}
			}
		}
	}
}

// function translate_date($language, $d, $m, $y)
// {

// 	if ($language == 'ar') {
// 		$months = array('January' => 'يناير', 'February' => 'فبراير', 'March' => 'مارس', 'April' => 'أبريل', 'May' => 'مايو', 'June' => 'يونيو', 'July' => 'يوليو', 'August' => 'أغسطس', 'September' => 'سبتمبر', 'October' => 'أكتوبر', 'November' => 'نوفمبر', 'December' => 'ديسمبر');
// 		$month = $months[$m];

// 		$numbers = array('0' => '.', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩');

// 		$y = str_split($y);
// 		$year = '';
// 		foreach ($y as $k => $v) {
// 			$year .= $numbers[$v];
// 		}

// 		$d = str_split($d);
// 		$day = '';
// 		foreach ($d as $k => $v) {
// 			$day .= $numbers[$v];
// 		}

// 		$output = $day . ' ' . $month . ' ' . $year;
// 	} else {
// 		$output = $d . ' ' . $m . ' ' . $y;
// 	}

// 	return $output;
// }

// function translate_number_date($language, $d, $m, $y)
// {

// 	if ($language == 'ar') {
// 		$numbers = array('0' => '.', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩');

// 		$m = str_split($m);
// 		$month = '';
// 		foreach ($m as $k => $v) {
// 			$month .= $numbers[$v];
// 		}

// 		$y = str_split($y);
// 		$year = '';
// 		foreach ($y as $k => $v) {
// 			$year .= $numbers[$v];
// 		}

// 		$d = str_split($d);
// 		$day = '';
// 		foreach ($d as $k => $v) {
// 			$day .= $numbers[$v];
// 		}

// 		$output = $day . '/' . $month . '/' . $year;
// 	} else {
// 		$output = $d . '/' . $m . '/' . $y;
// 	}

// 	return $output;
// }

// function polylang_langswitcher() {
// 	$output = '';
// 	if ( function_exists( 'pll_the_languages' ) ) {
// 		$args   = [
// 			'show_flags' => 0,
// 			'show_names' => 1,
// 			'hide_current' => 1,
// 			'echo'       => 0,
// 		];
// 		$output = '<ul class="polylang-langswitcher">'.pll_the_languages( $args ). '</ul>';
// 	}
// 	return $output;
// }
// add_shortcode( 'langswitcher', 'polylang_langswitcher' );

// add_filter('pll_get_post_types', 'add_cpt_to_pll', 10, 2);
// function add_cpt_to_pll($post_types, $hide) {
//     if ($hide){
//         unset($post_types['disclosure']);
//         unset($post_types['award']);
//         unset($post_types['news']);
//         unset($post_types['report']);
//         unset($post_types['fleet']);
//         unset($post_types['member']);
//         unset($post_types['customer']);
//     }else{
//         $post_types['award'] = 'award';
//         $post_types['news'] = 'news';
//         $post_types['disclosure'] = 'disclosure';
//         $post_types['report'] = 'report';
//         $post_types['fleet'] = 'fleet';
//         $post_types['member'] = 'member';
//         $post_types['customer'] = 'customer';
// 			}
//     return $post_types;
// }
