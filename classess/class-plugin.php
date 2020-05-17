<?php
class Starter_Templates_Plugin extends TGM_Plugin_Activation{

    static $instance;

    /**
     * Returns the singleton instance of the class.
     *
     * @since 2.4.0
     *
     * @return \TGM_Plugin_Activation The TGM_Plugin_Activation object.
     */
    public static function get_instance() {
        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof self ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    protected function activate_single_plugin( $file_path, $slug, $automatic = false ) {
        if ( $this->can_plugin_activate( $slug ) ) {
            $activate = activate_plugin( $file_path, '', false,  false );
        }

        return true;
    }

    function ajax(){
        $plugin = sanitize_text_field( $_REQUEST['plugin'] );
       //$_GET['plugin'] = 'contact-form-7';
        $_GET['plugin'] = $plugin;
        if ( ! current_user_can( 'install_plugins' ) ) {
            die( 'access_denied' );
        }
        ob_start();
        $action = sanitize_text_field( $_REQUEST['action'] );
        $did_action = '';
        $this->do_register_plugin( $plugin );
        if ( $action == 'cs_install_plugin' ) {
            $did_action = 'installed';
            $_GET['tgmpa-install'] = 'install-plugin';
            // set nonce for install plugin
            $nonce = wp_create_nonce( 'tgmpa-install' );
            $_GET['tgmpa-nonce'] = $nonce;
            $_REQUEST['tgmpa-nonce'] = $nonce;
            $this->do_plugin_install();
        } else if( $action == 'cs_active_plugin' ){
            // set nonce for active plugin
            $did_action = 'activated';
            $nonce = wp_create_nonce('tgmpa-activate');
            $_GET['tgmpa-nonce'] = $nonce;
            $_REQUEST['tgmpa-nonce'] = $nonce;
            $_GET['tgmpa-activate'] = 'activate-plugin';
            $this->do_plugin_install();
        }

        $msg = ob_get_clean();
        ob_end_clean();
        die( $plugin.'_'.$did_action );

    }


    function do_register_plugin( $slug ){
        // This is an example of how to include a plugin from the WordPress Plugin Repository.
        $this->register(
            array(
                'name'      => $slug,
                'slug'      => $slug,
                'required'  => false
                )
        );

        $config = array(
            'id'           => 'starter-templates-plugins',       // Unique ID for hashing notices for multiple instances of TGMPA.
            'default_path' => '',                      // Default absolute path to bundled plugins.
            'menu'         => 'tgmpa-install-plugins', // Menu slug.
            'parent_slug'  => 'plugins.php',            // Parent menu slug.
            'capability'   => 'manage_options',         // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
            'has_notices'  => true,                    // Show admin notices or not.
            'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
            'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
            'is_automatic' => false,                   // Automatically activate plugins after installation or not.
            'message'      => '',                      // Message to output right before the plugins table.
        );

        $this->config( $config );

    }

}