<?php 
if(!class_exists('Rt_Sports_API')) {
    class Rt_Sports_API {
        private $sportsApiUrl;

        public function __construct( $apiUrl ) {
			$this->sportsApiUrl = $apiUrl;
        }

        /**
         * Check if API is staging
         */

        public function isStaging($additional_args = []) {
			// Get the current domain
			$domain = $this->sportsApiUrl;

			// Check if 'staging' is in the domain
			$isStaging = strpos( $domain, 'staging' ) !== false;

			// Set up arguments for wp_remote_get
			$args = $isStaging ? [ 'sslverify' => false ] : [];

            return array_merge($args, $additional_args);
        }

        /**
         * Fetch all sports data from API
         * @return array
         */
		public function getTournaments() {
			$transient_key = 'cached_tournaments_data';
			$cache_time = 1 * HOUR_IN_SECONDS; // Adjust as needed
			$cached_tournaments = get_transient( $transient_key );
			if ( $cached_tournaments !== false ) {
				return $cached_tournaments;
			}

			$tournaments = self::fetchTournamentsFromAPI();
			set_transient( $transient_key, $tournaments, $cache_time );

			return $tournaments;
		}
        public function getMatches() {
            $transient_key = 'cached_matches_data';
            $cache_time = 1 * HOUR_IN_SECONDS; // Adjust as needed
            $cached_matches = get_transient( $transient_key );
            if ( $cached_matches !== false ) {
                return $cached_matches;
            }

            $matches = self::fetchMatchesFromAPI();
            set_transient( $transient_key, $matches, $cache_time );

            return $matches;
        }


        /**
         * Fetch Access tokens
         */
        public function fetchCredentials() {
            // Fetch the entire group field
            $options = get_field('site_specific_custom_options', 'option'); 

            $headers = [];

            // Check if the Cloudflare token option is enabled
            if ($options['cloudflare_token'] === true) {
                $cfAccessClientId = isset($options['access_id']) ? $options['access_id'] : '';
                $cfAccessClientSecret = isset($options['access_secret']) ? $options['access_secret'] : '';

                $headers['CF-Access-Client-Id'] = $cfAccessClientId;
                $headers['CF-Access-Client-Secret'] = $cfAccessClientSecret;
            }

            // Add API key to headers
            if (isset($options['api_key']) && !empty($options['api_key'])) {
                $headers['api-key'] = $options['api_key'];
            }

            // Return headers
            return $headers;
        }

		public function fetchTournamentsFromAPI() {
            // Fetch Cloudflare credentials from the ACF group field
            $credentials = $this->fetchCredentials();

            $additional_args = [
                'headers' => $credentials,
                'timeout' => 30,
            ];
            
			$args = $this->isStaging($additional_args);
			$response = wp_remote_get( $this->sportsApiUrl . 'tournaments', $args );
            if ( is_wp_error( $response ) ) {
				error_log( $response->get_error_message() );
				return [];
			}
			$tournaments = json_decode( wp_remote_retrieve_body( $response ) );
            return $tournaments;
		}

		// Function to clear cache, can be triggered manually or via webhook
		public static function clearSportsCache() {
            // Clear cache for Tournaments
            delete_transient( 'cached_tournaments_data' );

            // Clear cache for Matches
            delete_transient( 'cached_matches_data' );

            // Clear cache for Slugs API Endpoint
            delete_transient( 'all_post_slugs' );
			wp_die();
		}

        /**
         * Fetch Tournaments data by id from API
         * @param $id
         * @return array
         */
        public function getTournamentById($id) {
            // Fetch credentials from the ACF group field
            $credentials = $this->fetchCredentials();

            $additional_args = [
                'headers' => $credentials,
                'timeout' => 30,
            ];
            
			$args = $this->isStaging($additional_args);
            $response = wp_remote_get( $this->sportsApiUrl . 'tournaments/' . $id, $args);
            if ( is_wp_error( $response ) ) {
                error_log( $response->get_error_message() );
                return;
            }
            $tournaments = json_decode( wp_remote_retrieve_body( $response ) );
            return $tournaments;
        }

        /**
         * Fetch Matches data from API
         * @return array
         */
        public function fetchMatchesFromAPI() {
            // Fetch credentials from the ACF group field
            $credentials = $this->fetchCredentials();

            $additional_args = [
                'headers' => $credentials,
                'timeout' => 30,
            ];
            
			$args = $this->isStaging($additional_args);
            $response = wp_remote_get( $this->sportsApiUrl . 'matches', $args);
            if ( is_wp_error( $response ) ) {
                error_log( $response->get_error_message() );
                return;
            }
            $matches = json_decode( wp_remote_retrieve_body( $response ) );
            return $matches;
        }

        /**
         * Fetch provider data by ID from API
         * @param $key
         * @return array
         */
        public function getMatchesByKey($key) {
            // Fetch credentials from the ACF group field
            $credentials = $this->fetchCredentials();

            $additional_args = [
                'headers' => $credentials,
                'timeout' => 30,
            ];
            
			$args = $this->isStaging($additional_args);
            $response = wp_remote_get( $this->sportsApiUrl . 'matches/' . $key, $args);
            if ( is_wp_error( $response ) ) {
                error_log( $response->get_error_message() );
                return;
            }

            $matches = json_decode( wp_remote_retrieve_body( $response ) );
            return $matches;
        }

    }
}
