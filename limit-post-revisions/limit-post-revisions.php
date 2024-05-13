<?php
/**
 * Limits the number of post revisions stored for an individual post.
 * Checks the number of revisions for a given post. If the number exceeds 
 * the specified maximum,it deletes the oldest revisions to maintain the set limit.
 */
function limit_post_revisions($num, $post) {
    global $wpdb;

    // Set the maximum number of revisions allowed
    $max_revisions = 5;

    // Query to count the number of revisions for the post
    $revision_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_parent = %d", $post->ID));

    // If the number of revisions exceeds the maximum allowed
    if ($revision_count > $max_revisions) {
        // Number of revisions to delete
        $delete_count = $revision_count - $max_revisions;

        // Get the oldest revisions' IDs for the post, to be deleted
        $revision_ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_parent = %d ORDER BY post_date ASC LIMIT %d", $post->ID, $delete_count));

        foreach ($revision_ids as $revision_id) {
            wp_delete_post_revision($revision_id);
        }
    }

    return $num;
}
add_filter('wp_revisions_to_keep', 'limit_post_revisions', 10, 2);