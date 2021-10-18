<?php

function tax_news()
{

	$cpt = array(
		'post_type'   => 'news'
	);

	$tax = array(
		'singular' => 'Category',
		'plural'   => 'Categories',
		'slug'   => 'categories',
		'taxonomy_type'   => 'category'
	);

	register_taxonomy(
		$cpt['post_type'] . '_' . $tax['taxonomy_type'],
		array($cpt['post_type']),
		array(
			'hierarchical' => false,
			'labels' => array(
				'name'                       => $tax['plural'],
				'singular_name'              => $tax['singular'],
				'search_items'               => 'Search ' . $tax['singular'],
				'popular_items'              => 'Popular ' . $tax['plural'],
				'all_items'                  => 'All ' . $tax['plural'],
				'parent_item'                => 'Parent ' . $tax['plural'],
				'parent_item_colon'          => 'Parent ' . $tax['plural'] . ':',
				'edit_item'                  => 'Edit ' . $tax['singular'],
				'update_item'                => 'Update ' . $tax['singular'],
				'add_new_item'               => 'Add New ' . $tax['singular'],
				'new_item_name'              => 'New ' . $tax['singular'] . ' Name',
				'separate_items_with_commas' => 'Separate ' . $tax['plural'] . ' With Commas',
				'add_or_remove_items'        => 'Add or Remove ' . $tax['plural'],
				'choose_from_most_used'      => 'Choose From Most Used ' . $tax['plural'],
				'not_found'                  => 'No ' . $tax['plural'] . ' Found',
				'menu_name'                  => $tax['plural'],
			),
			'show_ui'               => true,
			'show_admin_column'     => true,
			'query_var'             => true,
			'public'                => false,
			'show_in_nav_menus'     => true,
			'show_tagcloud'         => true,
			'meta_box_cb'           => false,
			'update_count_callback' => '_update_post_term_count',
			'rewrite'               => array('slug' => $tax['slug'])
		)
	);
}
