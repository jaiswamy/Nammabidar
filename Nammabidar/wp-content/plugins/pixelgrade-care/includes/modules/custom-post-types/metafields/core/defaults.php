<?php return
	[
	'cleanup'   =>
		[
		'switch' => [ 'switch_not_available' ],
		],
	'checks'    =>
		[
		'counter' => [ 'is_numeric', 'not_empty' ],
		],
	'processor' =>
		[
		// callback signature: (array $input, PixelgradeCare_MetafieldsProcessor $processor)

		'preupdate'  =>
			[
			// callbacks to run before update process
			// cleanup and validation has been performed on data
			],
		'postupdate' =>
			[// callbacks to run post update
			],
		],
	'errors'    =>
		[
		'is_numeric' => __( 'Numberic value required.', PixelgradeCare_MetafieldsCore::textdomain() ),
		'not_empty'  => __( 'Field is required.', PixelgradeCare_MetafieldsCore::textdomain() ),
		],
	'callbacks' =>
		[
		// cleanup callbacks
		'switch_not_available' => 'pixcare_cpt_fields_cleanup_switch_not_available',
		// validation callbacks
		'is_numeric'           => 'pixcare_cpt_fields_validate_is_numeric',
		'not_empty'            => 'pixcare_cpt_fields_validate_not_empty'
		]

	]; # config
