<?php
if ( !defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'csb_fs' ) ) {
	function csb_fs() {
		global $csb_fs;

		if ( !isset( $csb_fs ) ) {
			require_once CSB_DIR_PATH . '/vendor/freemius-lite/start.php';

			$csb_fs = fs_lite_dynamic_init( [
				'id'					=> '14795',
				'slug'					=> 'content-slider-block',
				'__FILE__'				=> CSB_DIR_PATH . 'plugin.php',
				'premium_slug'			=> 'content-slider-block-pro',
				'type'					=> 'plugin',
				'public_key'			=> 'pk_9c4ec15b2a1340392c3932bd66c9e',
				'is_premium'			=> false,
				'premium_suffix'		=> 'Pro',
				'has_premium_version'	=> true,
				'has_addons'			=> false,
				'has_paid_plans'		=> true,
				'menu'					=> [
					'slug'			=> 'content-slider-block',
					'first-path'	=> 'tools.php?page=content-slider-block',
					'parent'		=> [
						'slug'	=> 'tools.php'
					],
					'contact'		=> false,
					'support'		=> false
				]
			] );
		}

		return $csb_fs;
	}

	csb_fs();
	do_action( 'csb_fs_loaded' );
}
