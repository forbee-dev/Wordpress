<?php

function hero_section() {
    // Get local from Rooberg
    $local = strtolower( rooberg_get_country() );
    //$local = 'pt';
    //error_log( 'local: ' . $local );

    // Get hero options array from ACF
    $hero_options= get_field('hero_options', 'option');
    //error_log( 'hero_mg: ' . print_r( $hero_options, true ) );

    ob_start();
    ?>
    <section class="hero_section">
		<div class="container">
			<div class="row">
                <div class="hero_carousel" >
                    <div class="carousel_track">
                    <?php
                        if ($hero_options) {
                            foreach ($hero_options as $hero_option) {
                                $hero_mg = strtolower( $hero_option['marketing_group'] );
                                $counter = 0;
                                if ($hero_mg === $local) {
                                    foreach ($hero_option['hero'] as $hero) {
                                        $banner_image_desktop = $hero['banner_image_desktop']['url'];
                                        $banner_image_mobile = $hero['banner_image_mobile']['url'];
                                        $banner_title = $hero['banner_title'];
                                        $banner_text = $hero['banner_text'];
                                        $cta_1 = $hero['cta_1'];
                                        $cta_1_url = $hero['cta_1_url'];
                                        $cta_2 = $hero['cta_2'];
                                        $cta_2_url = $hero['cta_2_url'];
                                        $cta_image = $hero['image']['url'];
                                        $cta_image_url = $hero['image_url'];
                                        $footer_heading = $hero_option['footer_heading'];
                                        $bullets = $hero_option['bullet_points'];
                                        $counter++;
                                        // Determine if the loading attribute should be lazy or not
                                        $loading_attr = $counter > 1 ? 'loading="lazy"' : '';

                                        //get GA4 Class and ID based on location
                                        $ga4_class = 'hero_ga';
                                        $ga4_id_1 = 'hero_' . $counter . '_cta_1_' . $local;
                                        $ga4_id_2 = 'hero_' . $counter . '_cta_2_' . $local;
                                        
                                        // Output the hero item
                    ?>
                                        <div class="hero_item" data-id='<?php echo $counter ?>' data-next='<?php echo $counter + 1 ?>' data-prev='<?php echo $counter -1 ?>'>
                                            <picture class="hero_bg_image" <?php echo $loading_attr; ?>>
                                                <source media="(max-width: 767px)" srcset="<?php echo esc_url($banner_image_mobile); ?>">
                                                <!-- Fallback image -->
                                                <img class="hero_bg_image" src="<?php echo esc_url($banner_image_desktop); ?>" alt="<?php echo esc_html($banner_title); ?>" <?php echo $loading_attr; ?>>
                                            </picture>
                                            <div class="hero_cta">
                                                <p class="banner_title"><?php echo esc_html($banner_title) ?></p>
                                                <p class="banner_text"><?php echo esc_html($banner_text) ?></p>
                                                <div class="cta">
                                                    <?php
                                                        if (!empty($cta_1)) {
                                                            echo '<a class="' . $ga4_class . ' cta_1 button" id="'.$ga4_id_1.'" href="' . esc_url($cta_1_url) . '">' . esc_html($cta_1) . '</a>';
                                                        }
                                                        if (!empty($cta_2)) {
                                                            echo '<a class="' . $ga4_class . ' cta_2 button" id="'.$ga4_id_2.'" href="' . esc_url($cta_2_url) . '">' . esc_html($cta_2) . '</a>';
                                                        }
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="cta_image">
                                                <a href="<?php echo esc_url($cta_image_url) ?>">
                                                    <img src="<?php echo esc_url($cta_image) ?>" alt="<?php echo esc_html($banner_title) ?>" <?php echo $loading_attr; ?>>
                                                </a>
                                            </div>
                                        </div>
                    <?php
                                    }
                                }
                            }
                        }
                    ?>
                    </div>
                </div>
                <button class="carousel_control prev">
                        <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" fill="currentColor" class="bi bi-chevron-left" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z" fill="none" stroke="black" stroke-width="1.8"/>
                            <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z" fill="none" stroke="currentColor" stroke-width="1"/>
                        </svg>
                </button>
                <button class="carousel_control next">
                    <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z" fill="none" stroke="black" stroke-width="1.8"/>
                        <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z" fill="none" stroke="currentColor" stroke-width="1"/>
                    </svg>
                </button>
			</div>
            <div class="hero_footer">
                <h1><?php echo esc_html($footer_heading) ?></h1>
                <?php if (isset($bullets) && is_array($bullets)) {
                    echo '<ul>';
                    // Loop through each bullet point
                    foreach ($bullets as $bullet) {
                        $bullet_text = $bullet['bullet'];
                        echo '<li>' . esc_html($bullet_text) . '</li>';
                    }
                    echo '</ul>';
                } ?>
            </div>
		</div>
	</section>
    <?php
    return ob_get_clean();
}