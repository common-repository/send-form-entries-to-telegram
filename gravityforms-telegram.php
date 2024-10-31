<?php
/**
 * Plugin Name: Send Form Entries To Telegram
 * Plugin URI:  #
 * Description: Send GravityForms Entries To Telegram Chats
 * Version:     1.1.0
 * Author:      Salar Sadeghi
 * Author URI:  https://salars.xyz
 * Text Domain: gft
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
require_once "include/EasyWPAjax.php";
define("GFTOTELEGRAM_DIR",__DIR__);
define("GFTOTELEGRAM_FILE",__FILE__);
class GFToTelegram{

    function __construct()
    {
        add_filter( 'plugin_row_meta', [$this,'plugin_row_meta'], 10, 2 );
        load_plugin_textdomain( 'gft', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
        require_once "include/vendor/autoload.php";
        require_once "include/GFT_Telegram.php";
        require_once "include/GFT_Menu.php";
        new GFT_Telegram();
        new GFT_Menu;
    }

    function plugin_row_meta( $links, $file ) {
        if ( plugin_basename( __FILE__ ) == $file ) {
            $row_meta = array(
                'settings'    => '<a href="' . admin_url( 'tools.php?page=gf-to-telegram' ) . '" aria-label="' . esc_attr__( 'Plugin Settings', 'gft' ) . '" style="color:green;">' . esc_html__( 'Settings', 'gft' ) . '</a>'
            );

            return array_merge( $links, $row_meta );
        }
        return (array) $links;
    }


}

if (version_compare(PHP_VERSION, '7.1.0', '<')) {
    add_action( 'admin_notices', function(){
        $class = 'notice notice-error';
        $message = __('GravityForms Entries To Telegram Chats','gft').' <hr>';
        $message .= __('The php version of your site is less than 7.1.0, please upgrade it. You can contact your hosting manager to upgrade.','gft');

        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message);
    } );
    return;
}

if (!extension_loaded('mbstring')) {
    add_action( 'admin_notices', function(){
        $class = 'notice notice-error';
        $message = __('GravityForms Entries To Telegram Chats','gft').' <hr>';
        $message .= __('The mbstring module is not enabled on your site\'s server. Contact your hosting to activate it.','gft');

        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message);
    } );
    return;
}

add_action('plugins_loaded',function (){
    if (!class_exists( 'GFCommon' )) {
        add_action( 'admin_notices', function(){
            $class = 'notice notice-error';
            $message = __('GravityForms Entries To Telegram Chats','gft').' <hr>';
            $message .= __('The GravityForms plugin is not installed / activated on the site. First install / activate the GravityForms plugin','gft');

            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message);
        } );
        return;
    }
   new GFToTelegram();
});
