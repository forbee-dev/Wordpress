<?php
/*
 * Template file: sotm.php
 */
if (!defined('ABSPATH')) {
    exit;
}

// Get the selected option from the ACF field "show_first"
$show_first_option = get_field('show_first');

// Define an array to specify the order of display
$order = ($show_first_option === 'Offer of the Month') ? ['Offer of the Month', 'Casino of the Month'] : ['Casino of the Month', 'Offer of the Month'];

foreach ($order as $item) {
    if ($item === 'Offer of the Month') {
        ## Get offer of the month data
        $offer_of_the_month = get_field('offer_of_the_month');
        $offer_of_the_month_casino = get_field('offer_of_the_month_casino');
        $offer_of_the_month_logo = get_field('offer_of_the_month_logo');
        $offer_of_the_month_points = get_field('offer_of_the_month_points');
        $offer_of_the_month_casino_logo = get_the_post_thumbnail_url($offer_of_the_month_casino->ID, 'full');
        $offer_of_the_month_casino_meta = get_post_meta($offer_of_the_month_casino->ID);
        $offer_rating = $offer_of_the_month_casino_meta['hub_casino_rating'];
        $offer_out_link = '/go/' . $offer_of_the_month_casino_meta['out_link'][0];
        $offer_custom_link = isset($offer_of_the_month_casino_meta['default_alt_affiliate_tracker'][0]) ? $offer_of_the_month_casino_meta['default_alt_affiliate_tracker'][0] : '';
        $offer_custom_out_link = '/out/' . $offer_of_the_month_casino_meta['out_link'][0];
        $offer_direct_affiliate_link = isset($offer_of_the_month_casino_meta['direct_affiliate_link'][0]) ? $offer_of_the_month_casino_meta['direct_affiliate_link'][0] : '';
        $offer_custom_direct_link = isset($offer_of_the_month_casino_meta['custom_direct_link'][0]) ? $offer_of_the_month_casino_meta['custom_direct_link'][0] : '';
    } elseif ($item === 'Casino of the Month') {
        ## Get casino of the month data
        $casino_of_the_month = get_field('casino_of_the_month');
        $casino_of_the_month_casino = get_field('casino_of_the_month_casino');
        $casino_of_the_month_logo = get_field('casino_of_the_month_logo');
        $casino_of_the_month_points = get_field('casino_of_the_month_points');
        $casino_of_the_month_casino_logo = get_the_post_thumbnail_url($casino_of_the_month_casino->ID, 'full');
        $casino_of_the_month_casino_meta = get_post_meta($casino_of_the_month_casino->ID);
        $casino_rating = $casino_of_the_month_casino_meta['hub_casino_rating'];
        $casino_out_link = '/go/' . $casino_of_the_month_casino_meta['out_link'][0];
        $casino_custom_link = isset($casino_of_the_month_casino_meta['default_alt_affiliate_tracker'][0]) ? $casino_of_the_month_casino_meta['default_alt_affiliate_tracker'][0] : '';
        $casino_custom_out_link = '/out/' . $casino_of_the_month_casino_meta['out_link'][0];
        $casino_direct_affiliate_link = isset($casino_of_the_month_casino_meta['direct_affiliate_link'][0]) ? $casino_of_the_month_casino_meta['direct_affiliate_link'][0] : '';
        $casino_custom_direct_link = isset($casino_of_the_month_casino_meta['custom_direct_link'][0]) ? $casino_of_the_month_casino_meta['custom_direct_link'][0] : '';
    }
}

// Outlinks logic for Offer of the Month
$offerLink = '';

if (!empty($offer_custom_direct_link)) {
    $offerLink = $offer_custom_direct_link;
} else if (!empty ($offer_custom_link)) {
    $offerLink = $offer_custom_out_link;
} else if ( ! empty( $offer_direct_affiliate_link ) ) {
	$offerLink = $offer_direct_affiliate_link;
} else {
    $offerLink = $offer_out_link;
}

error_log($offerLink);

// Outlinks logic for Casino of the Month
$casinoLink = '';

if (!empty($casino_custom_direct_link)) {
    $casinoLink = $casino_custom_direct_link;
} else if (!empty($casino_custom_link)) {
    $casinoLink = $casino_custom_out_link;
} else if ( ! empty( $casino_direct_affiliate_link ) ) {
	$casinoLink = $casino_direct_affiliate_link;
} else {
    $casinoLink = $casino_out_link;
}

// Define an array with month names in Finnish.
$monthNames = ['Tammikuu', 'Helmikuu', 'Maaliskuu', 'Huhtikuu', 'Toukokuu', 'Kesäkuu',
 'Heinäkuu', 'Elokuu', 'Syyskuu', 'Lokakuu', 'Marraskuu', 'Joulukuu'];

// Get the current month's index (numeric representation, 1 to 12).
$currentMonthIndex = date('n') - 1;

// Retrieve the name of the current month from the array using the index obtained above.
$currentMonthName = $monthNames[$currentMonthIndex];

/**
 * Casino rating stars function
 * @param $rating
 * @return string $stars 
 */
