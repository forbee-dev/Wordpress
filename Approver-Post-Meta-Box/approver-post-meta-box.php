<?php
/****** Approver Post Meta Box ******/

/**
 * Adds a meta box to the post editor for selecting an approver.
 * @return void
 */

 function add_post_approver_meta_box() {
    $post_types = array( 'post', 'page', 'bonus', 'slot', 'offer' );

    foreach ($post_types as $post_type) {
        add_meta_box(
            'post_approver_meta_box',
            'Approver',
            'render_post_approver_meta_box',
            $post_type,
            'side',
            'default'
        );
    }
}
add_action('add_meta_boxes', 'add_post_approver_meta_box');

/**
 * Responsible for rendering a select field in the post editor's meta box for selecting an approver.
 * @param $post
 * @return void
 */
function render_post_approver_meta_box($post) {
    // Get the list of authors
    $authors = get_users();

    // Get the currently selected approver for the post
    $selected_approver = get_post_meta($post->ID, 'approver', true);

    // Render the select field
    echo '<select name="approver">';
    // Add an option for null value
    $null_selected = ($selected_approver === null) ? 'selected' : '';
    echo '<option value="" ' . $null_selected . '>None</option>';
    foreach ($authors as $author) {
        $author_id = $author->ID;
        $author_name = $author->display_name;

        // Check if the current option is selected
        $selected = ($selected_approver == $author_id) ? 'selected' : '';

        echo '<option value="' . $author_id . '" ' . $selected . '>' . $author_name . '</option>';
    }
    echo '</select>';
}

/**
 * This function is triggered when a post is saved,
 * It updates the "approver" meta field with the selected value from the post editor's meta box.
 * @param $post_id
 * @return void
 */
function save_post_approver_meta_field($post_id) {
    if (isset($_POST['approver'])) {
        // Update the "approver" meta field with the selected value
        update_post_meta($post_id, 'approver', sanitize_text_field($_POST['approver']));
    }
}
add_action('save_post', 'save_post_approver_meta_field');

/** Approver Quick Edit Meta Box*/

/**
 * Adds a column for default post types
 * @param $column_array
 * @return mixed
 */
function casinobonusar_approver_columns( $column_array ) {
    $column_array[ 'approver' ] = 'Approver';
    return $column_array;
}
add_filter( 'manage_posts_columns', 'casinobonusar_approver_columns' );
add_filter( 'manage_pages_columns', 'casinobonusar_approver_columns' );

/**
 * Adds approver filter to the post list table
 * @return void
 */
function casinobonusar_add_approver_filter() {
    $screen = get_current_screen();
    $post_type = $screen->post_type;

    // Specify the post types for which you want to display the filter
    $allowed_post_types = array( 'post', 'page', 'bonus', 'slot', 'offer' );

    if ( in_array( $post_type, $allowed_post_types ) ) {
        $users = get_users();
        if ( ! empty( $users ) ) {
            ?>
            <select name="approver" id="filter-by-approver">
                <option value="">All Approvers</option>
                <?php foreach ( $users as $user ) : ?>
                    <option value="<?php echo $user->ID; ?>" <?php selected( $_GET['approver'], $user->ID ); ?>><?php echo $user->display_name; ?></option>
                <?php endforeach; ?>
            </select>
            <?php
        }
    }
}
add_action( 'restrict_manage_posts', 'casinobonusar_add_approver_filter' );

function casinobonusar_filter_posts_by_approver( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) {
        return;
    }

    $screen = get_current_screen();
    $post_type = $screen->post_type;

    // Specify the post types for which you want to apply the filter
    $allowed_post_types = array( 'post', 'page', 'bonus', 'slot', 'offer' );

    if ( in_array( $post_type, $allowed_post_types ) && isset( $_GET['approver'] ) && $_GET['approver'] !== '' ) {
        $query->set( 'meta_key', 'approver' );
        $query->set( 'meta_value', $_GET['approver'] );
    }
}
add_action( 'pre_get_posts', 'casinobonusar_filter_posts_by_approver' );

