<?php
/**
 * Plugin Name: Programmatic Content Changer
 * Description: Update post titles, meta titles, and meta descriptions using Yoast SEO from a CSV file.
 * Version: 1.0
 * Author: Tiago Santos
 */

// Register a custom menu page
add_action('admin_menu', 'content_updater_menu');

function content_updater_menu(){
    add_menu_page('Content Updater', 'Content Updater', 'manage_options', 'content-updater', 'content_updater_page');
}

// Register plugin settings
add_action('admin_init', 'content_updater_register_settings');

function content_updater_register_settings() {
    // Register a setting for batch size
    register_setting('content_updater_settings_group', 'content_updater_batch_size', array(
        'type' => 'integer',
        'default' => 10,
        'sanitize_callback' => 'absint' // Ensure batch size is a positive integer
    ));
}

// Add settings link on plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'content_updater_settings_link');

function content_updater_settings_link($links) {
    $settings_link = '<a href="admin.php?page=content-updater-settings">Settings</a>';
    array_push($links, $settings_link);
    return $links;
}

// Add settings page
add_action('admin_menu', 'content_updater_settings_page');

function content_updater_settings_page() {
    add_submenu_page(
        'content-updater', // Parent slug
        'Content Updater Settings', // Page title
        'Settings', // Menu title
        'manage_options', // Capability
        'content-updater-settings', // Menu slug
        'content_updater_settings_page_content' // Callback function
    );
}

// Settings page content
function content_updater_settings_page_content() {
    ?>
    <div class="wrap">
        <h2>Content Updater Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('content_updater_settings_group'); ?>
            <?php do_settings_sections('content_updater_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Batch Size</th>
                    <td>
                        <input type="number" name="content_updater_batch_size" value="<?php echo esc_attr(get_option('content_updater_batch_size')); ?>" min="1" step="1" required>
                        <p class="description">Number of items to process per batch.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Get batch size
function content_updater_get_batch_size() {
    return get_option('content_updater_batch_size', 10); // Default batch size is 10
}

function content_updater_page(){
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    // HTML for the page content, file upload form
    echo '<div class="wrap">';
    echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';
    echo '<form action="" method="post" enctype="multipart/form-data">';
    echo '<input type="hidden" name="content_updater_nonce" value="' . wp_create_nonce('content_updater') . '">';
    echo '<input type="file" name="csv_file" />';
    echo '<input type="submit" value="Upload CSV" name="submit">';
    echo '<p>Upload a CSV file in the format: post_url, post_title, meta_title, meta_description</p>';
    echo '</form>';
    echo '</div>';

    // Handle file upload
    if (isset($_FILES['csv_file'])) {
        // Verify nonce
        if (!isset($_POST['content_updater_nonce']) || !wp_verify_nonce($_POST['content_updater_nonce'], 'content_updater')) {
            wp_die('Invalid nonce. Please try again.');
        }

        content_updater_handle_file_upload($_FILES['csv_file']);
    }
}


function content_updater_handle_file_upload($file){
    // Check for errors
    if ($file['error']) {
        wp_die('File upload error.');
    }

    // Limit file types and validate the uploaded file
    $allowed_types = array('text/csv');
    if (!in_array($file['type'], $allowed_types)) {
        wp_die('Invalid file type. Please upload a valid CSV file.');
    }

    $csv_path = $file['tmp_name'];
    $file_handle = fopen($csv_path, 'r');

    // Skip the header line if your CSV has a header
    fgetcsv($file_handle);

    $batch_size = content_updater_get_batch_size(); // Use the function to get batch size
    $batch_data = array();

    while (($data = fgetcsv($file_handle)) !== false) {
        $url              = esc_url_raw($data[0]);
        $post_id          = url_to_postid($url); // Convert URL to post ID
        if(!$post_id) {
            error_log('No post ID found for URL: ' . $url);
            continue; // Skip if no post ID found for the URL
        }
        $post_title       = sanitize_text_field($data[1]);
        $meta_title       = sanitize_text_field($data[2]);
        $meta_description = sanitize_textarea_field($data[3]);

        // Add data to the batch
        $batch_data[] = array(
            'post_id' => $post_id,
            'post_title' => $post_title,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description
        );

        // If batch size is reached, process the batch
        if (count($batch_data) === $batch_size) {
            content_updater_process_batch($batch_data);
            $batch_data = array(); // Reset batch data
        }
    }

    fclose($file_handle);

    // Process any remaining items
    if (!empty($batch_data)) {
        content_updater_process_batch($batch_data);
    }
}

function content_updater_process_batch($batch_data) {
    // Initialize an empty array to store log data for this batch
    $batch_log_data = array();

    foreach ($batch_data as $item) {
        $post_id = $item['post_id'];
        $post_title = $item['post_title'];
        $meta_title = $item['meta_title'];
        $meta_description = $item['meta_description'];

        // Update post title
        $update_post = wp_update_post(array(
            'ID'         => $post_id,
            'post_title' => $post_title
        ), true);

        // Check for errors when updating the post
        if (is_wp_error($update_post)) {
            error_log('Error updating post: ' . $update_post->get_error_message());
        } else {
            // Only update the Yoast Meta if the post update was successful
            update_post_meta($post_id, '_yoast_wpseo_title', $meta_title);
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_description);

            // Add item to the batch log data
            $batch_log_data[] = array(
                'post_id' => $post_id,
                'post_title' => $post_title,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description
            );
        }
    }

    // Display the log for this batch
    content_updater_display_log($batch_log_data);
}

function content_updater_display_log($log_data) {
    if (empty($log_data)) {
        return;
    }

    echo '<h2>Log of Items Changed</h2>';
    echo '<div id="log-container">';
    echo '<ul id="log-list">';
    foreach ($log_data as $item) {
        echo '<li>Post ID: ' . $item['post_id'] . ', Post Title: ' . $item['post_title'] . ', Meta Title: ' . $item['meta_title'] . ', Meta Description: ' . $item['meta_description'] . '</li>';
    }
    echo '</ul>';
    echo '</div>';
}