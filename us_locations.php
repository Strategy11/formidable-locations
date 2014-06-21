<?php
/*
Plugin Name: Formidable Locations
Plugin URI: http://strategy11.com/formidable-wordpress-plugin
Description: Create forms and populate with Countries, states/provinces, and U.S. counties and cities
Author: Strategy11
Author URI: http://strategy11.com
Version: 1.0.02
*/

add_action('wp_ajax_frm_usloc_install', 'frm_usloc_create' );
function frm_usloc_create(){
    if ( !class_exists('FrmFormsController') ) {
        return;
    }
    
    $xml_file = FrmAppHelper::plugin_path() .'/pro/classes/helpers/FrmProXMLHelper.php';
    if ( !class_exists('FrmProXMLHelper') && file_exists($xml_file) ) {
        include_once($xml_file);
    }
    
    ini_set('display_errors', '0');
    FrmFormsController::add_default_templates( dirname(__FILE__) .'/templates', false, false);
        
    $opts = get_option('frm_usloc_options');
    echo frm_usloc_left_count($opts);
    die();
}

add_action('wp_ajax_frm_usloc_hide_install', 'frm_usloc_hide_install');
function frm_usloc_hide_install(){
    $opts = get_option('frm_usloc_options');
    $opts['dismiss'] = true;
    update_option('frm_usloc_options', $opts);
    die();
}

add_action('admin_init', 'frm_loc_include_updater', 1);
function frm_loc_include_updater(){
    if(!class_exists('FrmUpdate')) return;
    
    include_once(dirname(__FILE__) .'/FrmLocUpdate.php');
    global $frm_loc_update;
    $frm_loc_update = new FrmLocUpdate();
}

add_action('admin_notices', 'frm_usloc_get_started_headline');
function frm_usloc_get_started_headline(){
    if(!class_exists('FrmFormsController')) return;

    // Don't display this error as we're upgrading the thing...
    if ( isset($_GET['activate']) || (isset($_GET['action']) && $_GET['action'] == 'upgrade-plugin') || (isset($_GET['page']) && $_GET['page'] == 'formidable-settings') || (is_multisite() && !current_user_can('administrator')) ) {
        return;
    }

    //delete_option('frm_usloc_options');
    $opts = get_option('frm_usloc_options');

    if(!is_array($opts) or   
        !isset($opts['states_complete']) or
        !isset($opts['counties_complete']) or
        !isset($opts['cities_complete'])
    ){
        if(isset($opts['dismiss'])) return;
        
    $url = FrmAppHelper::plugin_url();
?>
<div class="error" id="frm_usloc_install_message" style="padding:7px;"><?php is_array($opts) ?  _e('Your Formidable U.S. Locations plugin needs to import more locations.', 'formidable') : _e('Your Formidable U.S. Locations forms need to be updated.', 'formidable'); ?> <a href="<?php echo admin_url('admin.php') ?>?page=formidable-settings#frm_usloc_install_message"><?php _e('Import Now', 'formidable') ?></a> or <a href="javascript:frm_usloc_dismiss()">Remove this message</a></div> 
<script type="text/javascript">
<!--
function frm_usloc_dismiss(){
    jQuery('#frm_usloc_install_link').replaceWith('<img src="<?php echo $url ?>/images/wpspin_light.gif" alt="<?php _e('Loading...', 'formidable'); ?>" />');
    jQuery.ajax({type:"POST",url:"<?php echo admin_url('admin-ajax.php') ?>",data:"action=frm_usloc_hide_install",
    success:function(count){jQuery("#frm_usloc_install_message").fadeOut("slow");}
    });
}
-->
</script> 
<?php
    }
}


add_action('frm_settings_form', 'frm_usloc_settings');
function frm_usloc_settings(){
    $min_version = '1.07.05';
    $frm_version = method_exists('FrmAppHelper', 'plugin_version') ? FrmAppHelper::plugin_version() : 0;
    
    // check if Formidable meets minimum requirements
    if ( version_compare($frm_version, $min_version, '<') ) {
    ?>
<div class="with_frm_style" id="frm_usloc_install_message" style="margin:15px 0;line-height:2.5em;"><span class="frm_message" style="padding:7px;"><?php _e('Your version of Formidable does not support this add-on. Please update Formidable.', 'formidable') ?></span></div>
    <?php
        return;
    }
    
    $opts = get_option('frm_usloc_options');

    if(!is_array($opts) or   
        !isset($opts['countries_complete']) or
        !isset($opts['states_complete']) or
        !isset($opts['counties_complete']) or
        !isset($opts['cities_complete'])
    ){
        $left = frm_usloc_left_count($opts);
        $url = FrmAppHelper::plugin_url();
    ?>
<div class="with_frm_style" id="frm_usloc_install_message" style="margin:15px 0;line-height:2.5em;"><span class="frm_message" style="padding:7px;"><?php is_array($opts) ?  printf(__('Your Formidable U.S. Locations plugin needs to import the next 250 of the remaining %1$s locations.', 'formidable'), $left) : _e('Your Formidable U.S. Locations forms need to be updated.', 'formidable'); ?> <a id="frm_usloc_install_link" class="button-secondary" href="javascript:frm_usloc_install_now()"><?php _e('Import Now', 'formidable') ?></a></span></div>
<script type="text/javascript">
<!--
<?php if(isset($_GET['install_frm_usloc'])){ ?>
setTimeout( "frm_usloc_install_now()", 250 );
<?php } ?>
function frm_usloc_install_now(){ 
    jQuery('#frm_usloc_install_link').replaceWith('<img src="<?php echo $url ?>/images/wpspin_light.gif" alt="<?php _e('Loading...', 'formidable'); ?>" />');
    jQuery.ajax({type:"POST",url:"<?php echo admin_url('admin-ajax.php') ?>",data:"action=frm_usloc_install&frm_skip_cookie=1",
    success:function(count){
        if(parseInt(count) > 0){ jQuery("#frm_usloc_install_message .frm_message").html('Your Formidable U.S. Locations plugin needs to import the next 250 of the remaining '+count+' locations.<br/> If your browser doesn&#8217;t start loading the next set automatically, click this button: <a id="frm_usloc_install_link"  class="button-secondary" href="javascript:frm_usloc_install_now()">Import Now</a>');
            location.href = "?page=<?php echo $_GET['page'] ?>&install_frm_usloc=1";
        }else jQuery("#frm_usloc_install_message").fadeOut("slow");
    }
    });
};
//-->
</script>
<?php
    }
}

function frm_usloc_left_count($opts){
    $imported = 0;
    foreach(array('countries','states','counties','cities') as $loc){
        if(isset($opts[$loc]))
            $imported = $imported + (int)$opts[$loc];
    }
    
    return (272+3978+3098+51935) - $imported;
}

?>