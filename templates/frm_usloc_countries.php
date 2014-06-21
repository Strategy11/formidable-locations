<?php
global $frmdb;

$values['name'] = __('Countries', 'formidable');

if (!$form){
    $form_id = $frm_form->create( $values );
}else{
    $form_id = $form->id;
    $frm_form->update($form_id, $values );
    unset($form);
    $country = $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_country'));
    $code2 = $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_country_code2'));
    $code3 = $frmdb->get_var($frmdb->fields, array('field_key' => 'frm_usloc_country_code3'));
}

if(!isset($country) or !$country){
    $field_values = apply_filters('frm_before_field_created', FrmFieldsHelper::setup_new_vars('text', $form_id));
    $field_values['field_key'] = 'frm_usloc_country';
    $field_values['name'] = __('Country', 'formidable');
    $country = $frm_field->create( $field_values );
}

if(!isset($code2) or !$code2){
    $field_values = apply_filters('frm_before_field_created', FrmFieldsHelper::setup_new_vars('text', $form_id));
    $field_values['field_key'] = 'frm_usloc_country_code2';
    $field_values['name'] = __('2 Letter Code', 'formidable');
    $field_values['required'] = 1;
    $code2 = $frm_field->create( $field_values );
}

if(!isset($code3) or !$code3){
    $field_values = apply_filters('frm_before_field_created', FrmFieldsHelper::setup_new_vars('text', $form_id));
    $field_values['field_key'] = 'frm_usloc_country_code3';
    $field_values['name'] = __('3 Letter Code', 'formidable');
    $field_values['required'] = 1;
    $field_values['field_options']['unique'] = 1;
    $code3 = $frm_field->create( $field_values );
}

unset($field_values);


$opts = get_option('frm_usloc_options');
if(!is_array($opts)) $opts = array();
if(!isset($opts['countries'])) $opts['countries'] = 1;
if($opts['countries'] < 272){
    // Import countries from CSV
    $opts['countries'] = FrmProXMLHelper::import_csv( dirname(dirname(__FILE__)) . '/countries.csv', $form_id, array(
        0 => $country, 1 => $code2, 2 => $code3), 2, $opts['countries']+1
    );
    
    if($opts['countries'] >= 272) $opts['countries_complete'] = 1;
    update_option('frm_usloc_options', $opts);
}
