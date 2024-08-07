</div><!-- #content -->

<footer id="colophon" class="site-footer" role="contentinfo">
    <div class="site-info">
        <?php
        if ( function_exists( 'the_privacy_policy_link' ) ) {
            the_privacy_policy_link( '', '<span role="separator" aria-hidden="true"></span>' );
        }
        ?>
        <a href="<?php echo esc_url( __( 'https://wordpress.org/', 'intentionally-blank' ) ); ?>">
            <?php printf( __( 'Proudly powered by %s', 'intentionally-blank' ), 'WordPress' ); ?>
        </a>
    </div><!-- .site-info -->
</footer><!-- #colophon -->
<?php wp_footer(); ?>
</body>
</html>