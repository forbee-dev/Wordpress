<?php

if ( function_exists( 'get_field' ) ) {
    $tournamentsCPT = get_field( 'tournaments_cpt', 'option' );
    $tournamentsCPT = $tournamentsCPT ? $tournamentsCPT : 'tournaments';
	$sportsApiUrl = get_field( 'sports_index_url', 'option' );
}

if (!class_exists('TournamentsActionHandler')){

    class TournamentsActionHandler {
		private $nonce;

		private static function verify_nonce() {
			if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sports_manager_nonce')) {
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

            global $sportsApiUrl;
            $sports_API = new Raketech_Sports_API( $sportsApiUrl );
            $tournament = $sports_API->getTournamentById($id);
            
			if ( !$tournament ) {
                wp_send_json_error( "Tournament not found" );
				return;
			}
            
			//error_log('Tournament: ' . print_r($tournament, true));

			$post_arr = array(
				'id' => isset($tournament->id) ? $tournament->id : 'N/A',
				'name' => isset($tournament->name) ? $tournament->name : 'N/A',
				'short_name' => isset($tournament->short_name) ? $tournament->short_name : 'N/A',
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
		 * Add a new tournament as draft
		 */
		private static function add_post( $post_arr ) {
			global $tournamentsCPT;
    
			error_log('Received post array: ' . print_r($post_arr, true));

			$title = $post_arr['name'];
			$existing_post_id = self::get_post_by_title( $title, $tournamentsCPT );
			
			if ( $existing_post_id ) {
				return wp_send_json_error(array(
					'message' => "Post already exists",
					'title' => $title,
					'existing_id' => $existing_post_id->ID
				));
			}
			
			// Map the fields to WordPress post structure
			$wp_post_arr = array(
				'post_title'   => $post_arr['name'],
				'post_content' => '',  // You might want to generate some content here
				'post_status'  => 'draft',
				'post_type'    => $tournamentsCPT,
				'meta_input'   => array(
					'tournament_id'        => $post_arr['id'],
					'tournament_name' => $post_arr['name'],
					'tournament_shortname' => $post_arr['short_name'],
					'tournament_countries' => $post_arr['countries']
				)
			);

			$post_id = wp_insert_post( $wp_post_arr, true );

			if ( is_wp_error( $post_id ) ) {
				error_log( 'Failed to create draft post: ' . $post_id->get_error_message() );
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
			global $tournamentsCPT;
			$title = isset($post_arr['name']) ? $post_arr['name'] : '';
			
			if (empty($title)) {
				return wp_send_json_error(array(
					'message' => "Failed to publish post: Title is required",
				));
			}

			$existing_post = self::get_post_by_title($title, $tournamentsCPT);

			if ($existing_post) {
				if ($existing_post->post_status == 'draft') {
					$update_arr = array(
						'ID' => $existing_post->ID,
						'post_status' => 'publish',
						'post_title' => $title,
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
					'post_content' => $title, // Using title as content if no specific content is provided
					'post_type' => $tournamentsCPT,
					'post_status' => 'publish'
				);
				$post_id = wp_insert_post($new_post, true);
				
				if (is_wp_error($post_id)) {
					return wp_send_json_error(array(
						'message' => "Failed to create and publish post: " . $post_id->get_error_message(),
						'title' => $title
					));
				}
				
				// Add any additional meta data if needed
				if (isset($post_arr['id'])) {
					update_post_meta($post_id, 'tournament_id', $post_arr['id']);
				}
				if (isset($post_arr['name'])) {
					update_post_meta($post_id, 'tournament_name', $post_arr['name']);
				}
				if (isset($post_arr['short_name'])) {
					update_post_meta($post_id, 'tournament_shortname', $post_arr['short_name']);
				}
				if (isset($post_arr['countries'])) {
					update_post_meta($post_id, 'tournament_countries', $post_arr['countries']);
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
			global $tournamentsCPT;
			$title = isset($post_arr['name']) ? $post_arr['name'] : '';
			
			if (empty($title)) {
				return wp_send_json_error(array(
					'message' => "Failed to update post: Title is required",
				));
			}

			$existing_post = self::get_post_by_title($title, $tournamentsCPT);

			if (!$existing_post) {
				return wp_send_json_error(array(
					'message' => "No post found to update",
					'title' => $title
				));
			}

			$update_arr = array(
				'ID' => $existing_post->ID,
				'post_title' => $title,
				'post_type' => $tournamentsCPT,
				// Maintain the current post status
				'post_status' => $existing_post->post_status
			);

			$result = wp_update_post($update_arr, true);
			
			if (is_wp_error($result)) {
				return wp_send_json_error(array(
					'message' => "Failed to update post: " . $result->get_error_message(),
					'title' => $title
				));
			}

			// Update meta fields
			if (isset($post_arr['id'])) {
				update_post_meta($result, 'tournament_id', $post_arr['id']);
			}
			if (isset($post_arr['name'])) {
				update_post_meta($result, 'tournament_name', $post_arr['name']);
			}
			if (isset($post_arr['short_name'])) {
				update_post_meta($result, 'tournament_shortname', $post_arr['short_name']);
			}
			if (isset($post_arr['countries'])) {
				update_post_meta($result, 'tournament_countries', $post_arr['countries']);
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
		public static function get_post_by_title($title, $tournamentsCPT) {
			$args = array(
				'post_type'              => $tournamentsCPT,
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
