<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

if (!function_exists('websquare_register_nav_menus')) {
	function websquare_register_nav_menus()
	{
		register_nav_menus(
			array(
				'header-navigation-main' => 'Header Navigation - Main',
				'header-navigation-quick' => 'Header Navigation - Quick Links ',
				'footer-navigation-main' => 'Footer Navigation - Main',
				'footer-navigation-documents' => 'Footer Navigation - Documents',
			)
		);
	}
}

add_action('after_setup_theme', 'websquare_register_nav_menus');

if (!function_exists('header_navigation_function')) {
	function header_navigation_function()
	{

		$walker = new Custom_Walker_Nav_Menu;
		$output = wp_nav_menu(array(
			'container' => false,
			'container_class' => '',
			'menu' => 'Header Navigation',
			'menu_class' => 'header-navigation-main',
			'theme_location' => 'header-navigation-main',
			'before' => '',
			'after' => '',
			'link_before' => '',
			'link_after' => '',
			'fallback_cb' => '',
			'walker' => $walker,
			'echo' => false,
		));
		ob_start();
		echo $output;
		return ob_get_clean();
	}
}

add_shortcode('header_navigation', 'header_navigation_function');


if (!function_exists('add_menu_description')) {
	function add_menu_description($item_output, $item, $depth, $args)
	{
		global $description;
		$description = __($item->post_content);
		return $item_output;
	}
}

add_filter('walker_nav_menu_start_el', 'add_menu_description', 10, 4);

if (!function_exists('add_menu_title')) {
	function add_menu_title($item_output, $item, $depth, $args)
	{
		global $title;
		$title = __($item->post_excerpt ? $item->post_excerpt : $item->title);
		return $item_output;
	}
}

add_filter('walker_nav_menu_start_el', 'add_menu_title', 10, 4);

