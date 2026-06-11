<?php
/**
 * Plugin Name: Content Slider Block
 * Description: Display your goal to your visitor in bountiful way with content slider block.
 * Version: 3.2.1
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * Plugin URI: https://bplugins.com/products/content-slider-block
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: content-slider-block
 * Requires at least: 6.5
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * @fs_premium_only /vendor/freemius, /includes/fs.php, /includes/admin/CPT.php, includes/LicenseActivation.php, /build/admin/post.asset.php, /build/admin/post.css, /build/admin/post.js
 * @fs_free_only /vendor/freemius-lite, /includes/fs-lite.php, /includes/admin/SubMenu.php
 */

// ABS PATH
if ( !defined( 'ABSPATH' ) ) { exit; }

if ( function_exists( 'csb_fs' ) ) {
	csb_fs()->set_basename( true, __FILE__ );
}else{
	// Constant
	define( 'CSB_VERSION', ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? time() : '3.2.1' );
	define( 'CSB_DIR_URL', plugin_dir_url( __FILE__ ) );
	define( 'CSB_DIR_PATH', plugin_dir_path( __FILE__ ) );

	require_once CSB_DIR_PATH . 'includes/fs-lite.php';
	require_once CSB_DIR_PATH . 'includes/admin/SubMenu.php';

	if( !class_exists( 'CSBPlugin' ) ){
		/**
		 * Class CSBPlugin
		 *
		 * Main initialization class for the Content Slider Block plugin.
		 *
		 * @since 1.0.0
		 */
		class CSBPlugin{
			/**
			 * CSBPlugin constructor.
			 *
			 * Registers plugin hooks, filters, and scripts.
			 *
			 * @since 1.0.0
			 */
			function __construct(){
				add_action( 'init', [ $this, 'onInit' ] );
				add_filter( 'block_categories_all', [$this, 'blockCategories'] );
				add_action( 'admin_enqueue_scripts', [ $this, 'adminEnqueueScripts' ] );
				add_action( 'enqueue_block_editor_assets', [$this, 'enqueueBlockEditorAssets'] );

				add_filter( 'plugin_action_links', [$this, 'pluginActionLinks'], 10, 2 );
				add_filter( 'default_title', [$this, 'defaultTitle'], 10, 2 );
				add_filter( 'default_content', [$this, 'defaultContent'], 10, 2 );
			}
			
			/**
			 * Filters the default post title when creating a new page from the dashboard link.
			 *
			 * @since 1.0.0
			 * @param string  $title The default post title.
			 * @param WP_Post $post  The post object.
			 * @return string The filtered post title.
			 */
			function defaultTitle( $title, $post ) {
				if ( 'page' === $post->post_type && isset( $_GET['title'] ) ) {
					$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

					if ( wp_verify_nonce( $nonce, 'csbCreatePage' ) ) {
						return sanitize_text_field( wp_unslash( $_GET['title'] ) );
					}
				}
				return $title;
			}

			/**
			 * Filters the default post content when creating a new page from the dashboard link.
			 *
			 * @since 1.0.0
			 * @param string  $content The default post content.
			 * @param WP_Post $post    The post object.
			 * @return string The filtered post content.
			 */
			function defaultContent( $content, $post ) {
				if ( 'page' === $post->post_type && isset( $_GET['content'] ) ) {
					$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

					if ( wp_verify_nonce( $nonce, 'csbCreatePage' ) ) {
						return wp_kses_post( wp_unslash( $_GET['content'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					}
				}
				return $content;
			}

			/**
			 * Adds action links to the plugin listing in the admin area.
			 *
			 * @since 1.0.0
			 * @param array  $links An array of plugin action links.
			 * @param string $file  Path to the plugin file relative to the plugins directory.
			 * @return array The filtered array of plugin action links.
			 */
			function pluginActionLinks( $links, $file ) {
				if( plugin_basename( __FILE__ ) === $file ) {
					$helpDemosLink = admin_url( 'tools.php?page=content-slider-block#/welcome' );

					$links['help-and-demos'] = sprintf( '<a href="%s" style="%s">%s</a>', $helpDemosLink, 'color:#FF7A00;font-weight:bold', __( 'Help & Demos', 'content-slider-block' ) );
				}
	
				return $links;
			}

			/**
			 * Initializes the plugin by registering block types.
			 *
			 * @since 1.0.0
			 */
			function onInit(){
				register_block_type( __DIR__ . '/build' );
			}

			/**
			 * Adds a custom block category for Content Slider blocks.
			 *
			 * @since 1.0.0
			 * @param array $categories Array of block categories.
			 * @return array The filtered block categories.
			 */
			function blockCategories( $categories ){
				return array_merge( [[
					'slug'	=> 'CSBlock',
					'title'	=> 'Content Slider Block',
				] ], $categories );
			} // Categories

			/**
			 * Enqueues CSS and JavaScript for the admin dashboard.
			 *
			 * @since 1.0.0
			 * @param string $hook The current admin page hook.
			 */
			function adminEnqueueScripts( $hook ) {
				if( strpos( $hook, 'content-slider-block' ) ){
					wp_enqueue_style( 'csb-admin-dashboard', CSB_DIR_URL . 'build/admin/dashboard.css', [], CSB_VERSION );

					$asset_file = include CSB_DIR_PATH . 'build/admin/dashboard.asset.php';
					wp_enqueue_script( 'csb-admin-dashboard', CSB_DIR_URL . 'build/admin/dashboard.js', array_merge( $asset_file['dependencies'], [ 'wp-util' ] ), CSB_VERSION, true );
					wp_set_script_translations( 'csb-admin-dashboard', 'content-slider-block', CSB_DIR_PATH . 'languages' );
				}
			}

			/**
			 * Enqueues inline scripts with licensing information for the block editor.
			 *
			 * @since 1.0.0
			 */
			function enqueueBlockEditorAssets(){
				wp_add_inline_script( 'csb-content-slider-block-editor-script', 'const csbpricingurl = "'. admin_url( 'tools.php?page=content-slider-block#/pricing' ) .'";', 'before' );
			}

			/**
			 * Renders the dashboard HTML element where the React application mounts.
			 *
			 * @since 1.0.0
			 */
			static function renderDashboard(){ ?>
				<div
					id='csbDashboard'
					data-info='<?php echo esc_attr( wp_json_encode( [
						'version' => CSB_VERSION,
						'adminUrl' => admin_url(),
						'startUrl' => admin_url( 'post-new.php?post_type=page&title=' . rawurlencode( 'Content Slider Block' ) . '&content=' . rawurlencode( '<!-- wp:csb/content-slider-block /-->' ) . '&nonce=' . wp_create_nonce( 'csbCreatePage' ) )
					] ) ); ?>'
				></div>
			<?php }
		}
		new CSBPlugin;
	}
}