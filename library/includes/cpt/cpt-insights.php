<?php

function cpt_insights() {

  $cpt = array(
      'singular' => 'Insight',
      'plural'   => 'Insights',
      'slug'   => 'insights',
      'post_type'   => 'insights'
  );

  register_post_type( $cpt['post_type'],
    array(
      'labels'             => array(
        'name'               => sprintf( '%s', $cpt['plural'] ),
        'singular_name'      => sprintf( '%s', $cpt['singular'] ),
        'add_new'            => 'Add New',
        'add_new_item'       => sprintf( 'Add New %s', $cpt['singular'] ),
        'edit'               => 'Edit',
        'edit_item'          => sprintf( 'Edit %s', $cpt['singular'] ),
        'new_item'           => sprintf( 'New %s', $cpt['singular'] ),
        'all_items'          => sprintf( 'All %s', $cpt['plural'] ),
        'view'               => sprintf( 'View %s', $cpt['singular'] ),
        'search_items'       => sprintf( 'Search %s', $cpt['plural'] ),
        'not_found'          => sprintf( 'No %s Found', $cpt['plural'] ),
        'not_found_in_trash' => sprintf( 'No %s Found In Trash', $cpt['plural'] ),
        'parent_item_colon'  => ''
      ),
      'description'        => 'Create a '.strtolower($cpt['singular']).' item',
      'public'             => true,
      'show_ui'            => true,
      'show_in_menu'       => true,
      'publicly_queryable' => true,
      'has_archive'        => false,
      'rewrite'            => array(
        'slug' => $cpt['slug']. '/%insights_category%',
        'with_front' => false,
      ),
      'menu_position'      => 5,
      'show_in_nav_menus'  => true,
      'menu_icon'          => 'dashicons-admin-post',
      'hierarchical'       => false,
      'query_var'          => true,
      'supports'           => array(
        'title',
        'thumbnail',
      )
    )
  );

  if(is_admin()){
      add_post_type_support( $cpt['post_type'], 'page-attributes' );

      if(isset($_GET['post']) || isset($_POST['post_ID'])){
        $post_id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post_ID']) ;
      }else{
        $post_id = '';
      }

      $template_file = get_post_meta($post_id,'_wp_page_template',TRUE);
      $post_type = isset($_GET['post_type']) ? $_GET['post_type'] : get_post_type($post_id);

      if ($post_type == $cpt['post_type']) {
          add_filter( 'cmb2_meta_boxes', 'cmb_'.$cpt['post_type'] );
      }
  }

}
