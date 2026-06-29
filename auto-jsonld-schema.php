<?php
/**
 * Plugin Name: Auto JSON-LD Schema
 * Plugin URI:  https://gitlab.com/bitshive-inc-group/auto-jsonld
 * Description: Automatically injects JSON-LD structured data schema markup for better SEO. Built for web development agencies.
 * Version:     2.0.0
 * Author:      BitsHive Inc
 * Author URI:  https://bitshive.com
 * License:     GPL-2.0+
 * Text Domain: auto-jsonld-schema
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'AUTO_JSONLD_VERSION', '2.0.0' );
define( 'AUTO_JSONLD_PATH', plugin_dir_path( __FILE__ ) );
define( 'AUTO_JSONLD_URL', plugin_dir_url( __FILE__ ) );

require_once AUTO_JSONLD_PATH . 'includes/class-settings.php';
require_once AUTO_JSONLD_PATH . 'includes/class-meta-box.php';
require_once AUTO_JSONLD_PATH . 'includes/class-content-parser.php';
require_once AUTO_JSONLD_PATH . 'includes/class-schema-types.php';
require_once AUTO_JSONLD_PATH . 'includes/class-schema-engine.php';
require_once AUTO_JSONLD_PATH . 'includes/class-opengraph.php';

class Auto_JSONLD_Schema_Plugin {

    public function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    public function init() {
        new Auto_JSONLD_Settings();
        new Auto_JSONLD_Meta_Box();
        new Auto_JSONLD_Schema_Engine();
        new Auto_JSONLD_OpenGraph();
    }
}

new Auto_JSONLD_Schema_Plugin();
