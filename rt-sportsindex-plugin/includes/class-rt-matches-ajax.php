<?php

if ( function_exists( 'get_field' ) ) {
    $matchesCPT = get_field( 'matches_cpt', 'option' );
    $matchesCPT = $matchesCPT ? $matchesCPT : 'matches';
	$sportsApiUrl = get_field( 'sports_index_url', 'option' );
}

if (!class_exists('MatchessActionHandler')){

    class MatchesActionHandler {
		private $nonce;

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
		 * Add a new Match as draft
		 */
		private static function add_post($post_arr) {
            global $matchesCPT;
            
            $title = $post_arr['name'];
            $existing_post_id = self::get_post_by_title($title, $matchesCPT);
            
            if ($existing_post_id) {
                return wp_send_json_error(array(
                    'message' => "Post already exists",
                    'title' => $title,
                    'existing_id' => $existing_post_id->ID
                ));
            }
            
            $wp_post_arr = array(
                'post_title' => $post_arr['name'] . ' - ' . $post_arr['sub_title'],
                'post_status' => 'draft',
                'post_type' => $matchesCPT,
                'meta_input' => self::get_meta_input($post_arr)
            );

            $post_id = wp_insert_post($wp_post_arr, true);

            if (is_wp_error($post_id)) {
                error_log('Failed to create draft post: ' . $post_id->get_error_message());
                return wp_send_json_error(array(
                    'message' => "Failed to create draft post: " . $post_id->get_error_message(),
                    'title' => $title
                ));
            }

            return wp_send_json_success(array(
                'message' => "Draft post created: $title",
                'post_id' => $post_id
            ));
        }

		/**
		 * Publish a draft Tournament or create a new Tournament if it doesn't exist
		 */
        private static function publish_post($post_arr) {
            global $matchesCPT;
            $title = (isset($post_arr['name']) ? $post_arr['name'] : '') . 
                    (isset($post_arr['sub_title']) && !empty($post_arr['sub_title']) ? ' - ' . $post_arr['sub_title'] : '');
                                
            if (empty($title)) {
                return wp_send_json_error(array(
                    'message' => "Failed to publish post: Title is required",
                ));
            }

            $existing_post = self::get_post_by_title($title, $matchesCPT);

            $meta_input = self::get_meta_input($post_arr);

            if ($existing_post) {
                if ($existing_post->post_status == 'draft') {
                    $update_arr = array(
                        'ID' => $existing_post->ID,
                        'post_status' => 'publish',
                        'post_title' => $title,
                        'meta_input' => $meta_input
                    );
                    $result = wp_update_post($update_arr, true);
                    
                    if (is_wp_error($result)) {
                        return wp_send_json_error(array(
                            'message' => "Failed to publish post: " . $result->get_error_message(),
                            'title' => $title
                        ));
                    }
                    
                    return wp_send_json_success(array(
                        'message' => "Post published",
                        'title' => $title,
                        'post_id' => $result
                    ));
                } else {
                    return wp_send_json_error(array(
                        'message' => "Post already published",
                        'title' => $title,
                        'post_id' => $existing_post->ID
                    ));
                }
            } else {
                $new_post = array(
                    'post_title' => $title,
                    'post_type' => $matchesCPT,
                    'post_status' => 'publish',
                    'meta_input' => $meta_input
                );
                $post_id = wp_insert_post($new_post, true);
                
                if (is_wp_error($post_id)) {
                    return wp_send_json_error(array(
                        'message' => "Failed to create and publish post: " . $post_id->get_error_message(),
                        'title' => $title
                    ));
                }
                
                return wp_send_json_success(array(
                    'message' => "New post created and published",
                    'title' => $title,
                    'post_id' => $post_id
                ));
            }
        }

		/**
		 * Update an existing Tournament
		 */
        private static function update_post($post_arr) {
            global $matchesCPT;
            $title = (isset($post_arr['name']) ? $post_arr['name'] : '') . 
                    (isset($post_arr['sub_title']) && !empty($post_arr['sub_title']) ? ' - ' . $post_arr['sub_title'] : '');
            
            if (empty($title)) {
                return wp_send_json_error(array(
                    'message' => "Failed to update post: Title is required",
                ));
            }

            $existing_post = self::get_post_by_title($title, $matchesCPT);

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
                'post_id' => $result
            ));
        }

		/**
		 * Get a post by title and post type
		 */
        public static function get_post_by_title($title, $matchesCPT) {
            $args = array(
                'post_type'              => $matchesCPT,
                'title'                  => $title,
                'post_status'            => 'any',
                'posts_per_page'         => 1,
                'no_found_rows'          => true,
                'ignore_sticky_posts'    => true,
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,
            );

            $query = new WP_Query($args);

            if ($query->have_posts()) {
                return $query->posts[0];
            }

            return null;
        }
    }
}
