<?php

if ( function_exists( 'get_field' ) ) {
	$postType = get_field( 'post_type', 'option' );
	$slotApiUrl = get_field( 'slot_library_url', 'option' );
}

if (!class_exists('SlotActionHandler')){

    class SlotActionHandler {
		private $nonce;

		private function includes() {
			require_once plugin_dir_path( __FILE__ ) . 'class-rt-providers-ajax.php';
		}

		private static function verify_nonce() {
			if ( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'slots_manager_nonce' ) ) {
				wp_die('Nonce verification failed!');
			}
		}

        public static function handle_request() {
			self::verify_nonce();

			if ( !isset( $_POST['action_type'] ) || !isset( $_POST['slug'] ) ) {
                wp_send_json_error('Invalid request parameters');
                return;
            }

            $slug = sanitize_text_field($_POST['slug']);
            $action = sanitize_text_field($_POST['action_type']);

            global $slotApiUrl;
            $slots_API = new Raketech_Slots_API( $slotApiUrl );
            $slot = $slots_API->getSlotBySlug($slug);
            
			if ( !$slot ) {
                wp_send_json_error( "Slot not found" );
				return;
			}
            
			$fallback_iframe = sprintf('<iframe src="%s" width="100%%" height="100%%" frameborder="0" id="gameFrame" loading="lazy"></iframe>', esc_url($slot->desktop_iframe));
			$fallback_mobile_iframe = sprintf('<iframe src="%s" width="100%%" height="100%%" frameborder="0" id="gameFrame" loading="lazy"></iframe>',  esc_url(!empty($slot->mobile_iframe) ? $slot->mobile_iframe : $slot->desktop_iframe . '?mobile=1'));
			//error_log('Slot: ' . print_r($slot, true));

			$post_arr = array(
				'post_title' => $slot->name,
				//'post_content' => '',
				'post_author' => get_current_user_id(),
				'post_name' => $slot->slug,
				'tax_input' => array(
					'casino_software' => array(
					//'id' => $slot->provider_id,
					'name' => $slot->provider,
					),
				),
				'meta_input' => array(
					'id_slug' => $slot->slug,
					'slot_title' => $slot->name,
					'provider' => $slot->provider,
					'provider_id' => $slot->provider_id,
					'rating' => 8,
					'width' => $slot->width,
					'height' => $slot->height,
					'default_force_landscape' => $slot->landscape_only,
					'rtp' => $slot->rtp,
					'rtp_variable' => $slot->rtp_variable,
					'rtp_variable_min' => $slot->rtp_variable_min,
					'rtp_variable_max' => $slot->rtp_variable_max,
					'variance' => $slot->variance,
					'max_coin_win' => $slot->max_coin_win,
					'min_bet' => $slot->min_bet,
					'max_bet' => $slot->max_bet,
					'layout' => $slot->layout,
					'lines' => $slot->lines,
					'technology' => $slot->technology,
					'release_date' => $slot->release_date,
					'reels_rows' => $slot->reels_rows,
					'devices' => $slot->devices,
					'image' => $slot->image,
					'fallback_iframe' => $fallback_iframe,
					'fallback_mobile_iframe' => $fallback_mobile_iframe,
				),
			);

			//error_log( 'Post array: ' . print_r( $post_arr, true ) );

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
				case 'temporarily_offline':
					$response = self::update_offline_desktop( $post_arr );
					break;
				case 'temporarily_offline_mobile':
					$response = self::update_offline_mobile( $post_arr );
					break;
                default:
					$response = 'Invalid action';
            }

			echo $response;
			wp_die();
        }

		/**
		 * Add a new slot as draft
		 */
		private static function add_post( $post_arr ) {
			global $postType;
			global $slotApiUrl;
            $title = $post_arr['post_title'];
			$existing_post_id = self::get_post_by_title( $title, $postType );
			if ( $existing_post_id ) {
				return "Post already exists for: " . $title . "with the ID: " . $existing_post_id->ID;
			}
			$post_arr['post_type'] = $postType;
			$post_arr['post_status'] = 'draft';
			$post_id = wp_insert_post( $post_arr );
			self::slot_add_provider( $post_arr, $post_id );

			// Upload image
			$image_path = $post_arr['meta_input']['image'];
			$slug = $post_arr['post_name'];
			$image_id = self::handle_image_upload($slotApiUrl, $image_path, $slug);

			if($image_id) {
				// Add the image ID to the post array
				$post_arr['meta_input']['slot_image'] = $image_id;
				$acf_img = 'slot_image';
				update_post_meta($post_id, $acf_img, $image_id);
				set_post_thumbnail($post_id, $image_id);

			} else {
				error_log('Failed to upload image for slot: '.$title);
			}

			return $post_id ? "Draft post created: $title" : "Failed to create draft post";
		}

		/**
		 * Publish a draft Slot or create a new Slot if it doesn't exist
		 */
        private static function publish_post($post_arr) {
            global $postType;
			global $slotApiUrl;
			$title = $post_arr['post_title'];
            $existing_post = self::get_post_by_title( $title, $postType );
            if ($existing_post && $existing_post->post_status == 'draft') {
                $existing_post->post_status = 'publish';
                wp_update_post($existing_post);
    			return "Post published: " . $title;
            } elseif (!$existing_post) {
				$post_arr['post_type'] = $postType;
				$post_arr['post_status'] = 'publish';
				$post_id = wp_insert_post( $post_arr );
				self::slot_add_provider($post_arr, $post_id);

				// Upload image
				$image_path = $post_arr['meta_input']['image'];
				$slug = $post_arr['post_name'];
				$image_id = self::handle_image_upload($slotApiUrl, $image_path, $slug);

				if($image_id) {
					// Add the image ID to the post array
					$post_arr['meta_input']['slot_image'] = $image_id;
					$acf_img = 'slot_image';
					update_post_meta($post_id, $acf_img, $image_id);
					set_post_thumbnail($post_id, $image_id);

				} else {
					error_log('Failed to upload image for slot: '.$title);
				}
            }
            return $post_id ? "Published post created: $title" : "Failed to create post, post already exists";
        }

		/**
		 * Update an existing Slot
		 */
		private static function update_post( $post_arr ) {
            global $postType;
			global $slotApiUrl;
			$title = $post_arr['post_title'];
			$existing_post_id = self::get_post_by_title( $title, $postType );
			$existing_slot_img = get_post_meta($existing_post_id->ID, 'slot_image', true);
			if ( !$existing_post_id ) {
				return "No post found to update";
			} else {
				$post_arr['ID'] = $existing_post_id->ID;
			    $post_id = wp_update_post( $post_arr );

				// Update provider
				self::slot_add_provider( $post_arr, $existing_post_id->ID );

				// Upload image
				if ($existing_slot_img == '') {
					$image_path = $post_arr['meta_input']['image'];
					$slug = $post_arr['post_name'];
					$image_id = self::handle_image_upload($slotApiUrl, $image_path, $slug);

					if($image_id) {
						// Add the image ID to the post array
						$post_arr['meta_input']['slot_image'] = $image_id;
						$acf_img = 'slot_image';
						update_post_meta($post_id, $acf_img, $image_id);
						set_post_thumbnail($post_id, $image_id);

					} else {
						error_log('Failed to upload image for slot: '.$title);
					}
				}
            }
			return "Post updated: " . $title;
		}

		/**
		 * Check if provider exist and add it if not
		 * Set the provider for the slot
		 */
		public static function slot_add_provider( $post_arr, $post_id ) {
			global $slotApiUrl;


			$provider_id = $post_arr['meta_input']['provider_id'];

			// Fetch additional provider details
			$slots_API = new Raketech_Slots_API( $slotApiUrl );
			$provider_details = $slots_API->getProviderById($provider_id);

			if ( !$provider_details ) {
				error_log( "Failed to fetch provider details for ID: $provider_id" );
				return;
			}

			// Prepare the data array for the addProvider method
			$term_data = [ 
				'name' => $provider_details->name,
				'logo_color' => $provider_details->logo_color,
				'logo_white' => $provider_details->logo_white
			];

			// Call the addProvider method from the Providers class
			$response = ProviderActionHandler::addProvider( $term_data );

			$term = get_term_by( 'name', $provider_details->name, 'casino_software' );
			if ( !empty($term) ) {
				// If the provider was added successfully, get the term ID
				$term_id = $term->term_id;

				// Associate the term with the post
				wp_set_post_terms( $post_id, array( $term_id ), 'casino_software', true );
			} else {
				// Handle errors or situations where the provider already exists
				error_log( $response );
			}
		}

		private static function handle_image_upload($base_url, $image_url, $slug) {
			require_once ABSPATH.'wp-admin/includes/file.php';
			require_once ABSPATH.'wp-admin/includes/media.php';
			require_once ABSPATH.'wp-admin/includes/image.php';
			
			$tmp = download_url($image_url);

			// Check for download errors
			if(is_wp_error($tmp)) {
				error_log('Error downloading image: '.$tmp->get_error_message());
				return null;
			}

			// Rename logic
			$image_name_addons = ['free', 'slot', 'logo', 'gratis', 'new', 'thumb', 'featured'];
			$image_name_separators = ['-', '_'];
			$image_slug = strtolower($slug);

			shuffle($image_name_addons);
			shuffle($image_name_separators);

			$file_ending = pathinfo($image_url, PATHINFO_EXTENSION);
			$new_file_name = $image_slug.$image_name_separators[0].$image_name_addons[0].'.'.$file_ending;

			$file_array = array(
				'name' => $new_file_name,
				'tmp_name' => $tmp,
			);

			// Check for file array errors
			if(is_wp_error($file_array['tmp_name'])) {
				error_log('Error in file array: '.$file_array['tmp_name']->get_error_message());
				@unlink($file_array['tmp_name']);
				return null;
			}

			$id = media_handle_sideload($file_array, 0);

			// Check for sideload errors
			if(is_wp_error($id)) {
				error_log('Error sideloading image: '.$id->get_error_message());
				@unlink($file_array['tmp_name']);
				return null;
			}

			return $id; // Returns the attachment ID
		}


		/**
		 * Check if the slot is offline on Desktop and update the post accordingly
		 */
		private static function update_offline_desktop( $post_arr ) {
			global $postType;
			$title = $post_arr['post_title'];
			$existing_post_id = self::get_post_by_title( $title, $postType );
			if ( !$existing_post_id ) {
				return "No post found to update, please add the slot first";
			} else {
				$post_arr['ID'] = $existing_post_id->ID;

				// Get the current state of the temporarily_offline meta
				$current_state = get_post_meta( $existing_post_id->ID, 'temporarily_offline', true );

				// Toggle the temporarily_offline state
				$new_state = ! $current_state;

				// Set the new state in the post array
				$post_arr['meta_input']['temporarily_offline'] = $new_state;
				wp_update_post( $post_arr );
			}
			return "Slot " . $title . " updated to " . ( $new_state ? 'Offline' : 'Online' ) . " on Desktop";
		}

		/**
		 * Check if the slot is offline on Mobile and update the post accordingly
		 */
		private static function update_offline_mobile( $post_arr ) {
			global $postType;
			$title = $post_arr['post_title'];
			$existing_post_id = self::get_post_by_title( $title, $postType );
			if ( !$existing_post_id ) {
				return "No post found to update, please add the slot first";
			} else {
				$post_arr['ID'] = $existing_post_id->ID;

				// Get the current state of the offline_mobile meta
				$current_state = get_post_meta($existing_post_id->ID, 'temporarily_offline_mobile', true);

				// Toggle the offline_mobile state
				$new_state = !$current_state;

				// Set the new state in the post array
				$post_arr['meta_input']['temporarily_offline_mobile'] = $new_state;
				wp_update_post( $post_arr );
			}
			return "Slot " . $title . " updated to " . ($new_state ? 'Offline' : 'Online') . " on Mobile";
		}

		/**
		 * Get a post by title and post type
		 */
        public static function get_post_by_title($title, $postType) {
			$args = [
				'post_type' => $postType,
                'post_status' => 'any',
				'posts_per_page' => 1,
				'title' => $title
			];

			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				return $query->posts[0];
			}
            return null;
        }

        /* public function get_post_by_slug($slug, $postType) {
            $args = [
                'post_type' => $postType,
                'post_status' => 'any',
                'posts_per_page' => 1,
                'name' => $slug
            ];

            $query = new WP_Query( $args );
            if ( $query->have_posts() ) {
                return $query->posts[0];
            }
            return null;
        } */
    }
}