=== 3R Urban Smart Solutions — Public Website ===
Contributors: 3rurban
Tags: composting, iot, smart city, municipal, monitoring
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 2.2.0
License: Proprietary

Installs the complete 3R Urban Smart Solutions public website in one click.

== Description ==

This plugin creates and manages the full public-facing website for 3R Urban Smart Solutions — a Compost Monitoring SaaS Platform.

On activation it automatically:
* Creates 4 pages: Home, About, Request Demo, Contact
* Sets the Home page as the WordPress front page
* Creates and assigns a navigation menu
* Enqueues all styles and scripts (scoped, no theme conflicts)

All pages use custom plugin-side templates — no theme modification required.

== Installation ==

1. Download the plugin zip file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the zip and click Install Now
4. Click Activate
5. Done — visit your site to see the full website live

IMPORTANT: After activation go to Appearance → Menus and confirm
"3R Urban Main Menu" is assigned to your Primary Navigation location.

== Frequently Asked Questions ==

= Will this conflict with my existing theme? =
No. All CSS is scoped under .tru-urban-body and the tru- prefix.
Templates override only pages with the 3R Urban template assigned.

= Can I edit the page content? =
The design and layout are controlled by the plugin templates in
/templates/public/. Edit those PHP files to customise content.

= What happens if I deactivate? =
The pages remain but will fall back to your theme's default template.
Reactivating restores the custom templates.

= Does this work with any theme? =
Yes. The plugin ships its own full-page templates with header and
footer — your theme's header/footer are not used on 3R pages.

== File Structure ==

3r-urban-smart/
├── 3r-urban-smart.php          Main plugin file
├── includes/
│   ├── class-activator.php     Creates pages, menu, reading settings
│   ├── class-page-templates.php Registers & loads custom templates
│   ├── class-assets.php        Enqueues CSS & JS
│   ├── class-ajax.php          Handles form submissions
│   └── class-admin-notice.php  Shows activation success notice
├── templates/
│   ├── partials/
│   │   ├── header.php          Shared nav header
│   │   └── footer.php          Shared footer
│   └── public/
│       ├── home.php            Homepage template
│       ├── about.php           About page template
│       ├── demo.php            Request Demo template
│       └── contact.php         Contact page template
└── assets/
    ├── css/3r-urban.css        All styles (scoped, no conflicts)
    └── js/3r-urban.js          Interactions, chart, marquee, forms

== Changelog ==

= 1.0.0 =
* Initial release
* Homepage with hero, features, how-it-works, machine preview, CTA
* About page with values, stats, team, tech stack
* Request Demo page with AJAX form and email notification
* Contact page with AJAX form and office map
* Scroll animations, animated chart, marquee, mobile nav
