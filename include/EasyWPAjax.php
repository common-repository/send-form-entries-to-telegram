<?php
// Easy WP Ajax
// version 1.1.2
// © Salar Sadeghi, 2020
// https://gitlab.com/sadeghisalar
//
// GitLab page:     https://gitlab.com/sadeghisalar/easy-wp-ajax
//
// Released under GNU licence
// =========================================================
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
if( !class_exists('EasyWPAjax') ){
    class EasyWPAjax
    {
        private $calls = [];
        private $ajaxUrl;

        public function __construct($calls , $isCall){
            $this->setCalls($calls);
            $this->ajaxUrl = ( $this->getCalls()['url'] == null ? admin_url('admin-ajax.php') : $this->getCalls()['url'] );
            if($isCall){
                $this->createCall();
            }else{
                $this->generateJs();
            }

        }

        function getCalls(){
            return $this->calls;
        }

        function setCalls($calls){
            $this->calls = $calls;
        }

        public function createCall(){

            if (count($this->getCalls()) < 1){return;}

            $ewa_call = $this->getCalls();


            if( !$this->checkAction($ewa_call) ){
                return;
            }

            if (!isset($ewa_call['callback'])){
                return;
            }

            $userSide = (!isset($ewa_call['userSide']) ? true : $ewa_call['userSide']);

            add_action('wp_ajax_'.$ewa_call['action'],$ewa_call['callback']);

            if($userSide){
                add_action('wp_ajax_nopriv_'.$ewa_call['action'],$ewa_call['callback']);
            }
        }

        public function generateJs(){

            $self = $this;
            $ewa_call = $this->getCalls();

            if( isset($ewa_call['showIF']) ){
                if ($ewa_call['showIF']() !== true){
                    return;
                }
            }

            if( !$this->checkAction($ewa_call) ){
                return;
            }

            if (!isset($ewa_call['callback'])){
                return;
            }

            $userSide = $ewa_call['userSide'];
            $adminSide = $ewa_call['adminSide'];
            $ewa_call['method'] = $method = (!isset($ewa_call['method']) ? 'POST' : $ewa_call['method']);

            if (isset($ewa_call['js']) && !empty($ewa_call['js']) && $ewa_call['js'] !== false){
                $ewa_call['js']['send']['id'] = $ewa_call['action'];

                if (gettype($ewa_call['js']['send']['data']) != 'array'){
                    $ewa_call['js']['send']['data'] = str_replace("mormData",'FormData',$ewa_call['js']['send']['data']);
                }else{
                    $ewa_call['js']['send']['data']['action'] = $ewa_call['action'];
                }
            }else{
                $ewa_call['js'] = false;
            }

            if($userSide){
                add_action('wp_footer',function() use($ewa_call,$self){
                    try {
                        $self->jsTemplate($ewa_call);
                    }catch (Exception $exception){
                        print_r($exception);
                    }
                },99);
            }
            if($adminSide){
                add_action('admin_footer',function() use($ewa_call,$self){
                    try {
                        ob_start();
                        $self->jsTemplate($ewa_call);
                        $wp_add_inline_script_ex = ob_get_clean();
                        wp_add_inline_script('gft-admin-scripts',$wp_add_inline_script_ex);
                    }catch (Exception $exception){
                        print_r($exception);
                    }
                },99);
            }
        }

        public function jsTemplate($ewa_call){
            if ($ewa_call['js'] === false){return;}
            $id = $ewa_call['js']['send']['id'];
            $data = $this->getOption($ewa_call['js']['send'],'data','[]');
            $event = $this->getOption($ewa_call['js']['trigger'],'event','click');
            $element =$ewa_call['js']['trigger']['element'];
            $args = [];
            $argsKeys = implode(",",array_keys($this->getOption($ewa_call['js']['trigger'],'variables',[])));
            if( !empty($this->getOption($ewa_call['js']['trigger'],'variables',null ))  ){
                $argsKeys = ",".$argsKeys;
            }
            foreach($this->getOption($ewa_call['js']['trigger'],'variables',[]) as $arg){
                if($this->isJsVar($arg) !== false){
                    $args[] = $this->getJsVar($arg);
                }else{
                    $args[] = "'".$arg."'";
                }
            }
            $args = (!empty($this->getOption($ewa_call['js']['trigger'],'variables')) ? " ,".implode(',',$args) : '');

            if( strpos($element,':js') === false){
                $element = "'".$element."'";
            }else{
                $element = str_replace(":js",'',$element);
            }

            ?>
            <script type="text/javascript">
                function <?php echo $id?>(trigger = null<?php echo $argsKeys?>){
                    let ajax_url = '<?php echo $this->ajaxUrl;?>';
                    let data = '';
                    <?php if($data === 'FormData'):?>
                    data = new FormData(trigger[0]);
                    data.append('action','<?php echo $ewa_call['action']?>');
                    <?php if( isset($ewa_call['js']['send']['appendData']) && !empty($ewa_call['js']['send']['appendData']) && gettype($ewa_call['js']['send']['appendData']) == 'array' ): ?>
                    <?php foreach ($ewa_call['js']['send']['appendData'] as $fkey => $fData): ?>
                    <?php
                    $f_data=$fData;
                    if($this->isJsVar($f_data)){
                    $f_data = $this->getJsVar($f_data);
                    ?>
                    data.append("<?php echo $fkey;?>",<?php echo $f_data?>);
                    <?php
                    }else{ ?>
                    data.append("<?php echo $fkey;?>",'<?php echo $fData?>');
                    <?php } ?>
                    <?php endforeach; ?>
                    <?php endif;?>
                    <?php else:?>
                    data = new FormData();
                    <?php foreach ($data as $fkey => $fData): ?>
                    <?php
                    $f_data=$fData;
                    if($this->isJsVar($f_data)){
                    $f_data = $this->getJsVar($f_data);
                    ?>
                    data.append("<?php echo $fkey;?>",<?php echo $f_data?>);
                    <?php
                    }else{ ?>
                    data.append("<?php echo $fkey;?>",'<?php echo $fData?>');
                    <?php } ?>
                    <?php endforeach; ?>
                    <?php endif;?>

                    jQuery.ajax({
                        type: "<?php echo $ewa_call['method'];?>",
                        url: ajax_url,
                        data: data,
                        processData: false,
                        contentType: false,
                        success:function(response){
                            <?php
                            if(isset($ewa_call['js']['send']['success'])){
                                echo str_replace(['<script>','</script>'],'',$this->getOption($ewa_call['js']['send'],'success')());
                            }?>
                        },
                        error:function(response){
                            <?php
                            if(isset($ewa_call['js']['send']['error'])){
                                echo str_replace(['<script>','</script>'],'',$this->getOption($ewa_call['js']['send'],'error')());
                            }
                            ?>
                        }
                    });
                }

                <?php if( isset($ewa_call['js']['trigger']) ): ?>
                <?php if(isset($ewa_call['js']['trigger']['element']) && !empty($ewa_call['js']['trigger']['element']) && $ewa_call['js']['trigger']['event'] !== 'custom' ): ?>
                jQuery(document).ready(function(){

                    jQuery(document).on('<?php echo $event?>',<?php echo $element;?>, function(e){
                        e.preventDefault();
                        let trigger = '';
                        trigger = jQuery(e.target);

                        <?php
                        if(isset($ewa_call['js']['trigger']['callback'])){
                            echo str_replace(['<script>','</script>'],'',$this->getOption($ewa_call['js']['trigger'],'callback')());
                        }?>

                        <?php echo $this->getOption($ewa_call['js']['send'],'id')?>(trigger<?php echo $args?>);
                    });

                });
                <?php else:?>
                <?php
                if(isset($ewa_call['js']['trigger']['callback'])){
                    echo str_replace(['<script>','</script>'],'',$this->getOption($ewa_call['js']['trigger'],'callback')());
                }?>
                <?php endif;?>
                <?php endif;?>
            </script>
            <?php
        }

        function getOption($calls,$call,$default = -1000){
            if (array_key_exists($call,$calls)){
                if ($default !== -1000){
                    if( !isset($calls[$call]) && empty($calls[$call]) ){
                        return $default;
                    }
                }
                return $calls[$call];
            }else{
                if ($default !== -1000){
                    if( !isset($calls[$call]) && empty($calls[$call]) ){
                        return $default;
                    }
                }
            }
            return '';
        }

        function checkAction($ewa_call){
            if ( !isset($ewa_call['action']) && empty($ewa_call['action']) ){return false;}
            return true;
        }

        function isJsVar($str){
            if (substr($str,-1,1) == '%' && substr($str,0,1) == '%'){
                return true;
            }
            return false;
        }

        function getJsVar($str){
            return str_replace("%",'',$str);
        }

    }
    class EasyWPAjaxCreateUserJs{
        function __construct(){
            add_action('wp',function (){
                foreach (apply_filters('wp_easy_ajax',[]) as $call){
                    $call['userSide'] = $userSide = (!isset($call['userSide']) ? true : $call['userSide']);
                    $call['adminSide'] = $adminSide = (!isset($call['adminSide']) ? false : $call['adminSide']);
                    if (class_exists('EasyWPAjax')){
                        new EasyWPAjax($call,false);
                    }
                }
            });

            add_action('wp_head',function (){?>
                <script type="text/javascript">
                    window.ewa = {
                        ajax_url : '<?php echo admin_url('admin-ajax.php')?>',
                        ID : <?php the_ID();?>,
                        title : '<?php the_title();?>',
                        url : '<?php the_permalink();?>',
                        type : '<?php echo get_post_type();?>',
                        is_single : <?php echo intval(is_single());?>,
                        is_singular : <?php echo intval(is_singular());?>,
                        is_page : <?php echo intval(is_page());?>,
                        is_front_page : <?php echo intval(is_front_page());?>,
                        is_home : <?php echo intval(is_home());?>,
                        is_search : <?php echo intval(is_search());?>,
                        is_archive : <?php echo intval(is_archive());?>,
                        is_category : <?php echo intval(is_category()); ?>,
                        is_tag : <?php echo intval(is_tag());?>,
                        is_tax : <?php echo intval(is_tax());?>,
                        is_user_logged_in : <?php echo intval(is_user_logged_in()); ?>,
                    };
                </script>
                <?php
            },1);
        }
    }
    class EasyWPAjaxCreateAdminJs{

        public function __construct(){

            add_action('wp_loaded',function (){
                if (is_admin()) {
                    if (class_exists('EasyWPAjax')){
                        foreach (apply_filters('wp_easy_ajax',[]) as $call) {
                            $call['userSide'] = $userSide = (!isset($call['userSide']) ? true : $call['userSide']);
                            $call['adminSide'] = $adminSide = (!isset($call['adminSide']) ? false : $call['adminSide']);
                            new EasyWPAjax($call, false);
                        }
                    }
                }
            });
        }
    }
    class EasyWPAjaxCreateCalls{
        public function __construct(){
            foreach (apply_filters('wp_easy_ajax',[]) as $call){
                new EasyWPAjax($call,true);
            }
        }
    }

    add_action('init',function (){
        new EasyWPAjaxCreateCalls();
        new EasyWPAjaxCreateUserJs();
        new EasyWPAjaxCreateAdminJs();
    });
}
