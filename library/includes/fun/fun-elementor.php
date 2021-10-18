<?php

function add_responsive_column_order($element, $args)
{
	$element->add_responsive_control(
		'responsive_column_order',
		[
			'label' => __('Responsive Column Order', 'elementor-extras'),
			'type' => \Elementor\Controls_Manager::NUMBER,
			'separator' => 'before',
			'selectors' => [
				'{{WRAPPER}}' => '-webkit-order: {{VALUE}}; -ms-flex-order: {{VALUE}}; order: {{VALUE}};',
			],
		]
	);
}
add_action('elementor/element/column/layout/before_section_end', 'add_responsive_column_order', 10, 3);
