<?php
/**
 * Plugin Name: Post Type Migrator
 * Description: Migrate posts between different post types and categories
 * Version: 1.0.0
 * Author: Tiago Santos
 * Email: forbee.dev@gmail.com
 * 
 * Changelog:
 * 1.0.0: Initial release
 */

if (!defined('ABSPATH')) exit;

class PostTypeMigrator {
    private $nonce_action = 'post_type_migrator_nonce';
    private $page_hook;

    public function __construct() {
        if (!function_exists('is_plugin_active')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_get_posts_for_migration', array($this, 'get_posts_for_migration'));
        add_action('wp_ajax_migrate_selected_posts', array($this, 'migrate_selected_posts'));
        add_action('admin_init', array($this, 'check_redirection_plugin'));
        
        // Increase max execution time for AJAX requests
        add_filter('http_request_timeout', array($this, 'increase_ajax_timeout'));
    }

    public function increase_ajax_timeout() {
        return 300; // 5 minutes
    }

    public function add_admin_menu() {
        $this->page_hook = add_management_page(
            __('Post Type Migrator', 'post-migrator'),
            __('Post Type Migrator', 'post-migrator'),
            'manage_options',
            'post-type-migrator',
            array($this, 'render_admin_page')
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="post-migrator-container">
                <div class="source-selection">
                    <h2><?php _e('Source', 'post-migrator'); ?></h2>
                    <select id="source-type">
                        <option value=""><?php _e('Select Source Type', 'post-migrator'); ?></option>
                        <option value="post-type"><?php _e('Post Type', 'post-migrator'); ?></option>
                        <option value="taxonomy"><?php _e('Taxonomy', 'post-migrator'); ?></option>
                    </select>

                    <div id="source-post-type-container" style="display:none;">
                        <select id="source-post-type">
                            <option value=""><?php _e('Select Post Type', 'post-migrator'); ?></option>
                            <?php
                            $post_types = get_post_types(array('public' => true), 'objects');
                            foreach ($post_types as $post_type) {
                                echo '<option value="' . esc_attr($post_type->name) . '">' . 
                                     esc_html($post_type->labels->singular_name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div id="source-taxonomy-container" style="display:none;">
                        <select id="source-taxonomy">
                            <option value=""><?php _e('Select Taxonomy', 'post-migrator'); ?></option>
                            <?php
                            $taxonomies = get_taxonomies(array('public' => true), 'objects');
                            foreach ($taxonomies as $taxonomy) {
                                echo '<option value="' . esc_attr($taxonomy->name) . '">' . 
                                     esc_html($taxonomy->labels->singular_name) . '</option>';
                            }
                            ?>
                        </select>
                        <div id="terms-container"></div>
                    </div>

                    <div id="category-filter-container" style="display:none;">
                        <select id="category-filter" multiple>
                            <?php
                            $categories = get_categories(array('hide_empty' => false));
                            foreach ($categories as $category) {
                                echo '<option value="' . esc_attr($category->term_id) . '">' . 
                                     esc_html($category->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="destination-selection">
                    <h2><?php _e('Destination', 'post-migrator'); ?></h2>
                    <select id="destination-post-type">
                        <option value=""><?php _e('Select Destination Post Type', 'post-migrator'); ?></option>
                        <?php
                        foreach ($post_types as $post_type) {
                            echo '<option value="' . esc_attr($post_type->name) . '">' . 
                                 esc_html($post_type->labels->singular_name) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div id="posts-list"></div>

                <div class="migration-controls">
                    <div class="migration-options">
                        <label class="delete-original-option">
                            <input type="checkbox" id="delete-original" name="delete-original">
                            <?php _e('Delete original posts after migration', 'post-migrator'); ?>
                        </label>
                        <p class="description"><?php _e('Warning: This action cannot be undone.', 'post-migrator'); ?></p>
                    </div>
                    <button id="migrate-button" class="button button-primary" disabled>
                        <?php _e('Migrate Selected Posts', 'post-migrator'); ?>
                    </button>
                </div>

                <div id="migration-progress"></div>
            </div>
        </div>
        <?php
    }

    public function enqueue_scripts($hook) {
        if ($hook !== $this->page_hook) return;

        wp_enqueue_style('post-migrator-styles', plugins_url('css/styles.css', __FILE__));
        wp_enqueue_script('post-migrator-script', plugins_url('js/script.js', __FILE__), array('jquery'), '1.0.6', true);
        
        wp_localize_script('post-migrator-script', 'postMigratorData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'loadNonce' => wp_create_nonce('load_posts_nonce'),
            'migrateNonce' => wp_create_nonce('migrate_posts_nonce'),
            'strings' => array(
                'error' => __('Error occurred during migration', 'post-migrator'),
                'success' => __('Migration completed successfully', 'post-migrator')
            )
        ));
    }

    public function migrate_selected_posts() {
        try {
            set_time_limit(600);
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', '600');
            ini_set('max_input_time', '600');

            if (!check_ajax_referer('migrate_posts_nonce', 'security', false)) {
                wp_send_json_error(array(
                    'message' => 'Security check failed',
                    'code' => 'invalid_nonce'
                ));
                return;
            }

            $post_ids = isset($_POST['postIds']) ? array_map('intval', (array)$_POST['postIds']) : array();
            $destination_type = isset($_POST['destinationType']) ? sanitize_text_field($_POST['destinationType']) : '';
            $batch_number = isset($_POST['batchNumber']) ? intval($_POST['batchNumber']) : 0;
            $delete_original = isset($_POST['deleteOriginal']) ? filter_var($_POST['deleteOriginal'], FILTER_VALIDATE_BOOLEAN) : false;
            $total_processed = isset($_POST['totalProcessed']) ? intval($_POST['totalProcessed']) : 0;
            $total_success = isset($_POST['totalSuccess']) ? intval($_POST['totalSuccess']) : 0;
            $total_failed = isset($_POST['totalFailed']) ? intval($_POST['totalFailed']) : 0;
            $total_skipped = isset($_POST['totalSkipped']) ? intval($_POST['totalSkipped']) : 0;

            if (empty($post_ids) || empty($destination_type)) {
                throw new Exception('Required parameters are missing');
            }

            $batch_size = 10; // Reduced batch size
            $batch_start = $batch_number * $batch_size;
            $current_batch = array_slice($post_ids, $batch_start, $batch_size);
            
            $results = array();
            $successful = 0;
            $failed = 0;
            $skipped = 0;

            foreach ($current_batch as $post_id) {
                try {
                    global $wpdb;
                    
                    $wpdb->query('START TRANSACTION');

                    $post = get_post($post_id);
                    if (!$post) {
                        throw new Exception("Post not found");
                    }

                    // More strict check for existing posts
                    $existing_post = $wpdb->get_var($wpdb->prepare(
                        "SELECT ID FROM {$wpdb->posts} 
                        WHERE post_title = %s 
                        AND post_type = %s 
                        AND ID != %d",
                        $post->post_title,
                        $destination_type,
                        $post_id
                    ));

                    if ($existing_post) {
                        if ($delete_original) {
                            wp_delete_post($post_id, true);
                            $skipped++;
                            $results[] = array(
                                'id' => $post_id,
                                'success' => true,
                                'message' => sprintf('Post "%s" already existed in destination post type - Original post deleted', $post->post_title)
                            );
                        } else {
                            $skipped++;
                            $results[] = array(
                                'id' => $post_id,
                                'success' => false,
                                'message' => sprintf('Post "%s" already exists in destination post type', $post->post_title)
                            );
                        }
                        $wpdb->query('COMMIT');
                        continue;
                    }

                    // If we get here, no existing post was found, so proceed with migration
                    $new_post_data = array(
                        'post_title' => urldecode($post->post_title),
                        'post_content' => urldecode($post->post_content),
                        'post_status' => $post->post_status,
                        'post_type' => $destination_type,
                        'post_author' => $post->post_author,
                        'post_date' => $post->post_date,
                        'post_date_gmt' => $post->post_date_gmt,
                        'post_modified' => $post->post_modified,
                        'post_modified_gmt' => $post->post_modified_gmt,
                        'comment_status' => $post->comment_status,
                        'ping_status' => $post->ping_status,
                        'post_parent' => $post->post_parent,
                        'menu_order' => $post->menu_order,
                        'post_excerpt' => urldecode($post->post_excerpt),
                        'post_name' => $post->post_name,
                    );

                    $new_post_id = wp_insert_post($new_post_data, true);

                    if (is_wp_error($new_post_id)) {
                        throw new Exception($new_post_id->get_error_message());
                    }

                    // Copy taxonomies
                    $taxonomies = get_object_taxonomies($post->post_type);
                    foreach ($taxonomies as $taxonomy) {
                        $terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'ids'));
                        if (!is_wp_error($terms)) {
                            wp_set_object_terms($new_post_id, $terms, $taxonomy);
                        }
                    }

                    // Copy meta
                    $post_meta = get_post_meta($post_id);
                    foreach ($post_meta as $meta_key => $meta_values) {
                        foreach ($meta_values as $meta_value) {
                            add_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value));
                        }
                    }

                    if ($new_post_id) {
                        // Get URLs before deleting the original post
                        $source_url = str_replace(home_url(), '', get_permalink($post_id));
                        $target_url = str_replace(home_url(), '', get_permalink($new_post_id));
                        
                        // Create redirect first
                        $redirect_result = $this->create_redirect($source_url, $target_url);
                        
                        // Create detailed redirect status
                        $redirect_status = array(
                            'success' => !is_wp_error($redirect_result) && $redirect_result !== false,
                            'source' => $source_url,
                            'target' => $target_url,
                            'message' => is_wp_error($redirect_result) 
                                ? $redirect_result->get_error_message() 
                                : 'Redirect created successfully',
                            'id' => !is_wp_error($redirect_result) && isset($redirect_result->id) ? $redirect_result->id : null
                        );

                        // Now delete the original post if required
                        if ($delete_original) {
                            wp_delete_post($post_id, true);
                        }

                        $results[] = array(
                            'id' => $post_id,
                            'new_id' => $new_post_id,
                            'success' => true,
                            'message' => $delete_original ? 'Successfully migrated and deleted original' : 'Successfully migrated',
                            'redirect' => $redirect_status
                        );
                    }

                    $wpdb->query('COMMIT');
                    $successful++;
                    
                } catch (Exception $e) {
                    $wpdb->query('ROLLBACK');
                    error_log('Migration error for post ' . $post_id . ': ' . $e->getMessage());
                    $failed++;
                    $results[] = array(
                        'id' => $post_id,
                        'success' => false,
                        'message' => $e->getMessage()
                    );
                }

                if (ob_get_level() > 0) {
                    ob_flush();
                    flush();
                }
            }

            $total_processed += count($current_batch);
            $total_success += $successful;
            $total_failed += $failed;
            $total_skipped += $skipped;

            wp_send_json_success(array(
                'results' => $results,
                'batch' => array(
                    'current' => $batch_number + 1,
                    'total' => ceil(count($post_ids) / $batch_size),
                    'processed' => $total_processed,
                    'totalPosts' => count($post_ids)
                ),
                'summary' => array(
                    'success' => $total_success,
                    'failed' => $total_failed,
                    'skipped' => array(
                        'total' => $total_skipped,
                        'deleted' => $delete_original ? $total_skipped : 0,
                        'preserved' => $delete_original ? 0 : $total_skipped
                    ),
                    'total' => count($post_ids),
                    'deleteOriginal' => $delete_original,
                    'redirects' => array_filter($results, function($item) {
                        return isset($item['redirect']) && $item['redirect']['success'];
                    })
                )
            ));

        } catch (Exception $e) {
            error_log('Migration error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => $e->getMessage(),
                'code' => 'migration_error'
            ));
        }
    }

    public function get_posts_for_migration() {
        try {
            if (!check_ajax_referer('load_posts_nonce', 'security', false)) {
                wp_send_json_error(array(
                    'message' => 'Security check failed',
                    'code' => 'invalid_nonce'
                ));
                return;
            }

            $source_type = isset($_POST['sourceType']) ? sanitize_text_field($_POST['sourceType']) : '';
            $source_value = isset($_POST['sourceValue']) ? sanitize_text_field($_POST['sourceValue']) : '';
            $term_ids = isset($_POST['termIds']) ? array_map('intval', (array)$_POST['termIds']) : array();
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $posts_per_page = 100;

            // Base query args
            $args = array(
                'posts_per_page' => $posts_per_page,
                'paged' => $page,
                'post_status' => array('publish', 'draft', 'private', 'pending'),
                'orderby' => 'ID',
                'order' => 'ASC',
                'suppress_filters' => false,
            );

            // Set post type and taxonomy query
            if ($source_type === 'post-type') {
                $args['post_type'] = $source_value;
                
                if (!empty($term_ids)) {
                    $args['tax_query'] = array(
                        'relation' => 'AND',
                        array(
                            'taxonomy' => 'category',
                            'field' => 'term_id',
                            'terms' => $term_ids,
                            'operator' => 'IN',
                            'include_children' => false
                        )
                    );
                }
            } else if ($source_type === 'taxonomy') {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => $source_value,
                        'field' => 'term_id',
                        'terms' => $term_ids,
                        'operator' => 'IN',
                        'include_children' => false
                    )
                );
            }

            // Get total posts first
            $count_query = new WP_Query($args);
            $total_posts = $count_query->found_posts;
            $total_pages = ceil($total_posts / $posts_per_page);

            // Get paginated posts
            $query = new WP_Query($args);
            $posts = $query->posts;

            $formatted_posts = array();
            foreach ($posts as $post) {
                $categories = wp_get_post_categories($post->ID, array('fields' => 'names'));
                $category_list = !empty($categories) ? implode(', ', $categories) : '';

                $formatted_posts[] = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'type' => $post->post_type,
                    'date' => get_the_date('Y-m-d', $post),
                    'status' => $post->post_status,
                    'categories' => $category_list
                );
            }

