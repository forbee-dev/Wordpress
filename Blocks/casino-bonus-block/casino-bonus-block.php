<?php 
/**
 * Casino Bonus Block Template.
 */

$casino_bonus_block_repeater = get_field('casino_bonus_block_rep');
?>

<div class="casino-bonus-block-container">
    <?php 
    if( have_rows('casino_bonus_block_rep') ) {
        $repeater_index = 0;
        foreach ($casino_bonus_block_repeater as $casino_bonus_group) {
            the_row(); 
            // Access Data
            $casino_bonus_group = $casino_bonus_block_repeater[$repeater_index]['casino_bonus_block_group'];
            //error_log(print_r($casino_bonus_group, true));
            if ($casino_bonus_group) {
                // Casino Title
                $casino_title = $casino_bonus_group['casino_title'] ?? null;
                $casino_position = $casino_bonus_group['position'] ?? null;
                if (empty($casino_position)) {
                    $casino_position = $repeater_index + 1;
                }
                // Category Star Marks
                $category_star_marks = $casino_bonus_group['category_star_marks'] ?? null;
                // Top Image
                $top_image = $casino_bonus_group['top_image'] ?? null;
                // CTA Repeater
                $cta_rep = $casino_bonus_group['cta_rep'] ?? null;
                // Bonus Image
                $bonus_image = $casino_bonus_group['bonus_image'] ?? null;
                // Features Box Block
                $features_box_title = $casino_bonus_group['fbb-feature_title'] ?? null;
                $features_box_content = $casino_bonus_group['fbb-feature_content'] ?? null;
                // Score Box Block
                $score_box_title = $casino_bonus_group['score_box_title'] ?? null;
                $recommended_score_title = $casino_bonus_group['recommended_score_title'] ?? null;  
                $outstanding_rating_title = $casino_bonus_group['outstanding_rating_title'] ?? null;
                $assessment_title = $casino_bonus_group['assessment_title'] ?? null;
                $point_individual_rating_title = $casino_bonus_group['point_individual_rating_title'] ?? null;
                $recommended_score = $casino_bonus_group['recommended_score'] ?? null;
                $outstanding_rating = $casino_bonus_group['outstanding_rating'] ?? null;
                $assessment_rating = $casino_bonus_group['assessment_rating'] ?? null;
                $point_individual_rating = $casino_bonus_group['point_individual_rating'] ?? null;
                $recommended_score_info = $casino_bonus_group['recommended_score_info'] ?? null;
                // Get the liquid balloon content from ACF field
                $balloon_title = $casino_bonus_group['balloon_title'] ?? '';
                $balloon_avatar = $casino_bonus_group['balloon_avatar'] ?? '';
                $balloon_avatar_name = $casino_bonus_group['balloon_avatar_name'] ?? '';
                $balloon_content = $casino_bonus_group['balloon_content'] ?? '';
                // Pros Cons Block
                $pros_title = $casino_bonus_group['pros_title'] ?? null;
                $cons_title = $casino_bonus_group['cons_title'] ?? null;
                $pros_rep = $casino_bonus_group['pros_rep'] ?? null;
                // Casino Brief Block
                $casino_brief_rep = $casino_bonus_group['brief_rep'] ?? null;
                // Bonus Slider Block
                $bonus_slider_rep = $casino_bonus_group['bonus_slider_rep'] ?? null;
                // Real Voice Block
                $real_voice_rep = $casino_bonus_group['real_voice_testimonials'] ?? null;

                ?>
                <div class="casino-bonus-block-repeater-item">
                    <?php if ($casino_title): ?>
                    <div class="casino-bonus-block-casino-details">
                        <img src="/wp-content/themes/mercury-child/assets/images/ranking-<?php echo $casino_position; ?>.png" alt="Ranking Image" class="cbb-image">
                        <h3 class="cbb-h3 cbb-h3-<?php echo $repeater_index + 1; ?>"><?php echo esc_html($casino_title); ?></h3>
                    </div>
                    <?php endif; ?>

                    <?php if ($category_star_marks): ?>
                    <div class="casino-bonus-block-category-star-marks">
                        <?php
                        acf_render_block(array(
                            'name' => 'acf/category-star-marks',
                            'id' => 'category-star-marks-' . $repeater_index,
                            'data' => array(
                                'category_star_marks' => $category_star_marks,
                            ),
                        ));
                        ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($top_image): ?>
                    <div class="casino-bonus-block-top-image">
                        <img src="<?php echo esc_url($top_image); ?>" alt="" />
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($casino_bonus_group['top_text'])): ?>
                    <div class="top-text">
                        <p><?php echo $casino_bonus_group['top_text']; ?></p> 
                    </div>
                    <?php endif; ?>

                    <?php if ($cta_rep): ?>
                    <div class="casino-bonus-block-cta-buttons">
                        <?php
                        foreach ($cta_rep as $index => $cta) {
                            acf_render_block(array(
                                'name' => 'acf/casino-bonus',
                                'id' => 'casino-bonus-' . $index . '-' . $repeater_index,
                                'data' => array(
                                    'cb_button_text' => $cta['cb_button_text'],
                                    'cb_button_tag' => $cta['cb_button_tag'],
                                    'cb_button_url' => $cta['cb_button_url'],
                                ),
                            ));
                        }
                        ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($bonus_image): ?>
                    <div class="casino-bonus-block-bonus-image">
                        <img src="<?php echo esc_url($bonus_image['url']); ?>" alt="<?php echo esc_attr($bonus_image['alt']); ?>" />
                    </div>
                    <?php endif; ?>

                    <div class="features-score-ballon-container">
                        <?php if ($features_box_title): ?>
                        <div class="casino-bonus-block-features-box-block">
                            <?php
                            acf_render_block(array(
                                'name' => 'acf/features-box-block',
                                'id' => 'features-box-block-' . $repeater_index,
                                'data' => array(
                                    'fbb-feature_title' => $features_box_title,
                                    'fbb-feature_content' => $features_box_content,
                                ),
                            ));
                            ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($score_box_title): ?>
                        <div class="casino-bonus-block-score-box-block">
                            <?php
                            acf_render_block(array(
                                'name' => 'acf/score-box-block',
                                'id' => 'score-box-block-' . $repeater_index,
                                'data' => array(
                                    'score_box_title' => $score_box_title,
                                    'recommended_score_title' => $recommended_score_title,
                                    'outstanding_rating_title' => $outstanding_rating_title,
                                    'assessment_title' => $assessment_title,
                                    'point_individual_rating_title' => $point_individual_rating_title,
                                    'recommended_score' => $recommended_score,
                                    'outstanding_rating' => $outstanding_rating,
                                    'assessment_rating' => $assessment_rating,
                                    'point_individual_rating' => $point_individual_rating,
                                    'recommended_score_info' => $recommended_score_info,
                                ),
                            ));
                            ?>

                            <?php if ($balloon_title): ?>
                            <?php $default_avatar = '/wp-content/themes/mercury-child/assets/images/casimaru.webp'; ?>
                            <div class="custom-speech-bubble">
                                <div class="avatar-container">
                                    <img src="<?php echo esc_url($balloon_avatar ? $balloon_avatar : $default_avatar); ?>" alt="<?php echo esc_attr($balloon_avatar_name); ?>" class="avatar-image">
                                    <span class="avatar-name"><?php echo esc_html($balloon_avatar_name); ?></span>
                                </div>
                                <div class="speech-content">
                                    <p class="speech-title"><?php echo esc_html($balloon_title); ?></p>
                                    <p class="speech-text"><?php echo esc_html($balloon_content); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($pros_title): ?>
                    <div class="casino-bonus-block-pros-cons-block">
                        <?php
                        acf_render_block(array(
                            'name' => 'acf/pros-cons-block',
                            'id' => 'pros-cons-block-' . $repeater_index,
                            'data' => array(
                                'pros_title' => $pros_title,
                                'cons_title' => $cons_title,
                                'pros_rep' => $pros_rep,
                            ),
                        ));
                        ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($casino_brief_rep): ?>
                    <div class="casino-bonus-block-casino-brief-block">
                        <?php
                        acf_render_block(array(
                            'name' => 'acf/casino-brief-block',
                            'id' => 'casino-brief-block-' . $repeater_index,
                            'data' => array(
                                'brief_rep' => $casino_brief_rep,
                            ),
                        ));
                        ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($bonus_slider_rep): ?>
                    <div class="casino-bonus-block-bonus-slider-block">
                        <?php
                        acf_render_block(array(
                            'name' => 'acf/bonus-slider-block',
                            'id' => 'bonus-slider-block-' . $repeater_index,
                            'data' => array(
                                'bonus_slider_rep' => $bonus_slider_rep,
                            ),
                        ));
                        ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($real_voice_rep): ?>
                    <div class="casino-bonus-block-real-voice-block">
                        <?php
                        acf_render_block(array(
                            'name' => 'acf/real-voice-block',
                            'id' => 'real-voice-block-' . $repeater_index,
                            'data' => array(
                                'real_voice_testimonials' => $real_voice_rep,
                            ),
                        ));
                        ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($cta_rep): ?>
                    <div class="casino-bonus-block-cta-buttons">
                        <?php
                        foreach ($cta_rep as $index => $cta) {
                            acf_render_block(array(
                                'name' => 'acf/casino-bonus',
                                'id' => 'casino-bonus-' . $index . '-' . $repeater_index,
                                'data' => array(
                                    'cb_button_text' => $cta['cb_button_text'],
                                    'cb_button_tag' => $cta['cb_button_tag'],
                                    'cb_button_url' => $cta['cb_button_url'],
                                ),
                            ));
                        }
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php 
                $repeater_index++;
            } else {
                if (WP_DEBUG) {
                    error_log('No casino_bonus_group found for row ' . $repeater_index);
                }
            }
        }
    } else { ?>
        <p>No content blocks found.</p>
    <?php } ?>
</div>