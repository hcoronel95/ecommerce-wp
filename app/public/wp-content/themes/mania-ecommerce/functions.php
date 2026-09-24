<?php
add_action( 'wp_enqueue_scripts', 'mania_ecommerce_style',100 );
			function mania_ecommerce_style() {
					// wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
				$themeVersion = wp_get_theme()->get('Version');
					wp_enqueue_style( 'mania-ecommerce-child-style', get_stylesheet_directory_uri() . '/style.css', array(), $themeVersion);
					wp_add_inline_style('mania-ecommerce-child-style', mania_ecommerce_custom_styles());
}

		add_action( 'init', function() {
		    if ( current_user_can( 'manage_options' ) ) {
		        update_option( 'woocommerce_catalog_columns', 5 );
		    }
		} );

function mania_ecommerce_setting( $wp_customize ){
	// theme color
	 $wp_customize->add_setting('th_shop_mania_theme_clr', array(
	        'default'        => '#1945ef',
	        'capability'     => 'edit_theme_options',
	        'sanitize_callback' => 'th_shop_mania_sanitize_color',
	        'transport'         => 'postMessage',
	    ));
	$wp_customize->add_control( 
	    new WP_Customize_Color_Control($wp_customize,'th_shop_mania_theme_clr', array(
	        'label'      => __('Theme Color', 'mania-ecommerce' ),
	        'section'    => 'th-shop-mania-gloabal-color',
	        'settings'   => 'th_shop_mania_theme_clr',
	        'priority' => 1,
	    ) ) 
	 );

	$wp_customize->add_setting('th_shop_mania_woo_quickview_enable', array(
                'default'               => true,
                'sanitize_callback'     => 'th_shop_mania_sanitize_checkbox',
            ) );
	$wp_customize->add_control( new WP_Customize_Control( $wp_customize,'th_shop_mania_woo_quickview_enable', array(
                'label'         => esc_html__('Enable Quick View.', 'mania-ecommerce'),
                'type'          => 'checkbox',
                'section'       => 'woocommerce_product_catalog',
                'settings'      => 'th_shop_mania_woo_quickview_enable',
            ) ) );

			// logo width
		if ( class_exists( 'Th_Shop_Mania_WP_Customizer_Range_Value_Control' ) && !function_exists('th_shop_mania_pro_load_plugin') ){
		$wp_customize->add_setting(
		            'th_shop_mania_logo_width', array(
		                'sanitize_callback' => 'th_shop_mania_sanitize_range_value',
		                'default' => '',
		                'transport'         => 'postMessage',
		                
		            ));
		$wp_customize->add_control(
		            new Th_Shop_Mania_WP_Customizer_Range_Value_Control(
		                $wp_customize, 'th_shop_mania_logo_width', array(
		                    'label'       => esc_html__( 'Logo Width', 'mania-ecommerce' ),
		                    'section'     => 'title_tagline',
		                    'priority'       => 9,
		                    'type'        => 'range-value',
		                    'input_attr'  => array(
		                        'min'  => 10,
		                        'max'  => 600,
		                        'step' => 1,
		                    ),
		                    'media_query' => false,
		                    'sum_type'    => true,
		                )
		        )
		);
		}
}
add_action( 'customize_register', 'mania_ecommerce_setting', 100 );



function mania_ecommerce_custom_styles(){
	$th_shop_mania_style = ""; 

	$th_shop_mania_logo_width = esc_html(get_theme_mod('th_shop_mania_logo_width',''));

	$th_shop_mania_style.=".thunk-logo img,.sticky-header .logo-content img{
	    max-width:{$th_shop_mania_logo_width}px;
	}";

	return $th_shop_mania_style;

}