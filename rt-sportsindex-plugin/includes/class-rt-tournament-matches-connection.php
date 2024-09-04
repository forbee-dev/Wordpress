<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Get the ACF fields from the options page and set the global variables accordingly
 */
add_action( 'acf/init', 'set_connection_global_variables' );
function set_connection_global_variables() {
    global $tournamentsCPT, $matchesCPT;

    if ( function_exists( 'get_field' ) ) {
        $tournamentsCPT = sanitize_key(strtolower(get_field('tournaments_post_type', 'option') ?: 'tournaments'));
        $matchesCPT = sanitize_key(strtolower(get_field('matches_post_type', 'option') ?: 'matches'));
    }
}

if (!class_exists('RT_Tournament_Matches_Connection')) {
    class RT_Tournament_Matches_Connection {

        public function __construct($tournamentsCPT, $matchesCPT) {
            $this->tournamentsCPT = $tournamentsCPT;
            $this->matchesCPT = $matchesCPT;
            
            add_action('admin_menu', array($this, 'add_tournament_matches_dashboard'));
            add_action('rest_api_init', array($this, 'register_tournament_matches_endpoint'));
        }

        /**
         * Register the tournament-matches endpoint
         */
        public function register_tournament_matches_endpoint() {
            register_rest_route('wp/v2', '/tournament-matches', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_tournament_matches_connections'),
                'permission_callback' => '__return_true',
            ));
        }

        /**
         * Get tournament-matches connections
         */
        public function get_tournament_matches_connections(WP_REST_Request $request) {
            $tournaments = get_posts(array(
                'post_type' => $this->tournamentsCPT,
                'post_status' => 'publish',
                'posts_per_page' => -1,
            ));

            $connections = array();

            foreach ($tournaments as $tournament) {
                $tournament_shortname = get_post_meta($tournament->ID, 'tournament_shortname', true);
                $tournament_data = array(
                    'id' => get_post_meta($tournament->ID, 'tournament_id', true),
                    'name' => $tournament->post_title,
                    'slug' => $tournament->post_name,
                    'shortname' => $tournament_shortname,
                    'matches' => array()
                );

                $matches = get_posts(array(
                    'post_type' => $this->matchesCPT,
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => 'match_tournament_short_name',
                            'value' => $tournament_shortname,
                        )
                    )
                ));

                foreach ($matches as $match) {
                    $sport = get_post_meta($match->ID, 'match_sport', true);
                    if (!isset($tournament_data['matches'][$sport])) {
                        $tournament_data['matches'][$sport] = array();
                    }
                    $tournament_data['matches'][$sport][] = array(
                        'key' => get_post_meta($match->ID, 'match_key', true),
                        'name' => $match->post_title,
                        'sub_title' => get_post_meta($match->ID, 'match_sub_title', true),
                        'slug' => $match->post_name,
                        'tournament_short_name' => get_post_meta($match->ID, 'match_tournament_short_name', true)
                    );
                }

                $connections[] = $tournament_data;
            }

            return new WP_REST_Response($connections, 200);
        }

        /**
         * Add dashboard page for tournament-matches connections
         */
        public function add_tournament_matches_dashboard() {
            add_submenu_page(
                'sports-settings',
                'Tournament-Matches Connections',
                'Tournament-Matches',
                'manage_options',
                'tournament-matches-connections',
                array($this, 'render_tournament_matches_dashboard')
            );
        }

        /**
         * Render the tournament-matches dashboard
         */
        public function render_tournament_matches_dashboard() {
            $connections = $this->get_tournament_matches_connections(new WP_REST_Request());
            $data = $connections->get_data();

            // Group all matches by sport and keep track of all tournaments
            $sports = array();
            $all_tournaments = array();
            foreach ($data as $tournament) {
                $all_tournaments[$tournament['id']] = $tournament;
                if (empty($tournament['matches'])) {
                    // If no matches, add to 'No Matches' category
                    if (!isset($sports['No Matches'])) {
                        $sports['No Matches'] = array();
                    }
                    $sports['No Matches'][] = array(
                        'tournament' => $tournament,
                        'matches' => array()
                    );
                } else {
                    foreach ($tournament['matches'] as $sport => $matches) {
                        if (!isset($sports[$sport])) {
                            $sports[$sport] = array();
                        }
                        $sports[$sport][] = array(
                            'tournament' => $tournament,
                            'matches' => $matches
                        );
                    }
                }
            }

            // Sort sports alphabetically, but keep 'No Matches' at the end
            uksort($sports, function($a, $b) {
                if ($a === 'No Matches') return 1;
                if ($b === 'No Matches') return -1;
                return strcasecmp($a, $b);
            });

            ?>
            <div class="wrap">
                <h1>Tournament-Matches Connections</h1>
                <?php foreach ($sports as $sport => $tournaments): ?>
                    <h2><?php echo esc_html($sport); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Tournament</th>
                                <th>Matches</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tournaments as $tournament_data): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($tournament_data['tournament']['name']); ?></strong><br>
                                        ID: <?php echo esc_html($tournament_data['tournament']['id']); ?><br>
                                        Slug: <a href="<?php echo esc_url(home_url("/{$this->tournamentsCPT}/{$tournament_data['tournament']['slug']}")); ?>" target="_blank"><?php echo esc_html($tournament_data['tournament']['slug']); ?></a>
                                    </td>
                                    <td>
                                        <?php if (empty($tournament_data['matches'])): ?>
                                            <em>No matches</em>
                                        <?php else: ?>
                                            <?php foreach ($tournament_data['matches'] as $match): ?>
                                                <strong><?php echo esc_html($match['name']); ?></strong><br>
                                                Key: <?php echo esc_html($match['key']); ?><br>
                                                Slug: <a href="<?php echo esc_url(home_url("/{$this->matchesCPT}/{$match['slug']}")); ?>" target="_blank"><?php echo esc_html($match['slug']); ?></a><br>
                                                <hr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <br><br>
                <?php endforeach; ?>
            </div>
            <?php
        }
    }
}