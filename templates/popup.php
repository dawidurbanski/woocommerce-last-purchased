<?php if ( $this->last_purchased_time_ago() ): ?>

    <div class="wlp-popup">

        <div class="wlp-wrapper">

            <div class="wlp-popup-text">

                <span><?php echo $this->last_purchased_text(); ?></span>
                <time class="timeago" datetime="<?php echo $this->last_purchased_time_ago(); ?>"><?php echo $this->last_purchased_date('d-m-Y') ?></time>

            </div>

            <span class="wlp-popup-close"></span>

        </div>

    </div>

<?php endif; ?>