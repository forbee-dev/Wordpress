<?php 
if(!class_exists('Raketech_Slots_API')) {
    class Raketech_Slots_API {
        private $slotApiUrl;

        public function __construct( $apiUrl ) {
			$this->slotApiUrl = $apiUrl;
        }

        /**
         * Check if API is staging
         */

        public function isStaging($additional_args = []) {
			// Get the current domain
			$domain = $this->slotApiUrl;

			// Check if 'staging' is in the domain
			$isStaging = strpos( $domain, 'staging' ) !== false;

			// Set up arguments for wp_remote_get
			$args = $isStaging ? [ 'sslverify' => false ] : [];

            return array_merge($args, $additional_args);
        }

        /**
         * Fetch all slots data from API
         * @return array
         */
		public function getSlots() {
			$transient_key = 'cached_slots_data';
			$cache_time = 12 * HOUR_IN_SECONDS; // Adjust as needed
			$cached_slots = get_transient( $transient_key );
			if ( $cached_slots !== false ) {
				return $cached_slots;
			}

			$slots = self::fetchSlotsFromAPI();
			set_transient( $transient_key, $slots, $cache_time );

			return $slots;
		}

        /**
         * Fetch Access tokens
         */
        public function fetchCloudflareCredentials() {
            // Fetch the entire group field
            $options = get_field('site_specific_custom_options', 'option'); 

            // Check if the Cloudflare token option is enabled
            if ($options['cloudflare_token'] === true) {
                $cfAccessClientId = isset($options['access_id']) ? $options['access_id'] : '';
                $cfAccessClientSecret = isset($options['access_secret']) ? $options['access_secret'] : '';

                return [
                    'CF-Access-Client-Id' => $cfAccessClientId,
                    'CF-Access-Client-Secret' => $cfAccessClientSecret,
                ];
            }

            // Return an empty array if the token is not enabled or fields are not set
            return [];
        }

		public function fetchSlotsFromAPI() {
            // Fetch Cloudflare credentials from the ACF group field
            $cfCredentials = $this->fetchCloudflareCredentials();

            $additional_args = [
                'headers' => $cfCredentials,
                'timeout' => 30,
            ];
            
			$args = $this->isStaging($additional_args);
			$response = wp_remote_get( $this->slotApiUrl . 'slots/json', $args );
            if ( is_wp_error( $response ) ) {
				error_log( $response->get_error_message() );
				return [];
			}
			$slots = json_decode( wp_remote_retrieve_body( $response ) );
            return $slots->items;
		}

		// Function to clear cache, can be triggered manually or via webhook
		public static function clearSlotsCache() {
            // Clear cache for slots
			$transient_key_slots = 'cached_slots_data';
			delete_transient( $transient_key_slots );

            // Clear cache for slugs
            $transient_key_slugs = 'all_post_slugs';
            delete_transient( $transient_key_slugs );
			wp_die();
		}

        /**
         * Fetch slot data by slug from API
         * @param $slug
         * @return array
         */
        public function getSlotBySlug($slug) {
            // Fetch Cloudflare credentials from the ACF group field
            $cfCredentials = $this->fetchCloudflareCredentials();

            $additional_args = [
                'headers' => $cfCredentials,
                'timeout' => 30,
            ];
            
			$args = $this->isStaging($additional_args);
            $response = wp_remote_get( $this->slotApiUrl . 'slots/json?slug=' . $slug, $args);
            if ( is_wp_error( $response ) ) {
                error_log( $response->get_error_message() );
                return;
            }
            $slot = json_decode( wp_remote_retrieve_body( $response ) );
            return $slot;
        }

        /**
         * Fetch providers data from API
         * @return array
         */
        public function getProviders() {
            // Fetch Cloudflare credentials from the ACF group field
            $cfCredentials = $this->fetchCloudflareCredentials();

            $additional_args = [
                'headers' => $cfCredentials,
                'timeout' => 30,
            ];
            
			$args = $this->isStaging($additional_args);
            $response = wp_remote_get( $this->slotApiUrl . 'slots/providers', $args);
            if ( is_wp_error( $response ) ) {
                error_log( $response->get_error_message() );
                return;
            }
            $providers = json_decode( wp_remote_retrieve_body( $response ) );
            return $providers->items;
        }

        /**
         * Fetch provider data by ID from API
         * @param $id
         * @return array
         */
        public function getProviderById($id) {
            // Fetch Cloudflare credentials from the ACF group field
            $cfCredentials = $this->fetchCloudflareCredentials();

            $additional_args = [
                'headers' => $cfCredentials,
                'timeout' => 30,
            ];
            
			$args = $this->isStaging($additional_args);
            $response = wp_remote_get( $this->slotApiUrl . 'slots/provider/' . $id, $args);
            if ( is_wp_error( $response ) ) {
                error_log( $response->get_error_message() );
                return;
            }

            $provider = json_decode( wp_remote_retrieve_body( $response ) );
            return $provider;
        }

    }
}