<?php
/**
 * Registers WordPress meta boxes for all 3R Urban page content.
 * Editors can update every piece of visible text from WP Admin
 * without touching any PHP or CSS files.
 */
defined( 'ABSPATH' ) || exit;

class ThreeR_Urban_Meta_Fields {

    /**
     * Field definitions per page template.
     * Format: 'meta_key' => [ 'label', 'type', 'default' ]
     * Types: text | textarea | repeater_3 | repeater_4
     */
    private static $pages = [

        // ── HOME PAGE ─────────────────────────────────────────
        '3r-urban-smart/home.php' => [
            'label'  => '🏠 Home Page Content',
            'groups' => [
                'Hero Section' => [
                    'tru_hero_eyebrow'    => [ 'Eyebrow Tag',        'text',     'Smart Composting Intelligence' ],
                    'tru_hero_line1'      => [ 'Title — Line 1',     'text',     'Monitor Every' ],
                    'tru_hero_line2'      => [ 'Title — Line 2 (green)', 'text', 'Compost Machine' ],
                    'tru_hero_line3'      => [ 'Title — Line 3 (earth)', 'text', 'In Real-Time' ],
                    'tru_hero_sub'        => [ 'Subtitle paragraph', 'textarea', '3R Urban Smart Solutions connects your compost fleet to a unified intelligence platform. Live PLC data, predictive alerts, and actionable reports — all in one dashboard.' ],
                    'tru_hero_btn1'       => [ 'Button 1 Text',      'text',     'Request Demo' ],
                    'tru_hero_btn2'       => [ 'Button 2 Text',      'text',     'Learn More →' ],
                    'tru_stat1_val'       => [ 'Stat 1 — Value',     'text',     '98.4%' ],
                    'tru_stat1_lbl'       => [ 'Stat 1 — Label',     'text',     'Uptime SLA' ],
                    'tru_stat2_val'       => [ 'Stat 2 — Value',     'text',     '15min' ],
                    'tru_stat2_lbl'       => [ 'Stat 2 — Label',     'text',     'Data Refresh' ],
                    'tru_stat3_val'       => [ 'Stat 3 — Value',     'text',     '3 Cities' ],
                    'tru_stat3_lbl'       => [ 'Stat 3 — Label',     'text',     'Deployed' ],
                ],
                'Features Section' => [
                    'tru_feat_heading'    => [ 'Section Title',       'text',     'Everything your fleet needs to run smarter' ],
                    'tru_feat_sub'        => [ 'Section Subtitle',    'textarea', 'Built specifically for urban composting operations. From raw PLC signals to boardroom-ready reports.' ],
                    'tru_feat1_title'     => [ 'Feature 1 — Title',  'text',     'Live Telemetry' ],
                    'tru_feat1_desc'      => [ 'Feature 1 — Desc',   'textarea', 'PLC data streams via Teltonika RUT200 gateways over MQTT. Every machine reports temperature, load, and motor status every 15–30 minutes.' ],
                    'tru_feat2_title'     => [ 'Feature 2 — Title',  'text',     'Smart Alerts' ],
                    'tru_feat2_desc'      => [ 'Feature 2 — Desc',   'textarea', 'Threshold-based alerting engine fires instantly when values go out of range. Get notified by email before a small issue becomes a breakdown.' ],
                    'tru_feat3_title'     => [ 'Feature 3 — Title',  'text',     'Visual Dashboard' ],
                    'tru_feat3_desc'      => [ 'Feature 3 — Desc',   'textarea', 'Each customer sees their own fleet in a clean, role-restricted portal. Historical charts, health scores, and trend lines — no spreadsheets needed.' ],
                    'tru_feat4_title'     => [ 'Feature 4 — Title',  'text',     'Role-Based Access' ],
                    'tru_feat4_desc'      => [ 'Feature 4 — Desc',   'textarea', 'Customers only see their machines. Admins manage the entire fleet. Fine-grained permission control built into every page and API call.' ],
                    'tru_feat5_title'     => [ 'Feature 5 — Title',  'text',     'Automated Reports' ],
                    'tru_feat5_desc'      => [ 'Feature 5 — Desc',   'textarea', 'Daily, weekly, and monthly reports generated automatically. Download PDFs or CSV exports for compliance, maintenance, or management reviews.' ],
                    'tru_feat6_title'     => [ 'Feature 6 — Title',  'text',     'Admin Control Panel' ],
                    'tru_feat6_desc'      => [ 'Feature 6 — Desc',   'textarea', 'Onboard new machines, assign them to customers, manage thresholds, and oversee your entire deployment from one secure admin interface.' ],
                ],
                'How It Works Section' => [
                    'tru_hiw_heading'     => [ 'Section Title',       'text',     'From machine to insight in four steps' ],
                    'tru_step1_title'     => [ 'Step 1 — Title',      'text',     'PLC Reads Sensors' ],
                    'tru_step1_desc'      => [ 'Step 1 — Desc',       'textarea', 'Your compost machine\'s PLC reads temperature, weight, and motor data at configured intervals via Modbus RTU.' ],
                    'tru_step2_title'     => [ 'Step 2 — Title',      'text',     'RUT200 Transmits' ],
                    'tru_step2_desc'      => [ 'Step 2 — Desc',       'textarea', 'The Teltonika RUT200 gateway packages the data as MQTT and securely transmits it to our cloud broker over TLS.' ],
                    'tru_step3_title'     => [ 'Step 3 — Title',      'text',     'Engine Processes' ],
                    'tru_step3_desc'      => [ 'Step 3 — Desc',       'textarea', 'Our Node.js middleware validates, stores, and runs the alert engine — flagging anything outside defined thresholds instantly.' ],
                    'tru_step4_title'     => [ 'Step 4 — Title',      'text',     'You See & Act' ],
                    'tru_step4_desc'      => [ 'Step 4 — Desc',       'textarea', 'Your portal dashboard updates with fresh data. Alerts surface immediately. Reports generate on schedule. You stay in control.' ],
                ],
                'CTA Section' => [
                    'tru_cta_title'       => [ 'CTA Headline',        'text',     'Ready to digitise your compost fleet?' ],
                    'tru_cta_sub'         => [ 'CTA Subtitle',        'textarea', 'Book a 30-minute live demo. We\'ll show you exactly how your machines would look on the platform — using your real sensor data.' ],
                    'tru_cta_btn1'        => [ 'CTA Button 1',        'text',     'Book a Demo →' ],
                    'tru_cta_btn2'        => [ 'CTA Button 2',        'text',     'Talk to Us' ],
                ],
            ],
        ],

        // ── ABOUT PAGE ────────────────────────────────────────
        '3r-urban-smart/about.php' => [
            'label'  => '📖 About Page Content',
            'groups' => [
                'Hero Section' => [
                    'tru_ab_eyebrow'      => [ 'Eyebrow Tag',         'text',     'Our Story' ],
                    'tru_ab_title'        => [ 'Main Headline',       'text',     'We built the platform we wished existed' ],
                    'tru_ab_mission'      => [ 'Mission Statement',   'textarea', 'Urban waste management is critical infrastructure — yet most compost operations still rely on manual logs and gut instinct. 3R Urban Smart Solutions was founded to change that. We bring industrial IoT intelligence to the front line of sustainable cities.' ],
                    'tru_ab_val1_title'   => [ 'Value 1 — Title',     'text',     'Transparency First' ],
                    'tru_ab_val1_desc'    => [ 'Value 1 — Desc',      'textarea', 'Every data point is timestamped, stored, and auditable. We believe operators deserve full visibility into their own infrastructure.' ],
                    'tru_ab_val2_title'   => [ 'Value 2 — Title',     'text',     'Open by Default' ],
                    'tru_ab_val2_desc'    => [ 'Value 2 — Desc',      'textarea', 'Built on open protocols — MQTT, REST, Modbus. No proprietary lock-in. Your data is yours, always exportable.' ],
                    'tru_ab_val3_title'   => [ 'Value 3 — Title',     'text',     'Urban Scale Thinking' ],
                    'tru_ab_val3_desc'    => [ 'Value 3 — Desc',      'textarea', 'Designed to handle fleets of machines across multiple wards, zones, and municipalities from a single platform instance.' ],
                ],
                'Stats Bar' => [
                    'tru_ab_stat1_val'    => [ 'Stat 1 — Value',      'text',     '3+' ],
                    'tru_ab_stat1_lbl'    => [ 'Stat 1 — Label',      'text',     'Cities Deployed' ],
                    'tru_ab_stat2_val'    => [ 'Stat 2 — Value',      'text',     '24/7' ],
                    'tru_ab_stat2_lbl'    => [ 'Stat 2 — Label',      'text',     'Monitoring' ],
                    'tru_ab_stat3_val'    => [ 'Stat 3 — Value',      'text',     '15min' ],
                    'tru_ab_stat3_lbl'    => [ 'Stat 3 — Label',      'text',     'Data Refresh' ],
                    'tru_ab_stat4_val'    => [ 'Stat 4 — Value',      'text',     '98.4%' ],
                    'tru_ab_stat4_lbl'    => [ 'Stat 4 — Label',      'text',     'Platform Uptime' ],
                ],
                'Team' => [
                    'tru_tm1_name'        => [ 'Member 1 — Name',     'text',     'Arjun Mehta' ],
                    'tru_tm1_role'        => [ 'Member 1 — Role',     'text',     '// Founder & CEO' ],
                    'tru_tm1_bio'         => [ 'Member 1 — Bio',      'textarea', '15 years in urban infrastructure and waste management. Previously led smart city initiatives across 4 municipal corporations.' ],
                    'tru_tm2_name'        => [ 'Member 2 — Name',     'text',     'Priya Sharma' ],
                    'tru_tm2_role'        => [ 'Member 2 — Role',     'text',     '// CTO & Platform Lead' ],
                    'tru_tm2_bio'         => [ 'Member 2 — Bio',      'textarea', 'IoT systems architect with deep experience in MQTT, industrial PLCs, and real-time data pipelines at scale.' ],
                    'tru_tm3_name'        => [ 'Member 3 — Name',     'text',     'Ravi Nair' ],
                    'tru_tm3_role'        => [ 'Member 3 — Role',     'text',     '// Head of Field Operations' ],
                    'tru_tm3_bio'         => [ 'Member 3 — Bio',      'textarea', 'Certified Teltonika integrator. Oversees all hardware deployments, RUT200 configurations, and on-site commissioning.' ],
                ],
                'Tech Section' => [
                    'tru_tech_title'      => [ 'Section Title',       'text',     'Built on proven, open-source foundations' ],
                    'tru_tech_sub'        => [ 'Section Subtitle',    'textarea', 'We chose battle-tested technologies that municipalities can audit, inspect, and trust.' ],
                ],
            ],
        ],

        // ── DEMO PAGE ─────────────────────────────────────────
        '3r-urban-smart/demo.php' => [
            'label'  => '📅 Request Demo Page Content',
            'groups' => [
                'Page Header' => [
                    'tru_demo_eyebrow'    => [ 'Eyebrow Tag',         'text',     'Get Started' ],
                    'tru_demo_title'      => [ 'Page Title',          'text',     'Request a Live Demo' ],
                    'tru_demo_form_head'  => [ 'Form Heading',        'text',     'Book your demo' ],
                    'tru_demo_form_sub'   => [ 'Form Subtitle',       'textarea', 'Fill in your details and our team will reach out within 24 hours to schedule a personalised walkthrough.' ],
                ],
                'Info Sidebar' => [
                    'tru_demo_info_title' => [ 'Sidebar Heading',     'text',     'What\'s included in the demo' ],
                    'tru_demo_duration'   => [ 'Duration (mins)',      'text',     '30' ],
                    'tru_demo_cost'       => [ 'Cost label',          'text',     'Free' ],
                    'tru_demo_response'   => [ 'Response time',       'text',     '24h' ],
                    'tru_demo_trust_title'=> [ 'Trust Box Title',     'text',     'Why teams trust 3R Urban' ],
                ],
            ],
        ],

        // ── CONTACT PAGE ──────────────────────────────────────
        '3r-urban-smart/contact.php' => [
            'label'  => '📞 Contact Page Content',
            'groups' => [
                'Contact Info' => [
                    'tru_con_eyebrow'     => [ 'Eyebrow Tag',         'text',     'Get In Touch' ],
                    'tru_con_title'       => [ 'Headline',            'text',     'Let\'s talk about your fleet' ],
                    'tru_con_sub'         => [ 'Intro paragraph',     'textarea', 'Whether you\'re exploring the platform, need technical support, or want to discuss a custom deployment — we\'re here.' ],
                    'tru_con_email'       => [ 'Email address',       'text',     'info@3rurban.com' ],
                    'tru_con_phone'       => [ 'Phone number',        'text',     '+91 98000 12345' ],
                    'tru_con_hours'       => [ 'Support hours',       'text',     'Mon–Fri, 9am – 6pm IST' ],
                    'tru_con_address'     => [ 'Office address',      'textarea', "3R Urban Smart Solutions Pvt Ltd\nBaner Road, Pune 411 045, Maharashtra" ],
                    'tru_con_map_label'   => [ 'Map pin label',       'text',     '📍 3R Urban HQ, Pune' ],
                ],
                'Contact Form' => [
                    'tru_con_form_head'   => [ 'Form Heading',        'text',     'Send us a message' ],
                    'tru_con_form_sub'    => [ 'Form Subtitle',       'text',     'We typically respond within one business day.' ],
                ],
            ],
        ],
    ];

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
        add_action( 'save_post_page', [ $this, 'save_meta'           ] );
        add_action( 'admin_head',     [ $this, 'admin_styles'        ] );
    }

    // ── Register one meta box per template ───────────────────────
    public function register_meta_boxes() {
        foreach ( self::$pages as $template => $config ) {
            add_meta_box(
                '3r-urban-content-' . sanitize_key( $template ),
                $config['label'],
                [ $this, 'render_meta_box' ],
                'page',
                'normal',
                'high',
                [ 'template' => $template, 'groups' => $config['groups'] ]
            );
        }
    }

    // ── Render the meta box UI ───────────────────────────────────
    public function render_meta_box( $post, $metabox ) {
        $template = get_post_meta( $post->ID, '_wp_page_template', true );
        $expected = $metabox['args']['template'];

        // Only show this meta box on its matching template
        if ( $template !== $expected ) {
            echo '<p style="color:#888;font-style:italic">This panel is hidden — assign the <strong>' . esc_html( $expected ) . '</strong> template to this page to see its fields.</p>';
            return;
        }

        wp_nonce_field( '3r_urban_save_meta_' . $post->ID, '3r_urban_meta_nonce' );
        $groups = $metabox['args']['groups'];

        foreach ( $groups as $group_label => $fields ) {
            echo '<div class="tru-meta-group">';
            echo '<h3 class="tru-meta-group-title">' . esc_html( $group_label ) . '</h3>';
            echo '<div class="tru-meta-fields">';

            foreach ( $fields as $key => [ $label, $type, $default ] ) {
                $value = get_post_meta( $post->ID, $key, true );
                if ( $value === '' ) $value = $default;
                $this->render_field( $key, $label, $type, $value );
            }

            echo '</div></div>';
        }
    }

    // ── Render a single field ────────────────────────────────────
    private function render_field( $key, $label, $type, $value ) {
        echo '<div class="tru-meta-field">';
        echo '<label for="' . esc_attr( $key ) . '" class="tru-meta-label">' . esc_html( $label ) . '</label>';

        if ( $type === 'textarea' ) {
            echo '<textarea id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" class="tru-meta-textarea" rows="3">' . esc_textarea( $value ) . '</textarea>';
        } else {
            echo '<input type="text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="tru-meta-input"/>';
        }

        echo '</div>';
    }

    // ── Save meta on post save ───────────────────────────────────
    public function save_meta( $post_id ) {
        if ( ! isset( $_POST['3r_urban_meta_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['3r_urban_meta_nonce'], '3r_urban_save_meta_' . $post_id ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_page', $post_id ) ) return;

        foreach ( self::$pages as $template => $config ) {
            foreach ( $config['groups'] as $fields ) {
                foreach ( array_keys( $fields ) as $key ) {
                    if ( isset( $_POST[ $key ] ) ) {
                        update_post_meta(
                            $post_id,
                            $key,
                            wp_kses_post( $_POST[ $key ] )
                        );
                    }
                }
            }
        }
    }

    // ── Admin styles ─────────────────────────────────────────────
    public function admin_styles() {
        global $post;
        if ( ! $post || $post->post_type !== 'page' ) return;
        ?>
        <style>
        #poststuff .postbox[id^="3r-urban-content-"] .inside { padding: 0; }
        .tru-meta-group { border-top: 1px solid #e5e7eb; }
        .tru-meta-group:first-child { border-top: none; }
        .tru-meta-group-title {
            font-size: 11px; font-weight: 600; text-transform: uppercase;
            letter-spacing: .06em; color: #6b7280;
            background: #f9fafb; padding: 8px 16px; margin: 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .tru-meta-fields {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 0; padding: 0;
        }
        .tru-meta-field {
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            border-right: 1px solid #f3f4f6;
        }
        .tru-meta-field:nth-child(even) { border-right: none; }
        .tru-meta-label {
            display: block; font-size: 11px; font-weight: 600;
            color: #374151; margin-bottom: 5px; letter-spacing: .02em;
        }
        .tru-meta-input {
            width: 100%; padding: 6px 9px; font-size: 13px;
            border: 1px solid #d1d5db; border-radius: 4px;
            background: #fff; color: #111;
            transition: border-color .15s;
        }
        .tru-meta-input:focus { border-color: #5dd862; outline: none; box-shadow: 0 0 0 2px rgba(93,216,98,.15); }
        .tru-meta-textarea {
            width: 100%; padding: 6px 9px; font-size: 13px;
            border: 1px solid #d1d5db; border-radius: 4px;
            background: #fff; color: #111; resize: vertical;
            transition: border-color .15s; font-family: inherit;
        }
        .tru-meta-textarea:focus { border-color: #5dd862; outline: none; box-shadow: 0 0 0 2px rgba(93,216,98,.15); }
        /* Make textarea fields span full width */
        .tru-meta-field:has(.tru-meta-textarea) {
            grid-column: 1 / -1; border-right: none;
        }
        </style>
        <?php
    }

    // ── Static helper: get field value with fallback to default ──
    public static function get( $post_id, $key ) {
        $all_fields = [];
        foreach ( self::$pages as $config ) {
            foreach ( $config['groups'] as $fields ) {
                foreach ( $fields as $k => [ $label, $type, $default ] ) {
                    $all_fields[ $k ] = $default;
                }
            }
        }
        $value = get_post_meta( $post_id, $key, true );
        return ( $value !== '' && $value !== false ) ? $value : ( $all_fields[ $key ] ?? '' );
    }
}
