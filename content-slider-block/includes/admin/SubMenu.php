<?php
namespace CSB\Admin;

if ( !defined( 'ABSPATH' ) ) { exit; }

class SubMenu {
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'adminMenu' ] );
	}

	function adminMenu(){
		add_submenu_page(
			'tools.php',
			__('Content Slider - bPlugins', 'content-slider-block'),
			__('Content Slider', 'content-slider-block'),
			'manage_options',
			'content-slider-block',
			[ \CSBPlugin::class, 'renderDashboard' ]
		);
	}
}
new SubMenu();