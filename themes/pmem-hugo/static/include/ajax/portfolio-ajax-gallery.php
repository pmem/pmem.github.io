<?php

$nextid = isset( $_POST['portnext'] ) ? $_POST['portnext'] : '';
$previd = isset( $_POST['portprev'] ) ? $_POST['portprev'] : '';
$postid = isset( $_POST['portid'] ) ? $_POST['portid'] : '';

?>

<div id="portfolio-ajax-single" class="clearfix">

    <div id="portfolio-ajax-title" style="position: relative;">
        <h2>Single Item with Thumbs</h2>
        <div id="portfolio-navigation">
            <?php if( $previd ){ ?><a href="#" id="prev-portfolio" data-id="<?php echo $previd; ?>"><i class="icon-angle-left"></i></a><?php } ?>
            <?php if( $nextid ){ ?><a href="#" id="next-portfolio" data-id="<?php echo $nextid; ?>"><i class="icon-angle-right"></i></a><?php } ?>
            <a href="#" id="close-portfolio"><i class="icon-line-cross"></i></a>
        </div>
    </div>

    <div class="line line-sm topmargin-sm"></div>

    <div class="row col-mb-50">
        <!-- Portfolio Single Gallery
        ============================================= -->
        <div class="col-12 portfolio-single-image">
            <div class="masonry-thumbs grid-container grid-5" data-big="3" data-lightbox="gallery">
                <a class="grid-item" href="images/portfolio/full/1.jpg" data-lightbox="gallery-item"><img src="images/portfolio/3/1.jpg" alt="Gallery Thumb 1"></a>
                <a class="grid-item" href="images/portfolio/full/2.jpg" data-lightbox="gallery-item"><img src="images/portfolio/3/2.jpg" alt="Gallery Thumb 2"></a>
                <a class="grid-item" href="images/portfolio/full/3.jpg" data-lightbox="gallery-item"><img src="images/portfolio/3/3.jpg" alt="Gallery Thumb 3"></a>
                <a class="grid-item" href="images/portfolio/full/4.jpg" data-lightbox="gallery-item"><img src="images/portfolio/3/4.jpg" alt="Gallery Thumb 4"></a>
                <a class="grid-item" href="images/portfolio/full/5.jpg" data-lightbox="gallery-item"><img src="images/portfolio/3/5.jpg" alt="Gallery Thumb 5"></a>
                <a class="grid-item" href="images/portfolio/full/6.jpg" data-lightbox="gallery-item"><img src="images/portfolio/3/6.jpg" alt="Gallery Thumb 6"></a>
                <a class="grid-item" href="images/portfolio/full/7.jpg" data-lightbox="gallery-item"><img src="images/portfolio/3/7.jpg" alt="Gallery Thumb 7"></a>
            </div>
        </div><!-- .portfolio-single-image end -->

        <div class="col-md-4">
            <!-- Portfolio Single - Meta
            ============================================= -->
            <div class="card event-meta">
                <div class="card-body">
                    <ul class="portfolio-meta mb-0">
                        <li><span><i class="icon-user"></i>Created by:</span> John Doe</li>
                        <li><span><i class="icon-calendar3"></i>Completed on:</span> 17th March 2021</li>
                        <li><span><i class="icon-lightbulb"></i>Skills:</span> HTML5 / PHP / CSS3</li>
                        <li><span><i class="icon-link"></i>Client:</span> <a href="#">Google</a></li>
                    </ul>
                </div>
            </div>
            <!-- Portfolio Single - Meta End -->

            <!-- Portfolio Single - Share
            ============================================= -->
            <div class="si-share d-flex justify-content-between align-items-center mt-4">
                <span>Share:</span>
                <div>
                    <a href="#" class="social-icon si-borderless si-facebook">
                        <i class="icon-facebook"></i>
                        <i class="icon-facebook"></i>
                    </a>
                    <a href="#" class="social-icon si-borderless si-twitter">
                        <i class="icon-twitter"></i>
                        <i class="icon-twitter"></i>
                    </a>
                    <a href="#" class="social-icon si-borderless si-pinterest">
                        <i class="icon-pinterest"></i>
                        <i class="icon-pinterest"></i>
                    </a>
                    <a href="#" class="social-icon si-borderless si-gplus">
                        <i class="icon-gplus"></i>
                        <i class="icon-gplus"></i>
                    </a>
                    <a href="#" class="social-icon si-borderless si-rss">
                        <i class="icon-rss"></i>
                        <i class="icon-rss"></i>
                    </a>
                    <a href="#" class="social-icon si-borderless si-email3">
                        <i class="icon-email3"></i>
                        <i class="icon-email3"></i>
                    </a>
                </div>
            </div>
            <!-- Portfolio Single - Share End -->
        </div>

        <!-- Portfolio Single Content
        ============================================= -->
        <div class="col-md-8 portfolio-single-content">
            <!-- Portfolio Single - Description
            ============================================= -->
            <div class="row col-mb-30">
                <div class="col-sm-6 col-md-12 col-lg-6">
                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aliquam, labore deserunt ex cupiditate est blanditiis dignissimos nesciunt doloremque laboriosam ullam necessitatibus voluptatum tempora itaque quia porro voluptates quo excepturi veritatis!</p>
                </div>

                <div class="col-sm-6 col-md-12 col-lg-6">
                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Voluptatem, sed.</p>
                    <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Nemo, soluta explicabo sunt aliquam officiis autem.</p>
                </div>
            </div>
            <!-- Portfolio Single - Description End -->
        </div><!-- .portfolio-single-content end -->
    </div>

</div>