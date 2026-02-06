<?php
/**
 * Plugin Name: Content Slider Block
 * Description: Display your goal to your visitor in bountiful way with content slider block.
 * Version: 3.1.9
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: content-slider-block
   */

// ABS PATH
if ( !defined( 'ABSPATH' ) ) { exit; }

if ( function_exists( 'csb_fs' ) ) {
    csb_fs()->set_basename( false, __FILE__ );
}else{
	// Constant
	define( 'CSB_VERSION', isset( $_SERVER['HTTP_HOST'] ) && ( 'localhost' === $_SERVER['HTTP_HOST'] || 'plugins.local' === $_SERVER['HTTP_HOST'] ) ? time() : '3.1.9' );
	define( 'CSB_DIR_URL', plugin_dir_url( __FILE__ ) );
	define( 'CSB_DIR_PATH', plugin_dir_path( __FILE__ ) );
	define( 'CSB_HAS_PRO', file_exists( CSB_DIR_PATH . 'vendor/freemius/start.php' ) );

	if ( CSB_HAS_PRO ) {
		require_once CSB_DIR_PATH . 'includes/fs.php';
		require_once CSB_DIR_PATH . 'includes/admin/CPT.php';
	}else{
		require_once CSB_DIR_PATH . 'includes/fs-lite.php';
		require_once CSB_DIR_PATH . 'includes/admin/SubMenu.php';
	}

	require_once CSB_DIR_PATH . 'includes/Patterns.php';

	function csbIsPremium(){
		return CSB_HAS_PRO ? csb_fs()->can_use_premium_code() : false;
	}

	class CSBPlugin{
		function __construct(){
			add_action( 'init', [ $this, 'onInit' ] );
			add_filter( 'block_categories_all', [$this, 'blockCategories'] );
			add_action( 'admin_enqueue_scripts', [ $this, 'adminEnqueueScripts' ] );
			add_action( 'enqueue_block_editor_assets', [$this, 'enqueueBlockEditorAssets'] );
		}

		function onInit(){
			register_block_type( __DIR__ . '/build' );
		}

		function blockCategories( $categories ){
			return array_merge( [[
				'slug'	=> 'CSBlock',
				'title'	=> 'Content Slider Block',
			] ], $categories );
		} // Categories

		function adminEnqueueScripts( $hook ) {
			if( strpos( $hook, 'content-slider-block' ) ){
				wp_enqueue_style( 'csb-admin-dashboard', CSB_DIR_URL . 'build/admin/dashboard.css', [], CSB_VERSION );
				wp_enqueue_script( 'csb-admin-dashboard', CSB_DIR_URL . 'build/admin/dashboard.js', [ 'react', 'react-dom' ], CSB_VERSION, true );
				wp_set_script_translations( 'csb-admin-dashboard', 'content-slider-block', CSB_DIR_PATH . 'languages' );
			}
		}

		function enqueueBlockEditorAssets(){
			wp_add_inline_script( 'csb-content-slider-block-editor-script', 'const csbpipecheck = ' . wp_json_encode( csbIsPremium() ) .'; const csbpricingurl = "'. admin_url( CSB_HAS_PRO ? 'edit.php?post_type=csb&page=content-slider-block#/pricing' : 'tools.php?page=content-slider-block#/pricing' ) .'";', 'before' );
		}

		static function renderDashboard(){ ?>
			<div
				id='csbDashboard'
				data-info='<?php echo esc_attr( wp_json_encode( [
					'version'	=> CSB_VERSION,
					'isPremium'	=> csbIsPremium(),
					'hasPro'	=> CSB_HAS_PRO
				] ) ); ?>'
			></div>
		<?php }
	}
	new CSBPlugin;
}