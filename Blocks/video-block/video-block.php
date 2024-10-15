<?php
/**
 * Video Block Template.
 *
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'video-block-' . $block['id'];
if( !empty($block['anchor']) ) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$className = 'video-block';
if( !empty($block['className']) ) {
    $className .= ' ' . $block['className'];
}
if( !empty($block['align']) ) {
    $className .= ' align' . $block['align'];
}

// Load values and assign defaults.
$youtube_url = get_field('youtube_url') ?: '';
$meta_title = get_field('meta_title') ?: '';
$meta_description = get_field('meta_description') ?: '';
$custom_thumbnail = get_field('custom_thumbnail');

// Extract video ID from YouTube URL
$video_id = '';
if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $youtube_url, $match)) {
    $video_id = $match[1];
}

// Determine which thumbnail to use
$thumbnail_url = '';
$thumbnail_alt = '';
if ($custom_thumbnail) {
    $thumbnail_url = $custom_thumbnail['url'];
    $thumbnail_alt = $custom_thumbnail['alt'];
} elseif ($video_id) {
    $thumbnail_url = "https://img.youtube.com/vi/{$video_id}/sddefault.jpg";
    $thumbnail_alt = "YouTube video thumbnail";
}

?>
<div id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($className); ?>">
    <?php if ($video_id): ?>
        <div itemscope itemtype="http://schema.org/VideoObject" class="video-container" data-video-id="<?php echo esc_attr($video_id); ?>">
            <meta itemprop="name" content="<?php echo esc_attr($meta_title); ?>">
            <meta itemprop="description" content="<?php echo esc_attr($meta_description); ?>">
            <meta itemprop="uploadDate" content="<?php echo date('c'); ?>">
            <meta itemprop="thumbnailUrl" content="<?php echo esc_url($thumbnail_url); ?>">
            <meta itemprop="embedUrl" content="https://www.youtube.com/embed/<?php echo esc_attr($video_id); ?>">
            <meta itemprop="contentUrl" content="<?php echo esc_url($youtube_url); ?>">
            
            <img itemprop="thumbnail" src="<?php echo esc_url($thumbnail_url); ?>" 
                 alt="<?php echo esc_attr($thumbnail_alt); ?>" 
                 class="video-thumbnail"
                 loading="lazy"
                 decoding="async">
            <div class="play-button"></div>
        </div>
    <?php endif; ?>
</div>