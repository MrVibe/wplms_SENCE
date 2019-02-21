<?php
/**
 * SENCE Integration
 *
 * @author      VibeThemes
 * @category    Admin
 * @package     SENCE
 * @version     3.9
 */

 if ( ! defined( 'ABSPATH' ) ) exit;

class WPLMS_SENCE{

    public static $instance;
    public static function init(){
    if ( is_null( self::$instance ) )
        self::$instance = new WPLMS_SENCE();

        return self::$instance;
    }

    private function __construct(){
    }
}

WPLMS_SENCE::init();