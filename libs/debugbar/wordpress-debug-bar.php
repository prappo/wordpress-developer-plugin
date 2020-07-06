<?php

require_once 'vendor/autoload.php';
require_once 'collectors/WPActionsCollector.php';
require_once 'collectors/WPFiltersCollector.php';
require_once 'collectors/WPDBCollector.php';

use DebugBar\StandardDebugBar;

class Wordpress_Debug_Bar {

    protected static $instance;
    protected static $debugbar;

    public function __construct() {
        self::$debugbar = new StandardDebugBar();
        self::$debugbar->addCollector(new WPActionsCollector());
        self::$debugbar->addCollector(new WPFiltersCollector());

        if ( defined('SAVEQUERIES') && SAVEQUERIES ) {
            self::$debugbar->addCollector(new WPDBCollector());
        }

        if ( defined('DOING_AJAX') && DOING_AJAX ) {
            add_action( 'admin_init', array( &$this, 'init_ajax' ) );
        }

        $arr = [
            'name' => 'prappo',
            'email' => 'prappo@gmail.com',
        ];



        add_action( 'init', array( &$this, 'init' ) );
    }

    public static function get_instance() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init() {
        if ( ! is_super_admin() || $this->is_wp_login() ) {
            return;
        }

        add_action( 'wp_footer', array( &$this, 'render' ), 1000 );
        add_action( 'wp_head', array( &$this, 'header' ), 1 );
    }

    public function init_ajax() {
        if ( ! is_super_admin() )
            return;

        self::$debugbar->sendDataInHeaders();
    }

    public function render() {
        $debugbarRenderer = self::$debugbar->getJavascriptRenderer();
        echo $debugbarRenderer->render();
    }

    public function header() {
        $path = plugin_dir_path( __FILE__ );
        $url = plugins_url( '',  __FILE__ );

        $debugbarRenderer = self::$debugbar->getJavascriptRenderer(
            $url . '/vendor/maximebf/debugbar/src/DebugBar/Resources/',
            $path . '/vendor/maximebf/debugbar/src/DebugBar/Resources/'
        );

        echo $debugbarRenderer->renderHead();
    }

    protected function is_wp_login() {
        return 'wp-login.php' == basename( $_SERVER['SCRIPT_NAME'] );
    }

    public function __call($name, $args) {
        if ($name == 'startMeasure') {
            self::$debugbar['time']->startMeasure($args[0], $args[1]);
        }
        elseif ($name == 'stopMeasure') {
            self::$debugbar['time']->stopMeasure($args[0]);
        }
        elseif ($name == 'addException') {
            self::$debugbar['exceptions']->addException($args[0]);
        }
        elseif ($name == 'info' || $name == 'debug') {
            self::$debugbar['messages']->info($args[0]);
        }
    }
}

$GLOBALS['wp_debug_bar'] = Wordpress_Debug_Bar::get_instance();


