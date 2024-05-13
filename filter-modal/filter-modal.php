<?php
/**
 * Add ACF options page for filter modal
 */
function filter_modal_ACF() {
	// Add sub-page
	acf_add_options_sub_page( array(
		'page_title' => 'Filtration Modal Options',
		'menu_title' => 'Filtration Modal Options',
		'parent_slug' => 'custom-options',
	) );
}
add_action( 'acf/init', 'filter_modal_ACF' );

/**
 * enqueue filter-modal script and style
 * 
 */
function my_enqueue_and_localize_scripts() {
	if ( ! wp_script_is( 'filter-modal-script', 'enqueued' ) ) {
		$script_enqueued = enqueue_build_script( 'filter-modal-script', 'filter-modal', array( 'jquery' ), '1.0.0', TRUE );
		enqueue_build_style( 'filter-modal-style', 'filter-modal', array(), '1.0.0', 'all' );

		if ( $script_enqueued ) {
			// Fetch the ACF Zero case Casinos
			$zeroCaseCasinos = get_field( 'recommended_casinos', 'options' );
			$zeroCaseCasinos_ids = [];

			// Loop through the posts and collect their IDs.
			if ( $zeroCaseCasinos ) {
				foreach ( $zeroCaseCasinos as $zeroCaseCasino ) {
					$zeroCaseCasinos_ids[] = $zeroCaseCasino->ID;
				}
				wp_localize_script( 'filter-modal-script', 'zeroCaseCasinoData', [ 'ids' => $zeroCaseCasinos_ids ] );
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'my_enqueue_and_localize_scripts' );

/**
 * Render filter modal
 */
if (!function_exists('render_filter_modal')) {
    function render_filter_modal()
    {
        $auto_open = get_field('auto_open', 'options');

        //Step 1
        $step_1_img = get_field('image_s1', 'options');
        $step_1_title = get_field('title_s1', 'options');
        $step_1_text = get_field('description_s1', 'options');
        $step_1_casinos = get_field('casinos', 'options');

        //Step 2
        $step_2_img = get_field('image_s2', 'options');
        $step_2_title = get_field('title_s2', 'options');
        $step_2_text = get_field('description_s2', 'options');
        $step_2_tags = get_field('tags_s2', 'options');

        //Step 3
        $step_3_img = get_field('image_s3', 'options');
        $step_3_title = get_field('title_s3', 'options');
        $step_3_text = get_field('description_s3', 'options');

        //General settings
        $skip_button_label = get_field('skip_button_label', 'options');
        $next_button_label = get_field('next_button_label', 'options');
        $finish_button_label = get_field('finish_button_label', 'options');

        ob_start(); // Start output buffering
        ?>

        <!-- Modal -->
        <div id="filterModal" class="modal" data-auto-open="<?php echo $auto_open ?>"> 
            <div class="modal-content">
                <!-- Step 1 Content -->
                <div class="steps step1" id="step1">
                    <div class="steps__top">            
                        <div class="steps__img">
                            <img src="<?php echo $step_1_img ?>"  alt="<?php echo $step_1_title ?>">
                        </div>
                        <div class="steps__text">
                            <h2><?php echo $step_1_title ?></h2>
                            <p><?php echo $step_1_text ?></p>
                        </div>
                    </div>
                    <div class="step1__bottom">
                        <ul id="casinoList">
                            <?php foreach ($step_1_casinos as $casino):
                                // Get license category
                                $terms = get_the_terms($casino->ID, 'license_category');
                                if (!empty($terms) && !is_wp_error($terms)) {
                                    $category = $terms[0];
                                    $category_name = $category->name;
                                } else {
                                    $category_name = '';
                                }
                                $img_url = get_field('logo_square', $casino->ID);
                                $img_id = attachment_url_to_postid($img_url);
                                $img = wp_get_attachment_image_url($img_id, 'square-icon');
                                ?> 
                                    <li>
                                        <button class="casino-button" data-casino-name="<?php echo $casino->post_title ?>" data-casino-id="<?php echo $casino->ID ?>" data-casino-license="<?php echo $category_name ?>">
                                            <img src="<?php echo $img ?>"  alt="<?php echo esc_attr($casino->post_title) ?>">
                                            <?php echo $casino->post_title ?>
                                        </button>
                                    </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Step 2 Content -->
                <div class="steps" id="step2">
                    <div class="steps__top">
                        <div class="steps__img">   
                            <img src="<?php echo $step_2_img ?>"  alt="<?php echo $step_2_title ?>">
                        </div>
                        <div class="steps__text">
                            <h2><?php echo $step_2_title ?></h2>
                            <p><?php echo $step_2_text ?></p>
                        </div>
                    </div>
                    <div class="step2__bottom">
                        <ul id="tagsList">
                            <?php foreach ($step_2_tags as $tag): ?> 
                                <li>
                                    <button class="casino-button" data-tag-name="<?php echo $tag->name ?>">
                                        <?php echo $tag->name ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Buttons for navigation -->
                <div class="modal-nav">
                    <div class="modal-nav__top">
                        <button id="prevBtn"><i class="fas fa-chevron-left"></i></button>
                        <button id="closeBtn"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-nav__bottom">
                        <section class="modal-slider">
                            <ul class="dots">
                                <li></li>
                                <li></li>
                            <!--<li></li>-->
                            </ul>
                        </section>
                        <div class="modal-buttons">
                            <button id="skipBtn"><?php echo $skip_button_label ?></button>
                            <button id="nextBtn"><?php echo $next_button_label ?></button>
                            <button id="finishBtn"><?php echo $finish_button_label ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        return ob_get_clean(); // Stop output buffering and return the content
    }
}

/**
 * Render disclaimer when filter is applied
 */
if (!function_exists('filtered_by_modal')) {
    function filtered_by_modal()
    {
        // Disclaimer General settings 
        $filter_success_title = get_field('filter_success_disclaimer_title', 'options');
        $filter_success_description = get_field('filter_success_disclaimer_description', 'options');
        $zero_case_title = get_field('zero_case_disclaimer_title', 'options');
        $zero_case_description = get_field('zero_case_disclaimer_description', 'options');
        $show_original_button_label = get_field('show_original_button_label', 'options');
        $show_filtered_button_label = get_field('show_filtered_button_label', 'options');
        $back_to_modal_button_label = get_field('back_to_modal_button_label', 'options');
        
        ob_start(); ?>
            <div class="filteredByModal">
                <div class="filteredByModal__text">
                    <p><span><i class="fa fa-info-circle"></i></span><b><?php echo $filter_success_title ?></b></p>
                    <p><?php echo $filter_success_description ?></p>
                </div>
                <div class="filteredByModal__button" id="showOriginal">
                    <button id="popup-non-filtered"><b><?php echo $show_original_button_label ?></b></button>
                </div>
                <div class="filteredByModal__button" id="showFiltered">
                    <button id="popup-filtered"><b><?php echo $show_filtered_button_label ?></b></button>
                </div>
            </div>
            <div class="zeroCase">
                <div class="zeroCase__text">
                    <p><span><i class="fa fa-info-circle"></i></span><b> <?php echo $zero_case_title ?></b></p>
                    <p><?php echo $zero_case_description ?></p>
                </div>
                <div class="zeroCase__button" id="backToModal">
                    <button id="popup-zero-case"><b><?php echo $back_to_modal_button_label ?></b></button>
                </div>
            </div>

        <?php return ob_get_clean();
    }

}

/**
 * Render filter modal CTA
 * use this function to add the CTA bar to the page
 */
if ( !function_exists( 'render_filter_modal_CTA' ) ) {
	function render_filter_modal_CTA() {
		static $modal_rendered = false;
		$cta_text = get_field( 'cta_text', 'options' );

		ob_start();
		?>
        <div id="popup-bonus-window" class="modal-cta open-modal">
            <p class="modal-cta__text"><?php echo $cta_text ?> </p>
            <button class='bonus-button open-modal'><i class="fas fa-chevron-right"></i></button>
        </div>
        <?php

        if ( ! $modal_rendered ) {
            echo render_filter_modal();
            $modal_rendered = true;
        }
        
        return ob_get_clean();
	}
}

/**  
 * Register REST API route 
 */
add_action( 'rest_api_init', function () {
	register_rest_route( 'filter-modal/v2', '/store_choices/', array(
		'methods' => 'POST',
		'callback' => 'store_choices',
		// Add permission_callback to authenticate request if needed
	) );
} );

/**
 * Store the selected fields in the database
 */
// The callback function
$uuid_count = array(); // This should ideally be a database table or persistent store
if (!function_exists('store_choices')) {
    function store_choices( WP_REST_Request $request ) {
        $data = $request->get_json_params();
        $uuid = $data['selectedFields'][0]; // Assuming UUID is always at the first position

        // Fetch existing counts and UUIDs from the database
        $global_count = get_option( 'options_user_analytics_global_choices_count', [] );
        $uuids_counted = get_option( 'uuids_counted', [] );

        // Ensure they are arrays
        $global_count = is_array( $global_count ) ? $global_count : [];
        $uuids_counted = is_array( $uuids_counted ) ? $uuids_counted : [];

        // Reset counts related to this UUID
        foreach ( $uuids_counted as $name => $uuid_array ) {
            if ( in_array( $uuid, $uuid_array, true ) ) {
                $global_count[ $name ] = max( 0, $global_count[ $name ] - 1 ); // Decrement and ensure it doesn't go below 0

                // Remove field if count reaches 0
                if ( $global_count[ $name ] === 0 ) {
                    unset( $global_count[ $name ] );
                }

                $key = array_search( $uuid, $uuid_array, true );
                unset( $uuids_counted[ $name ][ $key ] ); // Remove this UUID for this name
            }
        }

        // Loop through selected fields to rebuild the counts
        for ( $i = 1; $i < count( $data['selectedFields'] ); $i++ ) {
            $name = $data['selectedFields'][ $i ]['name'];

            $global_count[ $name ] = $global_count[ $name ] ?? 0;
            $uuids_counted[ $name ] = $uuids_counted[ $name ] ?? [];

            if ( ! in_array( $uuid, $uuids_counted[ $name ], true ) ) {
                $global_count[ $name ]++;
                $uuids_counted[ $name ][] = $uuid;
            }
        }

        // Update the counts and UUIDs in the database
        update_option( 'options_user_analytics_global_choices_count', $global_count );
        update_option( 'uuids_counted', $uuids_counted );

        return new WP_REST_Response( json_encode( [ 'message' => 'Received' ] ), 200 );
    }
}

/**
 * Add counter results to the filter modal options page
 */
add_filter( 'acf/load_value', 'load_my_option_value', 10, 3 );
if (!function_exists('load_my_option_value')) {
    function load_my_option_value( $value, $post_id, $field ) {
        if ( $field['name'] == 'analytics' ) {
            $option_value = get_option( 'options_user_analytics_global_choices_count' );
            if ( is_array( $option_value ) ) {
				// Convert the array to a human-readable string
				$pretty_string = '';
				foreach ( $option_value as $key => $val ) {
					$pretty_string .= $key . ': ' . $val . PHP_EOL;
				}
				$value = $pretty_string;
            } else {
                $value = $option_value;
            }
        }
        return $value;
    }
}

/**
 * Add reset option to Counters on the filter modal options page
 */
add_action( 'acf/save_post', 'my_acf_save_post', 20 ); // Run after ACF saves the post
if  (!function_exists('my_acf_save_post')) {
    function my_acf_save_post( $post_id ) {
        $current_screen = get_current_screen(); // Check if we're on the correct options sub-page
        if ( $current_screen->id === 'theme-options_page_acf-options-filtration-modal-options' ) {
            $should_reset = get_field( 'reset_counters', 'option' ); // Retrieve the value of the ACF field

            if ( $should_reset ) {
                // Reset your counters
                update_option( 'options_user_analytics_global_choices_count', [] );
                update_option( 'uuids_counted', [] );

                // Optionally, uncheck the reset box for next time
                update_field( 'reset_counters', 0, 'option' );
            }
        }
    }
}


