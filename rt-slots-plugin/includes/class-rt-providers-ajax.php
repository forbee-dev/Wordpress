<?php

if ( function_exists( 'get_field' ) ) {
	$postType = get_field( 'post_type', 'option' );
	$slotApiUrl = get_field( 'slot_library_url', 'option' );
}

if (!class_exists('ProviderActionHandler')){

    class ProviderActionHandler {
        private static $nonce;

		private static function verify_nonce() {
			if ( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'slots_manager_nonce' ) ) {
				wp_die( 'Nonce verification failed!' );
			}
        }
    
        public static function handle_providers_request() {
			self::verify_nonce();

			if ( !isset( $_POST['action_type'] ) || !isset( $_POST['id'] ) ) {
				wp_send_json_error( 'Invalid request parameters' );
				return;
			}

            $action = sanitize_text_field($_POST['action_type'] ?? '');
            $id = sanitize_text_field($_POST['id'] ?? '');

			global $slotApiUrl;
			$slots_API = new Raketech_Slots_API( $slotApiUrl );
            $provider = $slots_API->getProviderById($id);

            if ( !$provider ) {
                wp_send_json_error( "Provider not found" );
                return;
            }

            //error_log('Provider: ' . print_r($provider, true));

            $term_data = array(
                'name' => $provider->name,
                'slug' => $provider->slug,
                'logo_color' => $provider->logo_color,
                'logo_white' => $provider->logo_white,
            );

            switch ($action) {
                case 'add':
					$response = self::addProvider($term_data);
                    break;
                case 'update':
					$response = self::updateProvider($term_data);
                    break;
                default:
					$response = 'Invalid action type';
            }

			echo $response;
            wp_die();
        }

		public static function addProvider( $term_data ) {
            global $slotApiUrl;
			$taxonomy = 'casino_software';
			$term = term_exists( $term_data['name'], $taxonomy );

            if ( $term ) {
                // Term already exists, so return a message or handle as needed
                return "Provider {$term_data['name']} already exists.";
            } else {
                $term_info = wp_insert_term( $term_data['name'], $taxonomy );
                if ( is_wp_error( $term_info ) ) {
                    return 'Error adding provider: ' . $term_info->get_error_message();
                }
                $term_id = $term_info['term_id'];

                // Add Meta
                $logo_color = $term_data['logo_color'];
                $logo_white = $term_data['logo_white'];

                add_term_meta($term_id, 'logo_color', $logo_color, true);
                add_term_meta($term_id, 'logo_white', $logo_white, true);

				// Download and upload provider images
				$color_logo_id = self::upload_provider_image($slotApiUrl, $logo_color);
				$white_logo_id = self::upload_provider_image($slotApiUrl, $logo_white);

				// Save the attachment IDs in the term metadata
				if($color_logo_id) {
					add_term_meta($term_id, 'logo_color_id', $color_logo_id, true);
				}

				if($white_logo_id) {
					add_term_meta($term_id, 'logo_white_id', $white_logo_id, true);
				}

                return "Provider {$term_data['name']} added successfully.";
            }
        }

        public static function updateProvider($term_data) {
            //self::handle_providers_request();
            global $slotApiUrl;
			$taxonomy = 'casino_software';
			$term = term_exists( $term_data['name'], $taxonomy);
            
            if ($term) {
                $term_id = $term['term_id'];
				$term_info = wp_update_term( $term_id, $taxonomy, $term_data );

				if ( is_wp_error( $term_info ) ) {
					return 'Error updating provider: ' . $term_info->get_error_message();
				}

                // Update Meta
                $name = $term_data['name'];
                $slug = $term_data['slug'];
				$logo_color = $term_data['logo_color'];
				$logo_white = $term_data['logo_white'];

                //update_term_meta( $term_id, 'external_id', $external_id, true );
                update_term_meta( $term_id, 'name', $name, true );
                update_term_meta( $term_id, 'slug', $slug, true );
				update_term_meta( $term_id, 'logo_color', $logo_color, true );
                update_term_meta( $term_id, 'logo_white', $logo_white, true );

                $existing_color_logo_id = get_term_meta($term_id, 'logo_color_id', true);
                $existing_white_logo_id = get_term_meta($term_id, 'logo_white_id', true);

				// Download and upload provider images
                if (empty($existing_color_logo_id)) {
                    $color_logo_id = self::upload_provider_image($slotApiUrl, $logo_color);
                }
			
                if (empty($existing_white_logo_id)) {
                    $white_logo_id = self::upload_provider_image($slotApiUrl, $logo_white);
                }

				// Save the attachment IDs in the term metadata
				if($color_logo_id) {
					add_term_meta($term_id, 'logo_color_id', $color_logo_id, true);
				}

				if($white_logo_id) {
					add_term_meta($term_id, 'logo_white_id', $white_logo_id, true);
				}

                return "Provider {$term_id} updated successfully.";
            } else {
                return "Provider {$term_data['slug']} not found.";
            }
        }

		private static function upload_provider_image($base_url, $image_url) {
			require_once ABSPATH.'wp-admin/includes/file.php';
			require_once ABSPATH.'wp-admin/includes/media.php';
			require_once ABSPATH.'wp-admin/includes/image.php';

			$tmp = download_url($image_url);

			if(is_wp_error($tmp)) {
				error_log('Error downloading image: '.$tmp->get_error_message());
				return null;
			}

			$file_array = array(
				'name' => basename($image_url),
				'tmp_name' => $tmp,
			);

			$id = media_handle_sideload($file_array, 0);

			if(is_wp_error($id)) {
				@unlink($file_array['tmp_name']);
				error_log('Error sideloading image: '.$id->get_error_message());
				return null;
			}

			return $id; // Returns the attachment ID
		}
    }
}
