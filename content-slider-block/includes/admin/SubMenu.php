<?php
namespace CSB\Admin;

if ( !defined( 'ABSPATH' ) ) { exit; }

/**
 * Class SubMenu
 *
 * Registers the administration submenu page for Content Slider Block in the free/lite version.
 *
 * @package CSB\Admin
 * @since 1.0.0
 */
class SubMenu {
	/**
	 * SubMenu constructor.
	 *
	 * Registers action hook for admin menu registration.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'adminMenu' ] );
	}

	/**
	 * Registers the Content Slider admin submenu under the Tools menu.
	 *
	 * @since 1.0.0
	 */
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