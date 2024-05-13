<?php
/**
 * Add a Read Only field setting to ACF fields
 */
if (!function_exists('add_acf_readonly_field')) {
    function add_acf_readonly_field( $field ) {
        acf_render_field_setting( $field, array(
            'label' => __( 'Read Only?', 'acf' ),
            'instructions' => '',
            'type' => 'radio',
            'name' => 'readonly',
            'choices' => array(
                0 => __( "No", 'acf' ),
                1 => __( "Yes", 'acf' ),
            ),
            'layout' => 'horizontal',
        ) );
    }
}
add_action( 'acf/render_field_settings', 'add_acf_readonly_field' );