            wp_send_json_success(array(
                'posts' => $formatted_posts,
                'pagination' => array(
                    'current_page' => $page,
                    'total_pages' => $total_pages,
                    'total_posts' => $total_posts,
                    'posts_per_page' => $posts_per_page
                )
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage(),
                'code' => 'get_posts_error'
            ));
        }
    }

    /**
     * Check if Redirection plugin is active
     */
    public function check_redirection_plugin() {
        if (!class_exists('Red_Item') || !is_plugin_active('redirection/redirection.php')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p>' . __('Post Type Migrator: The Redirection plugin is not active. 301 redirects will not be created for migrated posts.', 'post-migrator') . '</p>';
                echo '</div>';
            });
            return false;
        }
        return true;
    }

    /**
     * Create 301 redirect using Redirection plugin
     * 
     * @param string $source_url Original post URL
     * @param string $target_url New post URL
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    private function create_redirect($source_url, $target_url) {
        if (!class_exists('Red_Item')) {
            return new WP_Error('redirection_missing', 'Redirection plugin class not found');
        }

        try {
            $details = array(
                'url'         => $source_url,
                'action_data' => array('url' => $target_url),
                'action_type' => 'url',
                'action_code' => 301,
                'group_id'    => 1,
                'match_type'  => 'url',
                'title'       => sprintf('Post migration: %s', basename($source_url)),
            );
            
            $result = Red_Item::create($details);
            
            if (is_wp_error($result)) {
                return $result;
            }
            
            $redirect_id = method_exists($result, 'get_id') ? $result->get_id() : null;
            if (!$redirect_id && method_exists($result, 'get_data')) {
                $data = $result->get_data();
                $redirect_id = isset($data['id']) ? $data['id'] : null;
            }
            
            return (object) array(
                'success' => true,
                'id' => $redirect_id,
                'source' => $source_url,
                'target' => $target_url
            );
        } catch (Exception $e) {
            return new WP_Error('redirect_creation_failed', $e->getMessage());
        }
    }
}

new PostTypeMigrator(); 