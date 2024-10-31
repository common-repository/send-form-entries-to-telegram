<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
use Telegram\Bot\Api;

class GFT_Telegram
{

    private $telegram;

    function __construct()
    {
        add_action('gform_after_submission', [$this, 'after_submission'], 10, 2);
        $token = get_option('gft_data_token');
        if (empty($token)) {
            return;
        }
        $this->telegram = new Api($token);
    }

    function after_submission($entry, $form)
    {
        try{
            
            $get_data = get_option('gft_data_' . $form['id']);
        $get_files = [];
        if (empty($get_data)) {
            return;
        }
        if (!isset($get_data['enabled'])) {
            return;
        }
        if ($get_data['enabled'] != 'on') {
            return;
        }
        if (empty($get_data['channel_id'])) {
            return;
        }

        $msg = '📌' . $form['title'] . "\n\r";
        foreach ($get_data['fields'] as $key => $field) {
            if (GFAPI::get_field($form['id'], $key)->type == 'fileupload') {
                if (!empty(rgar($entry, $key))) {
                    $temp = [];
                    if ($this->isJson(rgar($entry, $key))) {
                        $temp['file'] = json_decode(rgar($entry, $key), true);
                    } else {
                        $temp['file'] = rgar($entry, $key);
                    }
                    $temp['label'] = GFAPI::get_field($form['id'], $key)->label;
                    array_push($get_files, $temp);
                }
            } else {
                $msg .= '🔹' . GFAPI::get_field($form['id'], $key)->label . " : " . rgar($entry, $key) . "\n\r";
            }
        }

        if (!empty($get_data['attrs'])) {
            foreach ($get_data['attrs'] as $key => $field) {
                if ($key == 'ip') {
                    $msg .= '🔹 '.__('IP','gft').' : ' . $entry['ip'] . "\n\r";
                }
                if ($key == 'source_url') {
                    $msg .= '🔹 '.__('Source Url','gft').' : ' . $entry['source_url'] . "\n\r";
                }
                if ($key == 'created_by') {
                    if (!empty($entry['created_by'])) {
                        $user = get_user_by('id', $entry['created_by']);
                        $msg .= '🔹 '.__('Created By','gft').' : ' . $user->display_name . "\n\r";
                    } else {
                        $msg .= '🔹 '.__('Created By','gft').' : ' . __('Visitor','gft') . "\n\r";
                    }
                }
                if ($key == 'payment_status') {
                    $msg .= '🔹 '.__('Payment Status','gft').' : ' . ($entry['payment_status'] ?? __('Unknown','gft')) . "\n\r";
                }
                if ($key == 'payment_amount') {
                    $msg .= '🔹 '.__('Payment Amount','gft').' : ' . ($entry['payment_amount'] ?? __('Unknown','gft')) . "\n\r";
                }
            }
        }

        $response = '';

        if (strpos($get_data['channel_id'], ",") === false) {
            
            $response = $this->telegram->sendMessage([
                'chat_id' => $get_data['channel_id'],
                'text' => $msg . "\n\r"
            ]);


            if (!empty($get_files)) {
                foreach ($get_files as $file) {
                    if (gettype($file['file']) == 'array') {
                        foreach ($file['file'] as $f) {
                            $this->telegram->sendDocument([
                                'chat_id' => $get_data['channel_id'],
                                'document' => Telegram\Bot\FileUpload\InputFile::create($f, basename($f)),
                                'caption' => $file['label'],
                                'reply_to_message_id' => $response->getMessageId()
                            ]);
                        }
                    } else {
                        $this->telegram->sendDocument([
                            'chat_id' => $get_data['channel_id'],
                            'document' => Telegram\Bot\FileUpload\InputFile::create($file['file'], basename($file['file'])),
                            'caption' => $file['label'],
                            'reply_to_message_id' => $response->getMessageId()
                        ]);
                    }

                }
            }

        } else {

            $rs = explode(",", $get_data['channel_id']);
            foreach ($rs as $r) {
                
                $response = $this->telegram->sendMessage([
                    'chat_id' => $r,
                    'text' => $msg . "\n\r"
                ]);

                if (!empty($get_files)) {
                    foreach ($get_files as $file) {
                        if (gettype($file['file']) == 'array') {
                            foreach ($file['file'] as $f) {
                                $this->telegram->sendDocument([
                                    'chat_id' => $r,
                                    'document' => Telegram\Bot\FileUpload\InputFile::create($f, basename($f)),
                                    'caption' => $file['label'],
                                    'reply_to_message_id' => $response->getMessageId()
                                ]);
                            }
                        } else {
                            $this->telegram->sendDocument([
                                'chat_id' => $r,
                                'document' => Telegram\Bot\FileUpload\InputFile::create($file['file'], basename($file['file'])),
                                'caption' => $file['label'],
                                'reply_to_message_id' => $response->getMessageId()
                            ]);
                        }

                    }
                }

            }

        }


        update_option('gft_data_' . $form['id'] . '_log', json_encode($response));
            
        }catch(Exception $e){
             update_option('gft_data_' . $form['id'] . '_log', json_encode($e->getMessage()));
        }
    
    }

    function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    
    function sendToTelegram($func){
        try{
            return $func();
        }catch(Exception $e){
            
        }
    }

}
