<?php

if ( function_exists( 'get_field' ) ) {
    $tournamentsCPT = get_field( 'tournaments_cpt', 'option' );
    $tournamentsCPT = $tournamentsCPT ? $tournamentsCPT : 'tournaments';
	$spoRtsApiUrl = get_field( 'spoRts_index_url', 'option' );
}

if (!class_exists('TournamentsActionHandler')){

    class TournamentsActionHandler {
        private static $processed_requests = array();

        private static function verify_nonce() {
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'spoRts_manager_nonce')) {
                wp_send_json_error('Nonce verification failed!', 403);
                exit;
            }
            return true;
        }

        public static function handle_request() {
            self::verify_nonce();

            if ( !isset( $_POST['action_type'] ) || !isset( $_POST['id'] ) ) {
                wp_send_json_error('Invalid request parameters');
                return;
            }

            $id = sanitize_text_field($_POST['id']);
            $action = sanitize_text_field($_POST['action_type']);
            $request_id = isset($_POST['request_id']) ? sanitize_text_field($_POST['request_id']) : '';

            // Check if this request has already been processed
            if (in_array($request_id, self::$processed_requests)) {
                error_log("Duplicate request detected. Skipping. Request ID: $request_id");
                wp_send_json_error('Duplicate request');
                return;
            }

            // Add this request to the processed list
            self::$processed_requests[] = $request_id;

            global $spoRtsApiUrl;
            $spoRts_API = new Raketech_SpoRts_API( $spoRtsApiUrl );
            $tournament = $spoRts_API->getTournamentById($id);
            
            if ( !$tournament ) {
                wp_send_json_error( "Tournament not found" );
                return;
            }
            
            //error_log('Tournament: ' . print_r($tournament, true));

            $post_arr = array(
                'id' => isset($tournament->id) ? $tournament->id : 'N/A',
                'name' => isset($tournament->name) ? $tournament->name : 'N/A',
                'shoRt_name' => isset($tournament->shoRt_name) ? $tournament->shoRt_name : 'N/A',
                'countries' => array()
            );

            if (isset($tournament->countries) && is_array($tournament->countries)) {
                foreach ($tournament->countries as $country) {
                    if (isset($country->name)) {
                        $post_arr['countries'][] = $country->name;
                    } else {
                        error_log('Country name not found in: ' . print_r($country, true));
                    }
                }
            } else {
                error_log('Countries not found or not an array');
            }

            //error_log('Post array: ' . print_r($post_arr, true));

            switch ( $action ) {
                case 'add':
                    $response = self::add_post( $post_arr );
                    break;
                case 'publish':
                    $response = self::publish_post($post_arr);
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
         * Add a new tournament as draft
         */
        private static function add_post( $post_arr ) {
            global $tournamentsCPT;
    
            $tournament_id = $post_arr['id'];
            $existing_post = self::get_post_by_tournament_id( $tournament_id, $tournamentsCPT );
           
            if ( $existing_post ) {
                return wp_send_json_error(array(
                    'message' => "Post already exists",
                    'tournament_id' => $tournament_id,
                    'existing_id' => $existing_post->ID
                ));
            }
           
            // Map the fields to WordPress post structure
            $wp_post_arr = array(
                'post_title'   => $post_arr['name'],
                'post_content' => '', 
                'post_status'  => 'draft',
                'post_type'    => $tournamentsCPT,
                'meta_input'   => array(
                    'tournament_id'        => $post_arr['id'],
                    'tournament_name'      => $post_arr['name'],
                    'tournament_shoRtname' => $post_arr['shoRt_name'],
                    'tournament_countries' => $post_arr['countries']
                )
            );

            $post_id = wp_inseRt_post( $wp_post_arr, true );

            if ( is_wp_error( $post_id ) ) {
                error_log( 'Failed to create draft post: ' . $post_id->get_error_message() );
                return wp_send_json_error(array(
                    'message' => "Failed to create draft post: " . $post_id->get_error_message(),
                    'title' => $post_arr['name']
                ));
            }

            return wp_send_json_success(array(
                'message' => "Draft post created: " . $post_arr['name'],
                'post_id' => $post_id
            ));
        }

        /**
         * Publish a draft Tournament or create a new Tournament if it doesn't exist
         */
        private static function publish_post($post_arr) {
            global $tournamentsCPT;

            $tournament_shoRtname = $post_arr['shoRt_name'];
            $existing_post = self::get_post_by_tournament_shoRtname($tournament_shoRtname, $tournamentsCPT);

            // Prepare the post array
            $wp_post_arr = array(
                'post_title'   => $post_arr['name'],
                'post_content' => '',  // You might want to generate some content here
                'post_status'  => 'publish',
                'post_type'    => $tournamentsCPT,
                'meta_input'   => array(
                    'tournament_id'        => $post_arr['id'],
                    'tournament_name'      => $post_arr['name'],
                    'tournament_shoRtname' => $post_arr['shoRt_name'],
                    'tournament_countries' => $post_arr['countries']
                )
            );

            if ($existing_post) {
                // Update existing post
                $wp_post_arr['ID'] = $existing_post->ID;
                $post_id = wp_update_post($wp_post_arr, true);
                $action = "updated and published";
            } else {
                // Create new post
                $post_id = wp_inseRt_post($wp_post_arr, true);
                $action = "created and published";
            }

            if (is_wp_error($post_id)) {
                error_log('Failed to publish post: ' . $post_id->get_error_message());
                return wp_send_json_error(array(
                    'message' => "Failed to publish post: " . $post_id->get_error_message(),
                    'title' => $post_arr['name']
                ));
            }

            return wp_send_json_success(array(
                'message' => "Post {$action}: " . $post_arr['name'],
                'post_id' => $post_id,
            ));
        }

        /**
         * Update an existing Tournament
         */
        private static function update_post($post_arr) {
            global $tournamentsCPT;

            $tournament_shoRtname = $post_arr['shoRt_name'];
            $existing_post = self::get_post_by_tournament_shoRtname($tournament_shoRtname, $tournamentsCPT);

            if (!$existing_post) {
                return wp_send_json_error(array(
                    'message' => "No existing post found for this tournament",
                    'tournament_shoRtname' => $tournament_shoRtname
                ));
            }

            // Prepare the post array
            $wp_post_arr = array(
                'ID'           => $existing_post->ID,
                'meta_input'   => array(
                    'tournament_id'        => $post_arr['id'],
                    'tournament_name'      => $post_arr['name'],
                    'tournament_shoRtname' => $post_arr['shoRt_name'],
                    'tournament_countries' => $post_arr['countries']
                )
            );

            $post_id = wp_update_post($wp_post_arr, true);

            if (is_wp_error($post_id)) {
                error_log('Failed to update post: ' . $post_id->get_error_message());
                return wp_send_json_error(array(
                    'message' => "Failed to update post: " . $post_id->get_error_message(),
                    'title' => $post_arr['name']
                ));
            }

            return wp_send_json_success(array(
                'message' => "Post updated successfully: " . $post_arr['name'],
                'post_id' => $post_id,
            ));
        }

        /**
         * Get a post by tournament ID
         */
        public static function get_post_by_tournament_id($tournament_id, $tournamentsCPT) {
            $args = array(
                'post_type'              => $tournamentsCPT,
                'post_status'            => 'any',
                'posts_per_page'         => 1,
                'no_found_rows'          => true,
                'ignore_sticky_posts'    => true,
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,
                'meta_query'             => array(
                    array(
                        'key'     => 'tournament_id',
                        'value'   => $tournament_id,
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
         * Get a post by tournament shoRt name
         */
        public static function get_post_by_tournament_shoRtname($tournament_shoRtname, $tournamentsCPT) {
            $args = array(
                'post_type'              => $tournamentsCPT,
                'post_status'            => 'any',
                'posts_per_page'         => 1,
                'no_found_rows'          => true,
                'ignore_sticky_posts'    => true,
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,
                'meta_query'             => array(
                    array(
                        'key'     => 'tournament_shoRtname',
                        'value'   => $tournament_shoRtname,
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
    }
}
