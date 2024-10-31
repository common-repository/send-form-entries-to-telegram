<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
class GFT_Menu {

    public function __construct() {
        add_action( 'admin_menu', array($this, 'register_sub_menu') );
        add_action('admin_enqueue_scripts', [$this,'admin_style']);
        add_filter('wp_easy_ajax', function ($calls) {
            $calls[] = [
                'action' => 'gft_update',
                'adminSide' => true,
                'userSide' => false,
                'callback' => function () {
                   $error = '';

                   $form = sanitize_text_field($_REQUEST['form']);
                   $form = (intval($form) == 0 ? $error = __('From ID is invalid','gft') :  intval($form));

                   $fields = $this->arr_sanitize_text_field($_REQUEST['field']);
                   $attrs = $this->arr_sanitize_text_field($_REQUEST['attrs']);

                   $fields = (gettype($fields) != 'array' ? [] : $fields);// it can be empty array
                   $attrs = (gettype($attrs) != 'array' ? [] : $attrs);// it can be empty array

                   $channel_id = sanitize_text_field($_REQUEST['channel_id']);
                   $channel_id = (empty($channel_id) ? $error = __('Channel ID is invalid','gft') : $channel_id);

                   $enabled = sanitize_text_field($_REQUEST['enabled']);
                   $enabled = (empty($enabled) ? '' : $enabled); // it can be null


                   if (!empty($error)){
                       echo esc_html($error);
                       exit();
                   }

                   update_option('gft_data_'.$form,[
                       'fields' => $fields,
                       'attrs' => $attrs,
                       'channel_id' => $channel_id,
                       'enabled' => $enabled
                   ]);
                   echo esc_html('1');
                   exit();
                },
                'js' => [
                    'send' => [
                        'data' => 'FormData',
                        'success' => function () {
                            return 'if(response == "1"){alert("'.__('Saved','gft').'");}else{alert(response)} trigger.find("button").text("'.__('Save Changes','gft').'"); ';
                        }
                    ],
                    "trigger" => [
                        "event" => 'submit',
                        "element" => '#gft_update',
                        'callback' => function(){
                            return ' trigger.find("button").text("'.__('Saving ...','gft').'") ';
                        }
                    ]
                ]
            ];
            return $calls;
        });

        add_filter('wp_easy_ajax', function ($calls) {
            $calls[] = [
                'action' => 'gft_token',
                'adminSide' => true,
                'userSide' => false,
                'callback' => function () {
                    $gft_data_token = sanitize_text_field($_REQUEST['gft_data_token']);
                    if(empty($gft_data_token) && strlen($gft_data_token) < 11){
                        esc_html_e('The token is invalid','gft');
                        exit();
                    }
                    $s_telegram = false;
                    try {
                        $telegram = new \Telegram\Bot\Api($gft_data_token);
                        if( is_object($telegram) ){
                            if(boolval($telegram->getMe()->isBot)){
                                $s_telegram = true;
                            }else{
                                $s_telegram = false;
                            }
                        }
                    }catch (Exception $e){
                        $s_telegram = false;
                    }
                    if($s_telegram){
                        update_option('gft_data_token',$gft_data_token);
                        echo esc_html('1');
                    }else{
                        delete_option('gft_data_token');
                        esc_html_e('The token is invalid','gft');
                    }
                    exit();
                },
                'js' => [
                    'send' => [
                        'data' => 'FormData',
                        'success' => function () {
                            return 'if(response == "1"){ alert("'.__('Done','gft').'"); }else{alert(response)}; location.reload(); trigger.find("button").text("'.__('Save Changes','gft').'"); ';
                        }
                    ],
                    "trigger" => [
                        "event" => 'submit',
                        "element" => '#gft_token',
                        'callback' => function(){
                            return ' trigger.find("button").text("'.__('Saving ...','gft').'") ';
                        }
                    ]
                ]
            ];
            return $calls;
        });

    }

    function arr_sanitize_text_field($array) {
        foreach ( $array as $key => &$value ) {
            if ( is_array( $value ) ) {
                $value = $this->arr_sanitize_text_field($value);
            }
            else {
                $value = sanitize_text_field( $value );
            }
        }

        return $array;
    }

    function admin_style(){
        wp_enqueue_style('gft-admin-styles', plugins_url("assets/css/style.css",GFTOTELEGRAM_FILE));
        wp_enqueue_script('gft-admin-scripts', plugins_url("assets/js/script.js",GFTOTELEGRAM_FILE),['jquery'],1,true);
    }

    function checked_html($form,$id){
        $values = get_option('gft_data_'.$form);
        if(empty($values)){return;}
        foreach ($values['fields'] as $key => $value){
            if( $key == $id ){
                echo ' checked="checked" ';
            }
        }
    }

    public function register_sub_menu() {
        add_submenu_page(
            'tools.php', __('GravityForms Entries To Telegram Chats','gft'), __('GF Entries To Telegram Chats','gft'), 'manage_options', 'gf-to-telegram', array($this, 'submenu_page_callback')
        );
    }

    public function submenu_page_callback() {
        include "template/settings.php";
    }

}
