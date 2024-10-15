<?php

if ( function_exists( 'get_field' ) ) {
    $matchesCPT = get_field( 'matches_cpt', 'option' );
    $matchesCPT = $matchesCPT ? $matchesCPT : 'matches';
    $sportsApiUrl = get_field( 'sports_index_url', 'option' );
}

if (!class_exists('MatchesActionHandler')){

    class MatchesActionHandler {
        private static $processed_requests = array();

        // TODO:Constructor to set up the cron job (waiting on team requirements)
/*         public function __construct() {
            // Register the cron event
            if (!wp_next_scheduled('update_matches_cron_hook')) {
                wp_schedule_event(time(), 'hourly', 'update_matches_cron_hook');
            }

            // Hook the function to the cron event
            add_action('update_matches_cron_hook', array($this, 'update_matches_data'));
        } */

        private static function verify_nonce() {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sports_manager_nonce')) {
                wp_send_json_error('Nonce verification failed!', 403);
                exit;
            }
            return true;
        }

        public static function handle_matches_request() {
            self::verify_nonce();

            if ( !isset( $_POST['action_type'] ) || !isset( $_POST['key'] ) ) {
                wp_send_json_error('Invalid request parameters');
                return;
            }

            $key = sanitize_text_field($_POST['key']);
            $action = sanitize_text_field($_POST['action_type']);
            $request_id = isset($_POST['request_id']) ? sanitize_text_field($_POST['request_id']) : '';

            // Check if this request has already been processed
            if (in_array($request_id, self::$processed_requests)) {
                error_log("Duplicate match request detected. Skipping. Request ID: $request_id");
                wp_send_json_error('Duplicate request');
                return;
            }

            // Add this request to the processed list
            self::$processed_requests[] = $request_id;

            global $sportsApiUrl;
            $sports_API = new Rt_Sports_API( $sportsApiUrl );
            $match = $sports_API->getMatchesByKey($key);
            
            if ( !$match ) {
                wp_send_json_error( "Matche not found" );
                return;
            }
            
            //error_log('Match: ' . print_r($match, true));

            $post_arr = array(
                'key' => isset($match->key) ? $match->key : 'N/A',
                'name' => isset($match->name) ? $match->name : 'N/A',
                'short_name' => isset($match->short_name) ? $match->short_name : 'N/A',
                'sub_title' => isset($match->sub_title) ? $match->sub_title : 'N/A',
                'start_at' => (isset($match->start_at) && is_numeric($match->start_at)) 
                    ? date('M d, Y', (int)$match->start_at) 
                    : 'N/A',
                'match_time' => isset($match->start_at) 
                    ? date('g:i a', strtotime($match->start_at)) 
                    : 'N/A',
                'status' => isset($match->status) ? $match->status : 'N/A',
                'play_status' => isset($match->play_status) ? $match->play_status : 'N/A',
                'metric_group' => isset($match->metric_group) ? $match->metric_group : 'N/A',
                'sport' => isset($match->sport) ? $match->sport : 'N/A',
                'winner' => isset($match->winner) ? $match->winner : 'N/A',
                'gender' => isset($match->gender) ? $match->gender : 'N/A',
                'format' => isset($match->format) ? $match->format : 'N/A',
                'tournament' => array(
                    'key' => isset($match->tournament->key) ? $match->tournament->key : 'N/A',
                    'name' => isset($match->tournament->name) ? $match->tournament->name : 'N/A',
                    'short_name' => isset($match->tournament->short_name) ? $match->tournament->short_name : 'N/A'
                ),
                'teams' => array(
                    'a' => array(
                        'name' => isset($match->teams->a->name) ? $match->teams->a->name : 'N/A',
                        'code' => isset($match->teams->a->code) ? $match->teams->a->code : 'N/A'
                    ),
                    'b' => array(
                        'name' => isset($match->teams->b->name) ? $match->teams->b->name : 'N/A',
                        'code' => isset($match->teams->b->code) ? $match->teams->b->code : 'N/A'
                    )
                    ),
                'venue' => array(
                    'city' => isset($match->venue->city) ? $match->venue->city : 'N/A',
                    'name' => isset($match->venue->name) ? $match->venue->name : 'N/A',
                    'geolocation' => isset($match->venue->geolocation) ? $match->venue->geolocation : 'N/A',
                    'country' => isset($match->venue->country->name) ? $match->venue->country->name : 'N/A'
                ),
                'association' => array(
                    'name' => isset($match->association->name) ? $match->association->name : 'N/A'
                ),
                'scores' => isset($match->scores) ? $match->scores : array()
            );

            //error_log('Post array: ' . print_r($post_arr, true));

            switch ( $action ) {
                case 'add':
                    $response = self::add_post( $post_arr );
                    break;
                case 'publish':
                    $response = self::publish_post( $post_arr );
                    break;
                case 'update':
                    $response = self::update_post( $post_arr );
                    break;
                default:
                    $response = 'Invalid action';
            }

            error_log("Completed match request - Key: $key, Action: $action, Request ID: $request_id");

            echo $response;
            wp_die();
        }

        /**
         * Get match meta
         * @param mixed $post_arr
         * @return array
         */
        private static function get_meta_input($post_arr) {
            $meta_input = [
                'match_key' => $post_arr['key'] ?? '',
                'match_name' => $post_arr['name'] ?? '',
                'match_short_name' => $post_arr['short_name'] ?? '',
                'match_sub_title' => $post_arr['sub_title'] ?? '',
                'match_start_at' => $post_arr['start_at'] ?? '',
                'match_time' => $post_arr['match_time'] ?? '',
                'match_status' => $post_arr['status'] ?? '',
                'match_play_status' => $post_arr['play_status'] ?? '',
                'match_metric_group' => $post_arr['metric_group'] ?? '',
                'match_sport' => $post_arr['sport'] ?? '',
                'match_winner' => $post_arr['winner'] ?? '',
                'match_gender' => $post_arr['gender'] ?? '',
                'match_format' => $post_arr['format'] ?? '',
                'match_tournament_key' => $post_arr['tournament']['key'] ?? '',
                'match_tournament_name' => $post_arr['tournament']['name'] ?? '',
                'match_tournament_short_name' => $post_arr['tournament']['short_name'] ?? '',
                'team_a_name' => $post_arr['teams']['a']['name'] ?? '',
                'team_a_code' => $post_arr['teams']['a']['code'] ?? '',
                'team_b_name' => $post_arr['teams']['b']['name'] ?? '',
                'team_b_code' => $post_arr['teams']['b']['code'] ?? '',
                'venue_city' => $post_arr['venue']['city'] ?? '',
                'venue_name' => $post_arr['venue']['name'] ?? '',
                'venue_geolocation' => $post_arr['venue']['geolocation'] ?? '',
                'venue_country' => $post_arr['venue']['country'] ?? '',
                'association_name' => $post_arr['association']['name'] ?? '',
            ];

            if (isset($post_arr['scores']) && is_object($post_arr['scores'])) {
                foreach ($post_arr['scores'] as $key => $score) {
                    $meta_input['score_' . $key] = $score;
                }
            }

            return $meta_input;
        }

        /**
         * Ensure tournament exists, create if it doesn't
         */
        private static function ensure_tournament_exists($tournament_data) {
            global $tournamentsCPT;
            
            // First, try to find the tournament by its short name in meta
            $existing_tournament = TournamentsActionHandler::get_post_by_tournament_shortname($tournament_data['short_name'], $tournamentsCPT);
            
            if ($existing_tournament) {
                // Update the existing tournament with any new data
                $updated_post = array(
                    'ID'         => $existing_tournament->ID,
                    'post_title' => $tournament_data['name'],
                    'meta_input' => array(
                        'tournament_id'        => $tournament_data['key'],
                        'tournament_name'      => $tournament_data['name'],
                        'tournament_shortname' => $tournament_data['short_name']
                    )
                );
                
                wp_update_post($updated_post);
                
                return $existing_tournament->ID;
            }
            
            // If no existing tournament, create a new one
            $new_tournament = array(
                'post_title'  => $tournament_data['name'],
                'post_name'   => sanitize_title($tournament_data['name']),
                'post_type'   => $tournamentsCPT,
                'post_status' => 'publish',
                'meta_input'  => array(
                    'tournament_id'        => $tournament_data['key'],
                    'tournament_name'      => $tournament_data['name'],
                    'tournament_shortname' => $tournament_data['short_name']
                )
            );
            
            $tournament_id = wp_insert_post($new_tournament);
            
            if (is_wp_error($tournament_id)) {
                error_log('Failed to create tournament: ' . $tournament_id->get_error_message());
                return false;
            }
            
            return $tournament_id;
        }

        /**
         * Add a new Match as draft
         */
        private static function add_post($match_data) {
            global $matchesCPT;
            
            // Ensure tournament exists
            if (!self::ensure_tournament_exists($match_data['tournament'])) {
                return wp_send_json_error(array(
                    'message' => "Failed to create associated tournament",
                    'match_data' => $match_data
                ));
            }

            $title = $match_data['name'];
            $existing_post_id = self::get_post_by_match_key($match_data['key'], $matchesCPT);
            
            if ($existing_post_id) {
                return wp_send_json_error(array(
                    'message' => "Post already exists",
                    'title' => $title,
                    'existing_id' => $existing_post_id->ID
                ));
            }
            
            $wp_post_arr = array(
                'post_title' => $match_data['name'] . ' - ' . $match_data['sub_title'],
                'post_status' => 'draft',
                'post_type' => $matchesCPT,
                'meta_input' => self::get_meta_input($match_data)
            );

            $post_id = wp_insert_post($wp_post_arr, true);

            if (is_wp_error($post_id)) {
                return wp_send_json_error(array(
                    'message' => "Failed to create draft post: " . $post_id->get_error_message(),
                    'match_data' => $match_data,
                ));
            }

            return wp_send_json_success(array(
                'message' => "Draft post created successfully",
                'post_id' => $post_id,
                'actions' => array("Created draft post with ID: $post_id")
            ));
        }

        /**
         * Publish a draft Tournament or create a new Tournament if it doesn't exist
         */
        private static function publish_post($post_arr) {
            global $matchesCPT;
            
            $response = ['actions' => []];

            // Ensure tournament exists
            $tournament_result = self::ensure_tournament_exists($post_arr['tournament']);
            if (!$tournament_result) {
                return wp_send_json_error([
                    'message' => "Failed to create associated tournament",
                    'title' => $post_arr['name']
                ]);
            }
            $response['actions'][] = "Tournament created or updated successfully";

            $title = (isset($post_arr['name']) ? $post_arr['name'] : '') . 
                    (isset($post_arr['sub_title']) && !empty($post_arr['sub_title']) ? ' - ' . $post_arr['sub_title'] : '');
                
            if (empty($title)) {
                return wp_send_json_error([
                    'message' => "Failed to publish post: Title is required",
                ]);
            }

            $existing_post = self::get_post_by_match_key($post_arr['key'], $matchesCPT);
            $meta_input = self::get_meta_input($post_arr);

            if ($existing_post) {
                if ($existing_post->post_status == 'draft') {
                    $update_arr = [
                        'ID' => $existing_post->ID,
                        'post_status' => 'publish',
                        'post_title' => $title,
                        'meta_input' => $meta_input
                    ];
                    $result = wp_update_post($update_arr, true);
                    
                    if (is_wp_error($result)) {
                        return wp_send_json_error([
                            'message' => "Failed to publish post: " . $result->get_error_message(),
                            'title' => $title
                        ]);
                    }
                    
                    $response['actions'][] = "Existing draft post published";
                    return wp_send_json_success([
                        'message' => "Post published successfully",
                        'title' => $title,
                        'post_id' => $result,
                        'actions' => $response['actions']
                    ]);
                } else {
                    $response['actions'][] = "Post already published";
                    return wp_send_json_error([
                        'message' => "Post already published",
                        'title' => $title,
                        'post_id' => $existing_post->ID,
                        'actions' => $response['actions']
                    ]);
                }
            } else {
                $new_post = [
                    'post_title' => $title,
                    'post_type' => $matchesCPT,
                    'post_status' => 'publish',
                    'meta_input' => $meta_input
                ];
                $post_id = wp_insert_post($new_post, true);
                
                if (is_wp_error($post_id)) {
                    return wp_send_json_error([
                        'message' => "Failed to create and publish post: " . $post_id->get_error_message(),
                        'title' => $title
                    ]);
                }
                
                $response['actions'][] = "New post created and published";
                
                if ($tournament_result) {
                    $connection_result = self::connect_match_to_tournament($post_id, $tournament_result);
                    $response['actions'][] = $connection_result ? "Match connected to tournament" : "Failed to connect match to tournament";
                }
                
                return wp_send_json_success([
                    'message' => "New post created and published successfully",
                    'title' => $title,
                    'post_id' => $post_id,
                    'actions' => $response['actions']
                ]);
            }
        }

        /**
         * Update an existing Tournament
         */
        private static function update_post($post_arr) {
            global $matchesCPT;

            // Ensure tournament exists
            if (!self::ensure_tournament_exists($post_arr['tournament'])) {
                return wp_send_json_error(array(
                    'message' => "Failed to create associated tournament",
                    'title' => $post_arr['name']
                ));
            }

            $title = (isset($post_arr['name']) ? $post_arr['name'] : '') . 
                    (isset($post_arr['sub_title']) && !empty($post_arr['sub_title']) ? ' - ' . $post_arr['sub_title'] : '');
            
            if (empty($title)) {
                return wp_send_json_error(array(
                    'message' => "Failed to update post: Title is required",
                ));
            }

            $existing_post = self::get_post_by_match_key($post_arr['key'], $matchesCPT);

            if (!$existing_post) {
                return wp_send_json_error(array(
                    'message' => "No post found to update",
                    'title' => $title
                ));
            }

            $update_arr = array(
                'ID' => $existing_post->ID,
                'post_title' => $title,
                'post_type' => $matchesCPT,
                'post_status' => $existing_post->post_status
            );

            $result = wp_update_post($update_arr, true);
            
            if (is_wp_error($result)) {
                return wp_send_json_error(array(
                    'message' => "Failed to update post: " . $result->get_error_message(),
                    'title' => $title
                ));
            }

            $meta_input = self::get_meta_input($post_arr);
            foreach ($meta_input as $key => $value) {
                update_post_meta($result, $key, $value);
            }

            return wp_send_json_success(array(
                'message' => "Post updated successfully",
                'title' => $title,
                'post_id' => $result,
                'actions' => array("Updated post with Title: $title")
            ));
        }

        /**
         * Get a post by match key
         */
        public static function get_post_by_match_key($match_key, $matchesCPT) {
            $args = array(
                'post_type'              => $matchesCPT,
                'post_status'            => 'any',
                'posts_per_page'         => 1,
                'no_found_rows'          => true,
                'ignore_sticky_posts'    => true,
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,
                'meta_query'             => array(
                    array(
                        'key'     => 'match_key',
                        'value'   => $match_key,
                        'compare' => '='
                    )
                )
            );

            $query = new WP_Query($args);

            if ($query->have_posts()) {
                return $query->posts[0];
            }

            return null;
        }

        /**
         * Handle the fetch upcoming matches request
         */
        public static function handle_fetch_upcoming_matches() {
            self::verify_nonce();

            $dateStart = $_POST['dateStart'];
            $dateEnd = $_POST['dateEnd'];

            if (!$dateStart || !$dateEnd) {
                wp_send_json_error('Invalid date provided');
            }

            global $sportsApiUrl;
            $sports_API = new rt_Sports_API($sportsApiUrl);
            $upcoming_matches = $sports_API->fetchUpcomingMatchesFromAPI($dateStart, $dateEnd);

            wp_send_json_success($upcoming_matches);
        }

        public static function handle_fetch_tournaments() {
            self::verify_nonce();

            global $sportsApiUrl;
            $sports_API = new rt_Sports_API($sportsApiUrl);
            $tournaments = $sports_API->fetchTournamentsFromAPI();

            wp_send_json_success($tournaments);
        }

        public static function handle_fetch_matches() {
            self::verify_nonce();

            global $sportsApiUrl;
            $sports_API = new rt_Sports_API($sportsApiUrl);
            $matches = $sports_API->fetchMatchesFromAPI();

            wp_send_json_success($matches);
        }

        private static function connect_match_to_tournament($match_id, $tournament_id) {
            // Return true if successful, false otherwise
            return true; // Placeholder
        }

        public static function update_matches_data($manual_call = false) {
            global $matchesCPT;

            // Query for matches that are published or in draft status
            $args = array(
                'post_type' => $matchesCPT,
                'post_status' => array('publish', 'draft'),
                'posts_per_page' => -1,
            );

            $matches = get_posts($args);
            $updated_matches_ids = array();
            
            foreach ($matches as $match) {
                // Fetch updated data from the API for all matches
                $key = get_post_meta($match->ID, 'match_key', true);
                global $sportsApiUrl;
                $sports_API = new rt_Sports_API($sportsApiUrl);
                $updated_match = $sports_API->getMatchesByKey($key);

                if ($updated_match) {
                    $changes = array();
                    // Prepare the meta input directly from the API response
                    $meta_input = array(
                        'match_key' => $updated_match->key ?? '',
                        'match_name' => $updated_match->name ?? '',
                        'match_short_name' => $updated_match->short_name ?? '',
                        'match_sub_title' => $updated_match->sub_title ?? '',
                        'match_start_at' => isset($updated_match->start_at) ? date('M d, Y', (int)$updated_match->start_at) : '',
                        'match_time' => isset($updated_match->start_at) ? date('g:i a', strtotime($updated_match->match_time)) : '',
                        'match_status' => $updated_match->status ?? '',
                        'match_play_status' => $updated_match->play_status ?? '',
                        'match_metric_group' => $updated_match->metric_group ?? '',
                        'match_sport' => $updated_match->sport ?? '',
                        'match_winner' => $updated_match->winner ?? '',
                        'match_gender' => $updated_match->gender ?? '',
                        'match_format' => $updated_match->format ?? '',
                        'match_tournament_key' => $updated_match->tournament->key ?? '',
                        'match_tournament_name' => $updated_match->tournament->name ?? '',
                        'match_tournament_short_name' => $updated_match->tournament->short_name ?? '',
                        'team_a_name' => $updated_match->teams->a->name ?? '',
                        'team_a_code' => $updated_match->teams->a->code ?? '',
                        'team_b_name' => $updated_match->teams->b->name ?? '',
                        'team_b_code' => $updated_match->teams->b->code ?? '',
                        'venue_city' => $updated_match->venue->city ?? '',
                        'venue_name' => $updated_match->venue->name ?? '',
                        'venue_geolocation' => $updated_match->venue->geolocation ?? '',
                        'venue_country' => $updated_match->venue->country->name ?? '',
                        'association_name' => $updated_match->association->name ?? '',
                    );

                    // Add scores if they exist
                    if (isset($updated_match->scores) && is_object($updated_match->scores)) {
                        foreach ($updated_match->scores as $key => $score) {
                            $meta_input['score_' . $key] = $score;
                        }
                    }

                    // Check for changes and update if necessary
                    foreach ($meta_input as $meta_key => $meta_value) {
                        $current_value = get_post_meta($match->ID, $meta_key, true);
                        if ($current_value !== $meta_value) {
                            update_post_meta($match->ID, $meta_key, $meta_value);
                            $changes[$meta_key] = array('from' => $current_value, 'to' => $meta_value);
                        }
                    }

                    // If there were changes, add to the updated matches array
                    if (!empty($changes)) {
                        $updated_matches_ids[] = array(
                            'id' => $match->ID,
                            'updated_data' => $meta_input,
                            'changes' => $changes
                        );
                    }
                }
            }

            // For manual calls
            if ($manual_call) {
                if (!empty($updated_matches_ids)) {
                    $updated_info = array_map(function($item) {
                        $change_details = [];
                        foreach ($item['changes'] as $field => $change) {
                            $change_details[] = "$field: {$change['from']} -> {$change['to']}";
                        }
                        $changes_string = implode(', ', $change_details);
                        return "ID: {$item['id']} (Name: {$item['updated_data']['match_name']}) - Changes: $changes_string";
                    }, $updated_matches_ids);
                    
                    $updated_info_string = implode("\n", $updated_info);
                    echo "<script>
                        alert('Updated matches:\\n" . esc_js($updated_info_string) . "');
                    </script>";
                } else {
                    echo "<script>alert('No matches were updated.');</script>";
                }
            }

            return $updated_matches_ids;
        }

    }
}