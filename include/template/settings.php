<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div id="gform_tab_group" class="gform_tab_group vertical_tabs">
    <h1><?php echo __('GravityForms Entries To Telegram Chats','gft');?>  <?php if(empty(get_option('gft_data_token'))):?><span class="gft_err_token"><?php echo __('Bot Token is Empty','gft');?></span><?php endif;?></h1>
    <br>
    <h2><?php echo __('Forms','gft');?></h2>
    <?php
    foreach (GFAPI::get_forms() as $form){
       $f = GFAPI::get_form($form['id']);
       $values = get_option('gft_data_'.$f['id']);
       ?><form id="gft_update"><div class="warp_gft"><h6> <input title="<?php echo __('Enable / Disable Form','gft');?>" <?php echo($values['enabled'] == 'on' ? 'checked' : '');?> type="checkbox" name="enabled"> <?php echo $f['title'];?></h6>
            <?php
       foreach ($f['fields'] as $field){
           ?>
           <label for="field_<?php echo $f['id']."_".$field->id?>">
               <?php echo $field->label;?>
               <input title="<?php echo __('Enable / Disable','gft');?>" <?php $this->checked_html($f['id'],$field->id)?> type="checkbox" id="field_<?php echo $f['id']."_".$field->id?>" name="field[<?php echo $field->id?>]">
           </label>

           <?php
       }
        ?>
            <label for="attrs_<?php echo $f['id']."_"?>ip">
                <?php echo __('IP','gft');?>
                <input <?php echo($values['attrs']['ip'] == 'on' ? 'checked' : '')?> type="checkbox" id="attrs_<?php echo $f['id']."_"?>ip" name="attrs[ip]">
            </label>
            <label for="attrs_<?php echo $f['id']."_"?>source_url">
                <?php echo __('Source Url','gft');?>
                <input <?php echo($values['attrs']['source_url'] == 'on' ? 'checked' : '')?> type="checkbox" id="attrs_<?php echo $f['id']."_"?>source_url" name="attrs[source_url]">
            </label>
            <label for="attrs_<?php echo $f['id']."_"?>created_by">
                <?php echo __('Created By','gft');?>
                <input <?php echo($values['attrs']['created_by'] == 'on' ? 'checked' : '')?> type="checkbox" id="attrs_<?php echo $f['id']."_"?>created_by" name="attrs[created_by]">
            </label>
            <label for="attrs_<?php echo $f['id']."_"?>payment_status">
                <?php echo __('Payment Status','gft');?>
                <input <?php echo($values['attrs']['payment_status'] == 'on' ? 'checked' : '')?> type="checkbox" id="attrs_<?php echo $f['id']."_"?>payment_status" name="attrs[payment_status]">
            </label>
            <label for="attrs_<?php echo $f['id']."_"?>payment_amount">
                <?php echo __('Payment Amount','gft');?>
                <input <?php echo($values['attrs']['payment_amount'] == 'on' ? 'checked' : '')?> type="checkbox" id="attrs_<?php echo $f['id']."_"?>payment_amount" name="attrs[payment_amount]">
            </label>
            <hr>

            <label>
                <?php echo __('Chat ID (ex: Chanel ID Like @mychannel). Using , you can separate the Chats','gft');?>
                <input value="<?php echo $values['channel_id'];?>" style="text-align: left; direction: ltr;" type="text" name="channel_id">
            </label>

            <input type="hidden" name="form" value="<?php echo $f['id']?>">
            <button class="button" style="display: block;margin-top: 15px;" type="submit"><?php echo __('Save Changes','gft');?></button>
            <button onclick="document,getElementById('log_<?php echo $f['id'];?>').classList.toggle('d-none-x')" class="button" style="display: block;margin-top: -30px;float:left;" type="button"><?php echo __('Toggle Log','gft');?></button>
            <div class="d-none-x" id="log_<?php echo $f['id'];?>">
            <p><?php echo __('Latest Log:','gft');?></p>
            <textarea style="width:100%;height:200px;text-align: left;direction: ltr;"><?php print_r(json_decode(get_option('gft_data_' . $f['id'] . '_log')));?></textarea>
            </div>
            
            </div></form>

            <?php
    }
    ?>

    <hr>
    <h2><?php echo __('Configuration','gft');?></h2>
    <form id="gft_token">
        <div class="warp_gft" style="background: #fff;">
            <h6><?php echo __('Token Bot','gft');?></h6>
            <input value="<?php echo get_option('gft_data_token');?>" style="text-align: left; direction: ltr;width: 100%;" type="text" name="gft_data_token">
            <button class="button" style="display: block;margin-top: 15px;" type="submit"><?php echo __('Save Changes','gft');?></button>
        </div>
        <br>
        <div class="warp_gft" style="background: #ff00001f;">
            <p><strong><?php echo __('Note For Channels: Your bot should be used as a channel admin','gft')?></strong></p>
            <p><a target="_blank" href="<?php echo plugins_url("assets/img/how_chat_id.gif",GFTOTELEGRAM_FILE)?>"><?php echo __('How To Get Chat ID For Private Channels? Click Here','gft')?></a></p>
        </div>
    </form>
    <style>.d-none-x{display:none;}</style>
</div>
