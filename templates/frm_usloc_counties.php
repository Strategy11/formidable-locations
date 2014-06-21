<?php
global $frmdb;

$values['name'] = __('U.S. Counties', 'formidable');

if (!$form){
    $form_id = $frm_form->create( $values );
}else{
    $form_id = $form->id;
    $frm_form->update($form_id, $values );
    $state = $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_state_data'));
    $county = $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_county'));
    $county_id = $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_countyid'));
    unset($form);
}

$abr = $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_state_abr'));
if(!$abr) return;

if(!isset($state) or !$state){
    $field_values = apply_filters('frm_before_field_created', FrmFieldsHelper::setup_new_vars('data', $form_id));
    $field_values['field_key'] = 'frm_usloc_state_data';
    $field_values['name'] = __('State', 'formidable');
    $field_values['required'] = 1;
    $field_values['field_options']['data_type'] = 'select';
    $field_values['field_options']['form_select'] =  $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_state_abr'));  //ID of state field in state form
    $state = $frm_field->create( $field_values );
}

if(!isset($county) or !$county){
    $field_values = apply_filters('frm_before_field_created', FrmFieldsHelper::setup_new_vars('text', $form_id));
    $field_values['field_key'] = 'frm_usloc_county';
    $field_values['name'] = __('County', 'formidable');
    $field_values['required'] = 1;
    $county = $frm_field->create( $field_values );
}

if(!isset($county_id) or !$county_id){
    $field_values = apply_filters('frm_before_field_created', FrmFieldsHelper::setup_new_vars('hidden', $form_id));
    $field_values['field_key'] = 'frm_usloc_countyid';
    $field_values['name'] = __('County ID', 'formidable');
    $field_values['field_options']['unique'] = 1;
    $county_id = $frm_field->create( $field_values );
}

unset($field_values);


$opts = get_option('frm_usloc_options');
if(!is_array($opts)) return;
if(!isset($opts['counties'])) $opts['counties'] = 1;
if(isset($opts['states_complete']) and $opts['counties'] < 3098){
    // Import counties from CSV
    $opts['counties'] = FrmProXMLHelper::import_csv( dirname(dirname(__FILE__)) . '/us_counties.csv', $form_id, array(
        0 => $county_id, 1 => $county, 
        4 => array('field_id' => $state, 'type' => 'data', 'linked' => $abr)), 
        1, $opts['counties']+1
    );
    
    if($opts['counties'] >= 3098) $opts['counties_complete'] = 1;
    update_option('frm_usloc_options', $opts);
}


//0 = county ID
//1 = county name
//4 = State abbreviation
