<?php
defined( 'ABSPATH' ) || exit;

/**
 * Registers plugin-bundled page templates so WordPress can
 * find and use them without a theme override.
 */
class ThreeR_Urban_Page_Templates {

    private $templates = [
        // Public pages
        '3r-urban-smart/login.php'   => 'Login',
        '3r-urban-smart/home.php'    => 'Home',
        '3r-urban-smart/about.php'   => 'About',
        '3r-urban-smart/demo.php'    => 'Request Demo',
        '3r-urban-smart/contact.php' => 'Contact',
        // Portal pages
        '3r-urban-smart/portal/dashboard.php' => 'Portal — Dashboard',
        '3r-urban-smart/portal/machine.php'   => 'Portal — Machine Detail',
        '3r-urban-smart/portal/panel.php'     => 'Portal — Live Panel',
        '3r-urban-smart/portal/alerts.php'    => 'Portal — Alerts',
        '3r-urban-smart/portal/reports.php'   => 'Portal — Reports',
        '3r-urban-smart/portal/account.php'   => 'Portal — My Account',
        // Admin pages
        '3r-urban-smart/admin/machines.php'       => 'Admin — Machines',
        '3r-urban-smart/admin/machine-add.php'    => 'Admin — Add Machine',
        '3r-urban-smart/admin/machine-assign.php' => 'Admin — Assign Machine',
        '3r-urban-smart/admin/customers.php'      => 'Admin — Customers',
        '3r-urban-smart/admin/thresholds.php'     => 'Admin — Alert Thresholds',
    ];

    public function __construct() {
        add_filter( 'theme_page_templates', [ $this, 'register_templates' ] );
        add_filter( 'template_include',     [ $this, 'load_template'      ] );
    }

    /** Add our templates to the WP template dropdown */
    public function register_templates( $templates ) {
        return array_merge( $templates, $this->templates );
    }

    /**
     * Intercept template loading and serve from plugin directory.
     * Template key format: '3r-urban-smart/portal/dashboard.php'
     * Maps to:             TRU_PLUGIN_DIR . 'templates/portal/dashboard.php'
     *                  or  TRU_PLUGIN_DIR . 'templates/public/home.php'
     */
    public function load_template( $template ) {
        if ( ! is_page() ) return $template;

        $page_template = get_post_meta( get_the_ID(), '_wp_page_template', true );

        if ( ! array_key_exists( $page_template, $this->templates ) ) {
            return $template;
        }

        // Strip the 'plugin-slug/' prefix to get the relative path
        // e.g. '3r-urban-smart/portal/dashboard.php' → 'portal/dashboard.php'
        //      '3r-urban-smart/home.php'              → 'home.php' → 'public/home.php'
        $relative = str_replace( '3r-urban-smart/', '', $page_template );

        // Public templates live in templates/public/, portal in templates/portal/
        if ( strpos( $relative, 'portal/' ) === 0 ) {
            $plugin_template = TRU_PLUGIN_DIR . 'templates/' . $relative;
        } elseif ( strpos( $relative, 'admin/' ) === 0 ) {
            $plugin_template = TRU_PLUGIN_DIR . 'templates/' . $relative;
        } else {
            $plugin_template = TRU_PLUGIN_DIR . 'templates/public/' . $relative;
        }

        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }

        return $template;
    }
}
