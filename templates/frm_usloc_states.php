<?php
global $frmdb;

$values['name'] = __('States/Provinces', 'formidable');

if (!$form){
    $form_id = $frm_form->create( $values );
}else{
    $form_id = $form->id;
    $frm_form->update($form_id, $values );
    unset($form);
    $country = $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_country_data'));
    $state = $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_state'));
    $abr = $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_state_abr'));
}

$code = $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_country_code2'));
if(!$code) return;

if(!isset($country) or !$country){
    $field_values = apply_filters('frm_before_field_created', FrmFieldsHelper::setup_new_vars('data', $form_id));
    $field_values['field_key'] = 'frm_usloc_country_data';
    $field_values['name'] = __('Country', 'formidable');
    $field_values['field_options']['data_type'] = 'select';
    $field_values['field_options']['form_select'] =  $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_country'));  //ID of state field in state form
    $country = $frm_field->create( $field_values );
}

if(!isset($state) or !$state){
    $field_values = apply_filters('frm_before_field_created', FrmFieldsHelper::setup_new_vars('text', $form_id));
    $field_values['field_key'] = 'frm_usloc_state';
    $field_values['name'] = __('State', 'formidable');
    $field_values['required'] = 1;
    $state = $frm_field->create( $field_values );
}

if(!isset($abr) or !$abr){
    $field_values = apply_filters('frm_before_field_created', FrmFieldsHelper::setup_new_vars('text', $form_id));
    $field_values['field_key'] = 'frm_usloc_state_abr';
    $field_values['name'] = __('State Abbreviation', 'formidable');
    $field_values['required'] = 1;
    $field_values['field_options']['unique'] = 1;
    $abr = $frm_field->create( $field_values );
}

unset($field_values);


$opts = get_option('frm_usloc_options');
if(!is_array($opts)) $opts = array();
if(!isset($opts['states'])) $opts['states'] = 1;
if(isset($opts['countries_complete']) and $opts['states'] < 3978){
    // Import states from CSV
    $opts['states'] = FrmProXMLHelper::import_csv( dirname(dirname(__FILE__)) . '/us_states.csv', $form_id, array(
        0 => $state, 1 => $abr, 2 => array('field_id' => $country, 'type' => 'data', 'linked' => $code)),
        1, $opts['states']+1
    );
    
    if($opts['states'] >= 3978) $opts['states_complete'] = 1;
    update_option('frm_usloc_options', $opts);
}