if (!class_exists('Custom_Walker_Nav_Menu')) {

	class Custom_Walker_Nav_Menu extends Walker_Nav_Menu
	{
		/**
		 * Track Whether to show parent title
		 *
		 * @var Boolean
		 */

		/**
		 * What the class handles.
		 *
		 * @since 3.0.0
		 * @var string
		 *
		 * @see Walker::$tree_type
		 */
		public $tree_type = array('post_type', 'taxonomy', 'custom');

		/**
		 * Database fields to use.
		 *
		 * @since 3.0.0
		 * @todo Decouple this.
		 * @var array
		 *
		 * @see Walker::$db_fields
		 */
		public $db_fields = array(
			'parent' => 'menu_item_parent',
			'id'     => 'db_id',
		);

		/**
		 * Starts the list before the elements are added.
		 *
		 * @since 3.0.0
		 *
		 * @see Walker::start_lvl()
		 *
		 * @param string   $output Used to append additional content (passed by reference).
		 * @param int      $depth  Depth of menu item. Used for padding.
		 * @param stdClass $args   An object of wp_nav_menu() arguments.
		 */
		public function start_lvl(&$output, $depth = 0, $args = null)
		{
			if (isset($args->item_spacing) && 'discard' === $args->item_spacing) {
				$t = '';
				$n = '';
			} else {
				$t = "\t";
				$n = "\n";
			}
			$indent = str_repeat($t, $depth);

			// Default class.
			$classes = array('sub-menu');
			/**
			 * Filters the CSS class(es) applied to a menu list element.
			 *
			 * @since 4.8.0
			 *
			 * @param string[] $classes Array of the CSS classes that are applied to the menu `<ul>` element.
			 * @param stdClass $args    An object of `wp_nav_menu()` arguments.
			 * @param int      $depth   Depth of menu item. Used for padding.
			 */
			$class_names = implode(' ', apply_filters('nav_menu_submenu_css_class', $classes, $args, $depth));
			$class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';
			$extra_output = "";
			if ($depth === 1) {
				global $description;
				global $title;
				$extra_output .= '<div class="menu-title">';
				if (isset($title) && !empty($title)) {
					$extra_output .= '<h2>' . $title . '</h2>';
				}
				if (isset($description) && !empty($description)) {
					$extra_output .= '<p>' . $description . '</[div]>';
				}
				$extra_output .= '</div>';
			}
			$output .= "{$n}{$indent}<ul$class_names>$extra_output{$n}";
		}

		/**
		 * Ends the list of after the elements are added.
		 *
		 * @since 3.0.0
		 *
		 * @see Walker::end_lvl()
		 *
		 * @param string   $output Used to append additional content (passed by reference).
		 * @param int      $depth  Depth of menu item. Used for padding.
		 * @param stdClass $args   An object of wp_nav_menu() arguments.
		 */
		public function end_lvl(&$output, $depth = 0, $args = null)
		{
			if (isset($args->item_spacing) && 'discard' === $args->item_spacing) {
				$t = '';
				$n = '';
			} else {
				$t = "\t";
				$n = "\n";
			}
			$indent  = str_repeat($t, $depth);
			$output .= "$indent</ul>{$n}";
		}

		/**
		 * Starts the element output.
		 *
		 * @since 3.0.0
		 * @since 4.4.0 The {@see 'nav_menu_item_args'} filter was added.
		 *
		 * @see Walker::start_el()
		 *
		 * @param string   $output Used to append additional content (passed by reference).
		 * @param WP_Post  $item   Menu item data object.
		 * @param int      $depth  Depth of menu item. Used for padding.
		 * @param stdClass $args   An object of wp_nav_menu() arguments.
		 * @param int      $id     Current item ID.
		 */
		public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
		{
			// if (2 === $depth) {
			// 	$this->show_parent_title = false;
			// 	$object_id = get_post_meta($item->menu_item_parent, '_menu_item_object_id', true);
			// 	$object    = get_post_meta($item->menu_item_parent, '_menu_item_object',    true);
			// 	$type      = get_post_meta($item->menu_item_parent, '_menu_item_type',      true);
			// 	echo '<pre>';
			// 	print_r(get_post($object_id)->post_title);
			// 	echo '</pre>';
			// 	if ('post_type' == $type) {
			// 		$title = get_post($object_id)->post_title;
			// 	} elseif ('taxonomy' == $type) {
			// 		$title = get_term($object_id, $object)->name;
			// 	}
			// 	$output .= "<h2>" . $title . "</h2>";
			// }

			if (isset($args->item_spacing) && 'discard' === $args->item_spacing) {
				$t = '';
				$n = '';
			} else {
				$t = "\t";
				$n = "\n";
			}
			$indent = ($depth) ? str_repeat($t, $depth) : '';

			$classes   = empty($item->classes) ? array() : (array) $item->classes;
			$classes[] = 'menu-item-' . $item->ID;

			/**
			 * Filters the arguments for a single nav menu item.
			 *
			 * @since 4.4.0
			 *
			 * @param stdClass $args  An object of wp_nav_menu() arguments.
			 * @param WP_Post  $item  Menu item data object.
			 * @param int      $depth Depth of menu item. Used for padding.
			 */
			$args = apply_filters('nav_menu_item_args', $args, $item, $depth);

			/**
			 * Filters the CSS classes applied to a menu item's list item element.
			 *
			 * @since 3.0.0
			 * @since 4.1.0 The `$depth` parameter was added.
			 *
			 * @param string[] $classes Array of the CSS classes that are applied to the menu item's `<li>` element.
			 * @param WP_Post  $item    The current menu item.
			 * @param stdClass $args    An object of wp_nav_menu() arguments.
			 * @param int      $depth   Depth of menu item. Used for padding.
			 */
			$class_names = implode(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
			$class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

			/**
			 * Filters the ID applied to a menu item's list item element.
			 *
			 * @since 3.0.1
			 * @since 4.1.0 The `$depth` parameter was added.
			 *
			 * @param string   $menu_id The ID that is applied to the menu item's `<li>` element.
			 * @param WP_Post  $item    The current menu item.
			 * @param stdClass $args    An object of wp_nav_menu() arguments.
			 * @param int      $depth   Depth of menu item. Used for padding.
			 */
			$id = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
			$id = $id ? ' id="' . esc_attr($id) . '"' : '';

			$output .= $indent . '<li' . $id . $class_names . '>';

			$atts           = array();
			$atts['title']  = !empty($item->attr_title) ? $item->attr_title : '';
			$atts['target'] = !empty($item->target) ? $item->target : '';
			if ('_blank' === $item->target && empty($item->xfn)) {
				$atts['rel'] = 'noopener';
			} else {
				$atts['rel'] = $item->xfn;
			}
			$atts['href']         = !empty($item->url) ? $item->url : '';
			$atts['aria-current'] = $item->current ? 'page' : '';

			/**
			 * Filters the HTML attributes applied to a menu item's anchor element.
			 *
			 * @since 3.6.0
			 * @since 4.1.0 The `$depth` parameter was added.
			 *
			 * @param array $atts {
			 *     The HTML attributes applied to the menu item's `<a>` element, empty strings are ignored.
			 *
			 *     @type string $title        Title attribute.
			 *     @type string $target       Target attribute.
			 *     @type string $rel          The rel attribute.
			 *     @type string $href         The href attribute.
			 *     @type string $aria-current The aria-current attribute.
			 * }
			 * @param WP_Post  $item  The current menu item.
			 * @param stdClass $args  An object of wp_nav_menu() arguments.
			 * @param int      $depth Depth of menu item. Used for padding.
			 */
			$atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);

			$attributes = '';
			foreach ($atts as $attr => $value) {
				if (is_scalar($value) && '' !== $value && false !== $value) {
					$value       = ('href' === $attr) ? esc_url($value) : esc_attr($value);
					$attributes .= ' ' . $attr . '="' . $value . '"';
				}
			}

			/** This filter is documented in wp-includes/post-template.php */
			$title = apply_filters('the_title', $item->title, $item->ID);

			/**
			 * Filters a menu item's title.
			 *
			 * @since 4.4.0
			 *
			 * @param string   $title The menu item's title.
			 * @param WP_Post  $item  The current menu item.
			 * @param stdClass $args  An object of wp_nav_menu() arguments.
			 * @param int      $depth Depth of menu item. Used for padding.
			 */
			$title = apply_filters('nav_menu_item_title', $title, $item, $args, $depth);

			$item_output  = $args->before;
			$item_output .= '<a' . $attributes . '>';
			$item_output .= $args->link_before . $title . $args->link_after;
			if ($depth > 0 && $args->walker->has_children) {
				$item_output .= '<i class="fas fa-chevron-right"></i>';
			}
			$item_output .= '</a>';
			$item_output .= $args->after;
			// echo '<pre>';
			// print_r($args->walker->has_children);
			// echo '</pre>';
			/**
			 * Filters a menu item's starting output.
			 *
			 * The menu item's starting output only includes `$args->before`, the opening `<a>`,
			 * the menu item's title, the closing `</a>`, and `$args->after`. Currently, there is
			 * no filter for modifying the opening and closing `<li>` for a menu item.
			 *
			 * @since 3.0.0
			 *
			 * @param string   $item_output The menu item's starting HTML output.
			 * @param WP_Post  $item        Menu item data object.
			 * @param int      $depth       Depth of menu item. Used for padding.
			 * @param stdClass $args        An object of wp_nav_menu() arguments.
			 */
			$output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
		}

		/**
		 * Ends the element output, if needed.
		 *
		 * @since 3.0.0
		 *
		 * @see Walker::end_el()
		 *
		 * @param string   $output Used to append additional content (passed by reference).
		 * @param WP_Post  $item   Page data object. Not used.
		 * @param int      $depth  Depth of page. Not Used.
		 * @param stdClass $args   An object of wp_nav_menu() arguments.
		 */
		public function end_el(&$output, $item, $depth = 0, $args = null)
		{
			if (isset($args->item_spacing) && 'discard' === $args->item_spacing) {
				$t = '';
				$n = '';
			} else {
				$t = "\t";
				$n = "\n";
			}
			$output .= "</li>{$n}";
		}
	}
}
