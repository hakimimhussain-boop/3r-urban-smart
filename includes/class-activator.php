<?php
defined( 'ABSPATH' ) || exit;

class ThreeR_Urban_Activator {

    /**
     * Pages to create on activation.
     * 'slug' => [ title, template_file, set_as_front_page ]
     */
    private static $pages = [
        '3r-login' => [ 'Sign In', 'login.php', false ],
        // Portal pages (login-protected)
        '3r-portal-dashboard' => [ 'Portal — Dashboard', 'portal/dashboard.php', false ],
        '3r-portal-machine'   => [ 'Portal — Machine',   'portal/machine.php',   false ],
        '3r-portal-panel'     => [ 'Portal — Live Panel', 'portal/panel.php',     false ],
        '3r-portal-alerts'    => [ 'Portal — Alerts',    'portal/alerts.php',    false ],
        '3r-portal-reports'   => [ 'Portal — Reports',   'portal/reports.php',   false ],
        '3r-portal-account'   => [ 'Portal — Account',   'portal/account.php',   false ],
        // Admin pages
        '3r-admin-machines'   => [ 'Admin — Machines',    'admin/machines.php',    false ],
        '3r-admin-add'        => [ 'Admin — Add Machine', 'admin/machine-add.php', false ],
        '3r-admin-assign'     => [ 'Admin — Assign',      'admin/machine-assign.php', false ],
        '3r-admin-customers'  => [ 'Admin — Customers',   'admin/customers.php',   false ],
        '3r-admin-thresholds' => [ 'Admin — Thresholds',  'admin/thresholds.php',  false ],
        // Public pages
        '3r-home'    => [ 'Home',          'home.php',    true  ],
        '3r-about'   => [ 'About',         'about.php',   false ],
        '3r-demo'    => [ 'Request Demo',  'demo.php',    false ],
        '3r-contact' => [ 'Contact',       'contact.php', false ],
    ];

    public static function activate() {
        self::create_pages();
        self::create_nav_menu();
        self::set_reading_settings();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    // ── Create pages ──────────────────────────────────────────────────────────
    public static function create_pages() {
        $created_ids = [];

        foreach ( self::$pages as $slug => [ $title, $template, $is_front ] ) {

            // Skip if page already exists
            $existing = get_page_by_path( $slug );
            if ( $existing ) {
                $created_ids[ $slug ] = $existing->ID;
                continue;
            }

            $page_id = wp_insert_post( [
                'post_title'   => $title,
                'post_name'    => $slug,
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => '',
                'meta_input'   => [
                    '_wp_page_template' => '3r-urban-smart/' . $template,
                ],
            ] );

            if ( ! is_wp_error( $page_id ) ) {
                $created_ids[ $slug ] = $page_id;
            }
        }

        update_option( 'tru_page_ids', $created_ids );

        // Set front page
        if ( isset( $created_ids['3r-home'] ) ) {
            update_option( 'tru_front_page_id', $created_ids['3r-home'] );
        }
    }

    // ── Create navigation menu ────────────────────────────────────────────────
    private static function create_nav_menu() {
        $menu_name = '3R Urban Main Menu';

        if ( wp_get_nav_menu_object( $menu_name ) ) {
            return;
        }

        $menu_id = wp_create_nav_menu( $menu_name );
        if ( is_wp_error( $menu_id ) ) return;

        $page_ids = get_option( 'tru_page_ids', [] );
        $order    = 1;

        $menu_items = [
            '3r-home'    => 'Home',
            '3r-about'   => 'About',
            '3r-contact' => 'Contact',
        ];

        foreach ( $menu_items as $slug => $label ) {
            if ( ! isset( $page_ids[ $slug ] ) ) continue;
            wp_update_nav_menu_item( $menu_id, 0, [
                'menu-item-title'     => $label,
                'menu-item-object'    => 'page',
                'menu-item-object-id' => $page_ids[ $slug ],
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
                'menu-item-position'  => $order++,
            ] );
        }

        // Add Demo as CTA (custom link stored separately)
        if ( isset( $page_ids['3r-demo'] ) ) {
            wp_update_nav_menu_item( $menu_id, 0, [
                'menu-item-title'     => 'Request Demo',
                'menu-item-object'    => 'page',
                'menu-item-object-id' => $page_ids['3r-demo'],
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
                'menu-item-position'  => $order,
                'menu-item-classes'   => 'menu-item-cta',
            ] );
        }

        // Assign to all registered nav locations
        $locations = get_theme_mod( 'nav_menu_locations', [] );
        foreach ( array_keys( get_registered_nav_menus() ) as $location ) {
            $locations[ $location ] = $menu_id;
        }
        set_theme_mod( 'nav_menu_locations', $locations );

        update_option( 'tru_menu_id', $menu_id );
    }

    // ── Reading settings ──────────────────────────────────────────────────────
    private static function set_reading_settings() {
        $front_page_id = get_option( 'tru_front_page_id' );
        if ( ! $front_page_id ) return;

        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', $front_page_id );
    }
}
