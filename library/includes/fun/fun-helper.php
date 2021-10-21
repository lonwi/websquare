<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

if (!function_exists('print_result')) {
	function print_result($result = "", $admin = false)
	{
		if ($result != "") {
			if ($admin) {
				if (current_user_can('manage_options')) {
					echo '<pre>';
					print_r($result);
					echo '</pre>';
				}
			} else {
				echo '<pre>';
				print_r($result);
				echo '</pre>';
			}
		}
	}
}
