<?php
function cmb_team() {
  $prefix = 'team';

  $cmb = new_cmb2_box( array(
    'title'         => 'Content',
    'id'            => $prefix.'_content',
    'object_types'  => array( $prefix ),
    'context'       => 'normal',
    'priority'      => 'low'
  ) );

  $cmb->add_field( array(
    'name'        => 'Position',
  	'id'          => $prefix . '_position',
    'type'        => 'text',
  ) );

  $cmb->add_field( array(
    'name'        => 'Description',
  	'id'          => $prefix . '_description',
    'type' => 'wysiwyg',
    'options' => array(
      'media_buttons' => false,
      'teeny' => true,
    ),
  ) );

}
?>
