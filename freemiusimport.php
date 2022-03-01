<?php
if ( ! function_exists( 'npt_fs' ) ) {
    // Create a helper function for easy SDK access.
    function npt_fs() {
        global $npt_fs;

        if ( ! isset( $npt_fs ) ) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/freemius/start.php';

            $npt_fs = fs_dynamic_init( array(
                'id'                  => '3508',
                'slug'                => 'nova-poshta-ttn',
                'type'                => 'plugin',
                'public_key'          => 'pk_0fdf5d9273b8c379b218e2f2e38d4',
                'is_premium'          => false,
                'has_addons'          => false,
                'has_paid_plans'      => false,
                'menu'                => array(
                    'slug'           => 'morkvanp_plugin',
                    'first-path'     => 'admin.php?page=morkvanp_plugin',
                    'account'        => false,
                    'contact'        => false,
                    'support'        => false,
                ),
            ) );
        }

        return $npt_fs;
    }

    // Init Freemius.
    npt_fs();
    // Signal that SDK was initiated.
    do_action( 'npt_fs_loaded' );
}
