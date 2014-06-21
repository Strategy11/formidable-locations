<?php
global $frmdb;

$values['name'] = __('U.S. Cities', 'formidable');

if (!$form){
    $form_id = $frm_form->create( $values );
}else{
    $form_id = $form->id;
    $frm_form->update($form_id, $values );
    unset($form);
    
    $state = $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_statecounty_data'));
    $county = $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_county_data'));
    $city = $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_city'));
}

$abr = $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_state_abr'));
if(!$abr) return;

if(!isset($state) or !$state){
    $field_values = apply_filters('frm_before_field_created', FrmFieldsHelper::setup_new_vars('data', $form_id));
    $field_values['field_key'] = 'frm_usloc_statecounty_data';
    $field_values['name'] = __('State', 'formidable');
    $field_values['required'] = 1;
    $field_values['field_options']['data_type'] = 'select';
    $field_values['field_options']['form_select'] =  $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_state'));  //ID of state field in state form
    $state = $frm_field->create( $field_values );
}

if(!isset($county) or !$county){
    $field_values = apply_filters('frm_before_field_created', FrmFieldsHelper::setup_new_vars('data', $form_id));
    $field_values['field_key'] = 'frm_usloc_county_data';
    $field_values['name'] = __('County', 'formidable');
    $field_values['field_options']['data_type'] = 'select';
    $field_values['field_options']['form_select'] =  $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_county'));  //ID of county field in county form
    $field_values['field_options']['hide_field'] = $state; //set state dependency
    $county = $frm_field->create( $field_values );
}

if(!isset($city) or !$city){
    $field_values = apply_filters('frm_before_field_created', FrmFieldsHelper::setup_new_vars('text', $form_id));
    $field_values['field_key'] = 'frm_usloc_city';
    $field_values['name'] = __('City', 'formidable');
    $field_values['required'] = 1;
    $city = $frm_field->create( $field_values );
}

unset($field_values);


$opts = get_option('frm_usloc_options');
if(!is_array($opts)) return;
if(!isset($opts['cities'])) $opts['cities'] = 1;
if(isset($opts['counties_complete']) and $opts['cities'] < 51935){
    $county_id = $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_countyid'));
    // Import cities from CSV
    $opts['cities'] = FrmProXMLHelper::import_csv( dirname(dirname(__FILE__)) . '/us_cities.csv', $form_id, array(
        1 => $city, 3 => array('field_id' => $county, 'type' => 'data', 'linked' => $county_id),
        5 => array('field_id' => $state, 'type' => 'data', 'linked' => $abr)), 1, $opts['cities']+1
    );
    
    if($opts['cities'] >= 51935) $opts['cities_complete'] = 1;
    update_option('frm_usloc_options', $opts);
}

//1 = city name
//2 = county name
//3 = county id 
//5 = State abr
