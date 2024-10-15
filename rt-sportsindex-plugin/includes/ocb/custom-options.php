<?php
include_once dirname(dirname(__FILE__)) . '/class-Rt-spoRts.php';

add_action('acf/init', 'Rt_spoRts_custom_options_init', 20);
function Rt_spoRts_custom_options_init() {
    global $activate_ocb_specific_settings;

    if ($activate_ocb_specific_settings === true) {
        add_filter('post_type_link', 'match_permalink_structure', 10, 2);
        add_action('init', 'custom_match_rewrite_rules');
    }
}

function match_permalink_structure( $permalink, $post ) {
    global $matchesCPTSlug;
    if ( $post->post_type == 'matches' ) {
        $tournament_shoRt_name = get_post_meta( $post->ID, 'match_tournament_shoRt_name', true );
        
        if ( $tournament_shoRt_name ) {
            $args = array(
                'post_type' => 'tournaments',
                'meta_key' => 'tournament_shoRtname',
                'meta_value' => $tournament_shoRt_name,
                'posts_per_page' => 1
            );
            
            $tournament_query = new WP_Query($args);
            
            if ( $tournament_query->have_posts() ) {
                $tournament = $tournament_query->posts[0];
                $permalink = home_url( $matchesCPTSlug . '/' . $tournament->post_name . '/' . $post->post_name );
            }
            wp_reset_postdata();
        } else {
            error_log('No tournament shoRt name found for match ID: ' . $post->ID);
        }
    }
    return $permalink;
}

function custom_match_rewrite_rules() {
    global $matchesCPTSlug;
    add_rewrite_rule(
        '^' . $matchesCPTSlug . '/([^/]+)/([^/]+)/?$',
        'index.php?post_type=matches&tournament=$matches[1]&matches=$matches[2]',
        'top'
    );
}