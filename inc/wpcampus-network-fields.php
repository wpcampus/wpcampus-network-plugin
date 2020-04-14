<?php

if ( ! function_exists( 'acf_add_local_field_group' ) ) {
	return;
}

acf_add_local_field_group(
	[
		'key'                   => 'group_analytics',
		'title'                 => 'Analytics',
		'fields'                => [
			[
				'key'               => 'field_enable_google_analytics',
				'label'             => 'Enable Google Analytics',
				'name'              => 'wpc_google_analytics_enable',
				'type'              => 'true_false',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'message'           => 'Enable Google Analytics',
				'default_value'     => 0,
				'ui'                => 1,
				'ui_on_text'        => '',
				'ui_off_text'       => '',
			],
			[
				'key'               => 'field_google_analytics_tracking_id',
				'label'             => 'Google Analytics Tracking ID',
				'name'              => 'wpc_google_analytics_tracking_id',
				'type'              => 'text',
				'instructions'      => 'The tracking ID can be found under Property Settings in the Google Analytics admin.',
				'required'          => 1,
				'conditional_logic' => [
					[
						[
							'field'    => 'field_enable_google_analytics',
							'operator' => '==',
							'value'    => '1',
						],
					],
				],
				'default_value'     => '',
				'placeholder'       => 'The ID should start with "UA-"',
				'prepend'           => '',
				'append'            => '',
				'maxlength'         => '',
			],
		],
		'location'              => [
			[
				[
					'param'    => 'options_page',
					'operator' => '==',
					'value'    => 'wpcampus-settings',
				],
			],
		],
		'menu_order'            => 0,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen'        => '',
		'active'                => true,
		'description'           => '',
	]
);

acf_add_local_field_group(
	[
		'key'                   => 'group_5e9624dd7b294',
		'title'                 => 'WPCampus: Breacrumbs',
		'fields'                => [
			[
				'key'               => 'field_5e9640bcb891a',
				'label'             => 'Text',
				'name'              => 'wpc_crumb_text',
				'type'              => 'text',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'default_value'     => '',
				'placeholder'       => 'Overwrite the text used for the crumb. Default is page title.',
				'prepend'           => '',
				'append'            => '',
				'maxlength'         => '',
			],
			[
				'key'               => 'field_5e964127b891b',
				'label'             => 'ARIA label',
				'name'              => 'wpc_crumb_aria_label',
				'type'              => 'text',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'default_value'     => '',
				'placeholder'       => 'If necessary, provide an ARIA label for the crumb link.',
				'prepend'           => '',
				'append'            => '',
				'maxlength'         => '',
			],
		],
		'location'              => [
			[
				[
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'page',
				],
			],
		],
		'menu_order'            => 0,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'left',
		'instruction_placement' => 'field',
		'hide_on_screen'        => '',
		'active'                => true,
		'description'           => '',
	]
);
