<?php
defined( 'ABSPATH' ) || exit;

class ThreeR_Urban_Assets {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue'       ]      );
        add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_theme' ], 100 );
    }

    public function enqueue() {
        if ( $this->is_login_page() ) {
            // Login page loads its own styles inline — just dequeue theme
        } elseif ( $this->is_admin_page() ) {
            $this->enqueue_admin();
        } elseif ( $this->is_portal_page() ) {
            $this->enqueue_portal();
        } elseif ( $this->is_public_page() ) {
            $this->enqueue_public();
        }
    }

    // ── Public site assets ───────────────────────────────────────────────────
    private function enqueue_public() {
        // Fonts loaded via header.php <link> tag directly
        wp_enqueue_style( '3r-urban-main', TRU_PLUGIN_URL . 'assets/css/3r-urban.css', [], TRU_VERSION );
        wp_enqueue_script( '3r-urban-main', TRU_PLUGIN_URL . 'assets/js/3r-urban.js', [], TRU_VERSION, true );
        wp_localize_script( '3r-urban-main', 'tru3rUrban', [
            'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'tru_nonce' ),
            'homeUrl'    => get_permalink( get_page_by_path( '3r-home'    ) ),
            'aboutUrl'   => get_permalink( get_page_by_path( '3r-about'   ) ),
            'demoUrl'    => get_permalink( get_page_by_path( '3r-demo'    ) ),
            'contactUrl' => get_permalink( get_page_by_path( '3r-contact' ) ),
        ] );
    }

    // ── Admin panel assets ───────────────────────────────────────────────────
    private function enqueue_admin() {
        wp_enqueue_style( '3r-admin-main', TRU_PLUGIN_URL . 'assets/css/3r-admin.css', [], TRU_VERSION );
        wp_add_inline_script( 'wp-api', '' ); // ensure REST nonce
        wp_enqueue_script( '3r-admin-main', TRU_PLUGIN_URL . 'assets/js/3r-admin.js', [], TRU_VERSION, true );
        wp_localize_script( '3r-admin-main', 'truAdmin', [
            'restBase' => esc_url_raw( rest_url() ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
        ] );
    }
    private function enqueue_portal() {
        // Chart.js from CDN (only on portal)
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js',
            [], null, true
        );

        wp_enqueue_style(  '3r-urban-main',   TRU_PLUGIN_URL . 'assets/css/3r-urban.css',  [], TRU_VERSION );
        wp_enqueue_style(  '3r-portal-main',  TRU_PLUGIN_URL . 'assets/css/3r-portal.css', ['3r-urban-main'], TRU_VERSION );
        wp_enqueue_script( '3r-portal-main',  TRU_PLUGIN_URL . 'assets/js/3r-portal.js',   ['chartjs'], TRU_VERSION, true );

        wp_localize_script( '3r-portal-main', 'truPortal', [
            'restBase' => esc_url_raw( rest_url() ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
        ] );
    }

    // ── Dequeue Twenty Twenty-Five styles on our pages ───────────────────────
    public function dequeue_theme() {
        if ( ! $this->is_public_page() && ! $this->is_portal_page() && ! $this->is_admin_page() ) return;
        $handles = [
            'global-styles', 'twenty-twenty-five-style', 'twenty-twenty-five-print-style',
            'wp-block-library', 'wp-block-library-theme', 'classic-theme-styles',
            'core-block-supports', 'elementor-frontend', 'elementor-icons',
        ];
        foreach ( $handles as $h ) { wp_dequeue_style($h); wp_deregister_style($h); }
    }

    // ── Page detection ───────────────────────────────────────────────────────
    private function get_template() {
        if ( ! is_page() ) return '';
        return get_post_meta( get_the_ID(), '_wp_page_template', true );
    }

    private function is_login_page() {
        $t = $this->get_template();
        return $t === '3r-urban-smart/login.php';
    }

    private function is_public_page() {
        $t = $this->get_template();
        return $t && strpos( $t, '3r-urban-smart/' ) === 0 && strpos( $t, 'portal/' ) === false;
    }

    private function is_portal_page() {
        $t = $this->get_template();
        return $t && strpos( $t, '3r-urban-smart/portal/' ) === 0;
    }

    private function is_admin_page() {
        $t = $this->get_template();
        return $t && strpos( $t, '3r-urban-smart/admin/' ) === 0;
    }
}
