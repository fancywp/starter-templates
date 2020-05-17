<?php
/*
Plugin Name: Starter Templates
Plugin URI: https://fancywp.com/plugins/starter-templates/
Description: Import free sites build with Starter Blog theme.
Author: FancyWP
Author URI: https://fancywp.com/about/
Version: 1.0.0
Text Domain: starter-templates
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

/*
When I first got into technology I didn't really understand what open source was. 
Once I started writing software, I realized how important this would be.
- Matt Mullenweg
*/

define( 'STARTER_TEMPLATES_URL', untrailingslashit( plugins_url(  '', __FILE__ ) ) );
define( 'STARTER_TEMPLATES_PATH',dirname( __FILE__ ) );

if ( ! class_exists( 'WP_Importer' ) ) {
	defined( 'WP_LOAD_IMPORTERS' ) || define( 'WP_LOAD_IMPORTERS', true );
	require ABSPATH . '/wp-admin/includes/class-wp-importer.php';
}

// Required files
require dirname( __FILE__ ) . '/classess/class-placeholder.php';
require dirname( __FILE__ ) . '/importer/class-logger.php';
require dirname( __FILE__ ) . '/importer/class-logger-serversentevents.php';
require dirname( __FILE__ ) . '/importer/class-wxr-importer.php';
require dirname( __FILE__ ) . '/importer/class-wxr-import-info.php';
require dirname( __FILE__ ) . '/importer/class-wxr-import-ui.php';

require dirname( __FILE__ ) . '/classess/class-tgm.php';
require dirname( __FILE__ ) . '/classess/class-plugin.php';
require dirname( __FILE__ ) . '/classess/class-sites.php';
require dirname( __FILE__ ) . '/classess/class-export.php';
require dirname( __FILE__ ) . '/classess/class-ajax.php';


Starter_Templates::get_instance();
new Starter_Templates_Ajax();

/**
 * Redirect to import page
 *
 * @param $plugin
 * @param bool|false $network_wide
 */
function starter_templates_plugin_activate( $plugin, $network_wide = false ) {
    if ( ! $network_wide &&  $plugin == plugin_basename( __FILE__ ) ) {

        $url = add_query_arg(
            array(
                'page' => 'starter-templates'
            ),
            admin_url('themes.php')
        );

        wp_redirect($url);
        die();

    }
}
add_action( 'activated_plugin', 'starter_templates_plugin_activate', 90, 2 );

if ( is_admin() ){
	function starter_templates_admin_footer( $html ){
		if( isset( $_REQUEST['dev'] ) ) {
			$sc = get_current_screen();
			if ( $sc->id == 'appearance_page_starter-templates' ) {
				$html = '<a class="page-title-action" href="' . admin_url( 'export.php?content=all&download=true&from_starter=placeholder' ) . '">Export XML Placeholder</a> - <a class="page-title-action" href="' . admin_url( 'export.php?content=all&download=true&from_starter' ) . '">Export XML</a> - <a class="page-title-action" href="' . admin_url( 'admin-ajax.php?action=cs_export' ) . '">Export Config</a>';
			}
		}
		return $html;
	}
	add_filter( 'update_footer', 'starter_templates_admin_footer', 199 );
}
