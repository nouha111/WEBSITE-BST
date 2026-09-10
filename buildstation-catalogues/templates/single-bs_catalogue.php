<?php
if (!defined('ABSPATH')) { exit; }
get_header();
while (have_posts()): the_post();
    $id = get_the_ID();
    $brand = get_post_meta($id, '_bsc_brand', true) ?: get_the_title();
    $edition = get_post_meta($id, '_bsc_edition', true);
    $logo_id = (int)get_post_meta($id, '_bsc_logo_id', true);
    $pdf_id = (int)get_post_meta($id, '_bsc_pdf_id', true);
    $pdf_url = $pdf_id ? wp_get_attachment_url($pdf_id) : '';
    wp_enqueue_style('bsc-front', BSC_URL . 'assets/front.css', [], BSC_VERSION);
?>
<main class="bsc-single">
    <div class="bsc-single-wrap">
        <div class="bsc-breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a><span>/</span><span>Catalogues</span><span>/</span><span><?php echo esc_html($brand); ?></span></div>
        <div class="bsc-hero">
            <div class="bsc-single-cover"><?php if (has_post_thumbnail()) the_post_thumbnail('large'); else echo '<span class="bsc-placeholder">CATALOGUE</span>'; ?></div>
            <div class="bsc-single-copy">
                <?php if ($logo_id) echo wp_get_attachment_image($logo_id, 'medium', false, ['class'=>'bsc-logo','alt'=>$brand]); ?>
                <p class="bsc-kicker"><?php echo esc_html($brand); ?></p>
                <h1><?php the_title(); ?></h1>
                <?php if ($edition): ?><p class="bsc-edition"><?php echo esc_html($edition); ?></p><?php endif; ?>
                <div class="bsc-description"><?php the_content(); ?></div>
                <?php if ($pdf_url): ?><div class="bsc-actions"><a class="bsc-button bsc-primary" href="<?php echo esc_url($pdf_url); ?>" target="_blank" rel="noopener">Open Catalogue</a><a class="bsc-button" href="<?php echo esc_url($pdf_url); ?>" download>Download PDF</a></div><?php endif; ?>
            </div>
        </div>
        <?php if ($pdf_url): ?><section class="bsc-viewer"><h2>Browse the catalogue</h2><iframe src="<?php echo esc_url($pdf_url); ?>#view=FitH" title="<?php echo esc_attr(get_the_title()); ?> PDF"></iframe><p><a href="<?php echo esc_url($pdf_url); ?>" target="_blank" rel="noopener">Open the full catalogue in a new tab</a></p></section><?php endif; ?>
    </div>
</main>
<?php endwhile; get_footer();
