<?php
// Add post types choices to ACF field
// Slotjava needs to show Providers on Pages PostType
function my_acf_add_post_types_choices( $field ) {
    // Get all post types
    $post_types = get_post_types( [ 'public' => true ], 'names' );

    // Add choices to the field
    $field['choices'] = array_combine( $post_types, $post_types );

    // Allow multiple selections
    $field['multiple'] = 1;

    return $field;
}
add_filter('acf/load_field/name=post_types', 'my_acf_add_post_types_choices');