/**
 * Adds Proofleads column to the user table
 * @param $columns
 * @return mixed
 */
// Add new column to the user table
function casinobonusar_user_proofreads_column( $columns ) {
    $columns['proofreads'] = 'Proofreads';
    return $columns;
}
add_filter( 'manage_users_columns', 'casinobonusar_user_proofreads_column' );

/**
 * Display the count of approved posts in the new column with a link to the filtered posts
 * @param $value	
 * @param $column_name
 * @param $user_id
 * @return string
 */
function casinobonusar_user_proofreads_column_content( $value, $column_name, $user_id ) {
    if ( 'proofreads' === $column_name ) {
		$post_types = array( 'post', 'page', 'bonus', 'slot', 'offer' );

        $approved_count = new WP_Query( array(
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'meta_key'       => 'approver',
            'meta_value'     => $user_id,
            'posts_per_page' => -1,
        ) );

        $count = $approved_count->found_posts;
        wp_reset_postdata();

        return $count;
    }
    return $value;
}
add_action( 'manage_users_custom_column', 'casinobonusar_user_proofreads_column_content', 10, 3 );

/**
 * Populate the "approver" column in the manage posts screen.
 * @param string $column_name The name of the column being populated.
 * @param int    $post_id     The ID of the current post.
 */
function casinobonusar_populate_approver_columns( $column_name, $post_id ) {
    switch ( $column_name ) {
        case 'approver':
            $approver = get_post_meta( $post_id, 'approver', true );
            $approver_name = ($approver) ? get_the_author_meta('display_name', $approver) : 'None';
            $post_type = get_post_type( $post_id );
            echo '<a href="/wp-admin/edit.php?post_type=' . $post_type . '&approver=' . $approver . '">' . $approver_name . '</a>';
            break;
    }
}
add_action( 'manage_posts_custom_column', 'casinobonusar_populate_approver_columns', 10, 2 );
add_action( 'manage_pages_custom_column', 'casinobonusar_populate_approver_columns', 10, 2 );

/**
 * Save the bulk edit data for the "approver" meta field.
 * @param int $post_id The ID of the post being saved.
 */
function casinobonusar_bulk_edit_save( $post_id ) {
    // Check the bulk edit nonce
    if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-posts' ) ) {
        return;
    }

    // Check if the "approver" value is set in the request
    if ( isset( $_REQUEST['approver'] ) ) {
        $approver = sanitize_text_field( $_REQUEST['approver'] );

        // Update the "approver" meta field with the selected value
        update_post_meta( $post_id, 'approver', $approver );
    }
}
add_action( 'save_post', 'casinobonusar_bulk_edit_save' );

/**
 * Add the quick edit custom box for the "approver" column.
 * @param string $column_name The name of the column being edited.
 * @param string $post_type   The post type of the column being edited.
 */
function casinobonusar_add_quick_edit_custom_box( $column_name, $post_type ) {
    if ( $column_name === 'approver' ) {
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <label class="inline-edit-group">
                    <span class="title">Approver</span>
                    <span class="input-text-wrap">
                        <select name="approver">
                            <option value="">None</option>
                            <?php
                            $authors = get_users();
                            foreach ( $authors as $author ) {
                                $author_name = $author->display_name;
                                $author_ID = $author->ID;
                                ?>
                                <option value="<?php echo esc_attr( $author_ID ); ?>"><?php echo esc_html( $author_name ); ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </span>
                </label>
            </div>
        </fieldset>
        <?php
    }
}

//enable if you want quick edit for single custom box
add_action( 'quick_edit_custom_box', 'casinobonusar_add_quick_edit_custom_box', 10, 2 );
add_action( 'bulk_edit_custom_box', 'casinobonusar_add_quick_edit_custom_box', 10, 2 );

/****** END OF Approver Post Meta Box ******/