<?php

Class Starter_Templates {
    static $_instance = null;
    const THEME_NAME = 'starter-blog';


    function admin_scripts( $id ){
        if( $id == 'appearance_page_starter-templates' ){
            wp_localize_script('jquery', 'Starter_Templates',  $this->get_localize_script() );
            wp_enqueue_style('owl.carousel', STARTER_TEMPLATES_URL.'/assets/css/owl.carousel.css' );
            wp_enqueue_style('owl.theme.default', STARTER_TEMPLATES_URL.'/assets/css/owl.theme.default.css' );
            wp_enqueue_style('starter-templates', STARTER_TEMPLATES_URL.'/assets/css/starter-templates.css' );

            wp_enqueue_script('owl.carousel', STARTER_TEMPLATES_URL.'/assets/js/owl.carousel.min.js',  array( 'jquery' ), false, true );
            wp_enqueue_script('starter-templates', STARTER_TEMPLATES_URL.'/assets/js/backend.js',  array( 'jquery', 'underscore' ), false, true );
        }
    }

    static function get_instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
            add_action( 'admin_menu', array( self::$_instance, 'add_menu' ), 50 );
            add_action( 'admin_enqueue_scripts', array( self::$_instance, 'admin_scripts' ) );
            add_action( 'admin_notices', array( self::$_instance, 'admin_notice' ) );
        }
        return self::$_instance;
    }

    function admin_notice( $hook ) {
        $screen = get_current_screen();
        if( $screen->id != 'appearance_page_starter-templates' && $screen->id != 'themes' ) {
            return '';
        }

        if( get_template() == self::THEME_NAME  ) {
            return '';
        }

        $themes = wp_get_themes();
        if ( isset( $themes[ self::THEME_NAME ] ) ) {
            $url = esc_url( 'themes.php?theme='.self::THEME_NAME );
        } else {
            $url = esc_url( 'theme-install.php?search='.self::THEME_NAME );
        }

        $html = sprintf( '<strong>Starter Templates Library</strong> requires <strong>Starter Blog</strong> theme to be activated to work. <a href="%1$s">Install &amp; Activate Now</a>', $url );
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <?php echo $html; ?>
            </p>
        </div>
        <?php
    }

    static function get_api_url(){
        return apply_filters( 'starter_templates/api_url', 'https://wpfancy.github.io/starter-templates/sites.json' );
    }

    function add_menu() {
        add_theme_page(__( 'Starter Templates', 'starter-templates' ), __( 'Starter Templates', 'starter-templates' ), 'edit_theme_options', 'starter-templates', array( $this, 'page' ));
    }

    function page(){
        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">'.__( 'Starter Templates Library', 'starter-templates' ).'</h1><hr class="wp-header-end">';
        require_once STARTER_TEMPLATES_PATH.'/templates/dashboard.php';
        require_once STARTER_TEMPLATES_PATH.'/templates/modal.php';
        echo '</div>';
        require_once STARTER_TEMPLATES_PATH.'/templates/preview.php';
    }

    function get_installed_plugins(){
        // Check if get_plugins() function exists. This is required on the front end of the
        // site, since it is in a file that is normally only loaded in the admin.
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_plugins = get_plugins();
        if ( ! is_array( $all_plugins ) ) {
            $all_plugins = array();
        }

        $plugins = array();
        foreach ( $all_plugins as $file => $info ) {
            $slug = dirname( $file );
            $plugins[ $slug ] = $info['Name'];
        }

        return $plugins;
    }

    function get_activated_plugins(){
        $activated_plugins = array();
        foreach( ( array ) get_option('active_plugins') as $plugin_file ) {
            $plugin_file = dirname( $plugin_file );
            $activated_plugins[ $plugin_file ] = $plugin_file;
        }
        return $activated_plugins;
    }

    function get_support_plugins(){
        $plugins = array(
            'starter-pro' => _x( 'Starter Blog Pro', 'plugin-name', 'starter-templates' ),

            'elementor' => _x( 'Elementor', 'plugin-name', 'starter-templates' ),
            'elementor-pro' => _x( 'Elementor Pro', 'plugin-name', 'starter-templates' ),
            'beaver-builder-lite-version' => _x( 'Beaver Builder', 'plugin-name', 'starter-templates' ),
            'contact-form-7' => _x( 'Contact Form 7', 'plugin-name', 'starter-templates' ),

            'breadcrumb-navxt' => _x( 'Breadcrumb NavXT', 'plugin-name', 'starter-templates' ),
            'jetpack' => _x( 'JetPack', 'plugin-name', 'starter-templates' ),
            'qubely' => _x( 'qubely', 'plugin-name', 'starter-templates' ),
            'easymega' => _x( 'Mega menu', 'plugin-name', 'starter-templates' ),
            'polylang' => _x( 'Polylang', 'plugin-name', 'starter-templates' ),
            'loco-translate' => _x( 'Loco Translate', 'plugin-name', 'starter-templates' ),
            'woocommerce' => _x( 'WooCommerce', 'plugin-name', 'starter-templates' ),
            'give' => _x( 'Give - Donation Plugin and Fundraising Platform', 'plugin-name', 'starter-templates' ),
        );

        return $plugins;
    }

    function is_license_valid(){

        if ( ! class_exists('StarterBlog_Pro' ) ) {
            return false;
        }
	    $pro_data = get_option('starterblog_pro_license_data');
	    if ( ! is_array( $pro_data ) ) {
	        return false;
        }
	    if ( ! isset( $pro_data['license'] ) ) {
		    return false;
	    }

	    if ( ! isset( $pro_data['data'] ) || ! is_array( $pro_data['data'] ) ) {
		    return false;
	    }

	    if ( isset( $pro_data['data']['license'] ) && $pro_data['data']['license'] == 'valid' &&  $pro_data['data']['success'] ) {
            return true;
        }

        return false;
    }

    function get_localize_script(){

        $args = array(
            'api_url' => self::get_api_url(),
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'is_admin' => is_admin(),
            'try_again' => __( 'Try Again', 'starter-templates' ),
            'pro_text' => __( 'Pro only', 'starter-templates' ),
            'activated_plugins' => $this->get_activated_plugins(),
            'installed_plugins' => $this->get_installed_plugins(),
            'support_plugins' => $this->get_support_plugins(),
            'license_valid' =>   $this->is_license_valid(),
        );

        $args['elementor_clear_cache_nonce'] = wp_create_nonce( 'elementor_clear_cache' );
        $args['elementor_reset_library_nonce'] = wp_create_nonce( 'elementor_reset_library' );

        return $args;
    }

}