function ratingStars($rating) {
    $stars = '';

    $star_icons = array(
        'full' => '<svg aria-hidden="true" data-prefix="fas" data-icon="star" class="svg-inline--fa fa-star fa-w-18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z"/></svg>',
        'half' => '<svg aria-hidden="true" data-prefix="fas" data-icon="star-half-alt" class="svg-inline--fa fa-star-half-alt fa-w-17" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 536 512"><path fill="currentColor" d="M508.55 171.51L362.18 150.2 296.77 17.81C290.89 5.98 279.42 0 267.95 0c-11.4 0-22.79 5.9-28.69 17.81l-65.43 132.38-146.38 21.29c-26.25 3.8-36.77 36.09-17.74 54.59l105.89 103-25.06 145.48C86.98 495.33 103.57 512 122.15 512c4.93 0 10-1.17 14.87-3.75l130.95-68.68 130.94 68.7c4.86 2.55 9.92 3.71 14.83 3.71 18.6 0 35.22-16.61 31.66-37.4l-25.03-145.49 105.91-102.98c19.04-18.5 8.52-50.8-17.73-54.6zm-121.74 123.2l-18.12 17.62 4.28 24.88 19.52 113.45-102.13-53.59-22.38-11.74.03-317.19 51.03 103.29 11.18 22.63 25.01 3.64 114.23 16.63-82.65 80.38z"/></svg>',
        'empty' => '<svg aria-hidden="true" data-prefix="far" data-icon="star" class="svg-inline--fa fa-star fa-w-18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M528.1 171.5L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6zM388.6 312.3l23.7 138.4L288 385.4l-124.3 65.3 23.7-138.4-100.6-98 139-20.2 62.2-126 62.2 126 139 20.2-100.6 98z"/></svg>'
    );

    $full_stars = floor($rating);
    $half_stars = ceil($rating - $full_stars);
    $empty_stars = 5 - $full_stars - $half_stars;

    for ($i = 0; $i < $full_stars; $i++) {
        $stars .= $star_icons['full'];
    }

    for ($i = 0; $i < $half_stars; $i++) {
        $stars .= $star_icons['half'];
    }

    for ($i = 0; $i < $empty_stars; $i++) {
        $stars .= $star_icons['empty'];
    }

    return $stars;
}

?>

<div class="selection-of-the-month">
    <?php foreach ($order as $item) : ?>
        <?php if ($item === 'Offer of the Month') : ?>
            <?php if ($offer_of_the_month) : ?>
                <div class="card">
                    <h5 class="title"><?php echo $currentMonthName; ?>n paras kasinobonus</h5>
                    <?php if ($offer_of_the_month_casino) : ?>
                        <div class="casino">
                            <div class="casino__img">
                                <a rel="nofollow" target="_blank" href="<?php echo $offerLink ?>">
                                    <img src="<?php echo $offer_of_the_month_logo ? $offer_of_the_month_logo['url'] : $offer_of_the_month_casino_logo; ?>" alt="<?php echo $offer_of_the_month_casino->post_title; ?>">
                                </a>
                            </div>
                            <div class="casino__info">
                                <p class="casino__name"><?php echo $offer_of_the_month_casino->post_title; ?></p>
                                <p class="casino__stars"><?php echo ratingStars($offer_rating[0]); ?> <span>(<?php echo $offer_rating[0] ?>)</span></p>      
                            </div>
                        </div> 
                    <?php endif; ?>

                    <?php if ($offer_of_the_month_points) : ?>
                        <div class="bonus">
                            <p>Bonus</p>
                            <ul>        
                                <?php foreach ($offer_of_the_month_points as $point) : ?>
                                    <li class="bonus__point"><?php echo $point['ootm_points']; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <div class="cta">
                        <div class="wp-block-buttons">
                            <div class="wp-block-button is-style-fill mb-0">
                                <a class="wp-block-button__link" rel="nofollow" target="_blank" href="<?php echo $offerLink ?>"><small>Nappaa bonus</small></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php elseif ($item === 'Casino of the Month') : ?>
            <?php if ($casino_of_the_month) : ?>
                <div class="card">
                    <h5 class="title"><?php echo $currentMonthName; ?>n paras nettikasino</h5>
                    <?php if ($casino_of_the_month_casino) : ?>
                        <div class="casino">
                            <div class="casino__img">
                                <a rel="nofollow" target="_blank" href="<?php echo $casinoLink ?>">
                                    <img src="<?php echo $casino_of_the_month_logo ? $casino_of_the_month_logo['url'] : $casino_of_the_month_casino_logo; ?>" alt="<?php echo $casino_of_the_month_casino->post_title; ?>">
                                </a>
                            </div>
                            <div class="casino__info">
                                <p class="casino__name"><?php echo $casino_of_the_month_casino->post_title; ?></p>
                                <p class="casino__stars"><?php echo ratingStars($casino_rating[0]); ?> <span>(<?php echo $casino_rating[0] ?>)</span></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($casino_of_the_month_points) : ?>
                        <div class="bonus">
                            <p>&nbsp;</p> <!-- hack to align bonus points -->
                            <ul>
                                <?php foreach ($casino_of_the_month_points as $point) : ?>
                                    <li class="bonus__points"><p><?php echo $point['cotm_points']; ?></p></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="cta">
                        <div class="wp-block-buttons">
                            <div class="wp-block-button is-style-fill mb-0">
                                <a class="wp-block-button__link" rel="nofollow" target="_blank" href="<?php echo $casinoLink ?>"><small>Kokeile kasinoa</small></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
