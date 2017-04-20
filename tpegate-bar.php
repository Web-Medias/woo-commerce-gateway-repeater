<?php
/*
Plugin Name: TPE Gate Bank Account Reference
Plugin URI: http://cms.web-medias.com/tpegate-bar
Description: This plugin allows to manage bank account parameters for Payment Gateways by a singular reference.
Version: 1.0
Author: Sébastien Brémond
Author URI: http://cms.web-medias.com/authors
License: GPL2
Text Domain: tpegate-bar
Domain Path: /languages
*/

/*
 * Copyright 2003-2017 Sébastien Brémond
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 */

// Exit if accessed directly.
if ( ! function_exists('add_action') || ! defined( 'ABSPATH' ) )
	exit();





/*
 * controls the plugin, as well as activation, and deactivation
 *
 * @since 0.1
 */
class twm_TPE_Gate_BankAccountReference {

    /**
     * Holds the class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;

    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * The name of the plugin.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $plugin_name = 'TPE Gateway Repeater';

    /**
     * Unique plugin slug identifier.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $plugin_slug = 'tpegate-bar';

    /**
     * Unique plugin post type identifier.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $plugin_post_type = 'tpegate-bar';

    /**
     * Plugin textdomain.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $domain = 'tpegate-bar';

    /**
     * Plugin file.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $file = __FILE__;

    public $stack;

    /**
     * Primary class constructor.
     *
     * @since 1.0.0
     */
	public function __construct() {

        register_activation_hook( __FILE__, array( $this, 'tpe_gate_bar_plugin_activate') );
        register_deactivation_hook( __FILE__, array( $this, 'tpe_gate_bar_plugin_deactivate') );

        // Fire a hook before the class is setup.
        do_action( $this->plugin_slug .'_pre_init' );


        // Load the plugin textdomain first !
        add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ), 2 );


        // Run hook once Plugin has been initialized.

        // Add post type and taxonomy first to be initialized before adding meta box...
        add_action( 'init', array( $this, 'tpe_gate_bar_plugin_post_type' ), 1 );


        // Removes the category, author, post excerpt, and slug meta boxes.
        add_action( 'default_hidden_meta_boxes', 'tpe_gate_bar_remove_meta_boxes', 10, 2 );

        // Build the Meta boxes system and set saving method.
        add_action( 'add_meta_boxes', array( $this, 'tpe_gate_bar_plugin_add_meta_box') );
        add_action( 'save_post', array( $this, 'tpe_gate_bar_plugin_save_meta_box_data') );


        // Add shortcode to embed into posts, pages,...
        add_shortcode( $this->plugin_post_type , array(&$this, 'tpe_gate_bar_plugin_add_shortcode'));

        // Load the plugin back-end behavior.
        add_action('admin_enqueue_scripts', array(&$this, 'tpe_gate_bar_plugin_admin_head'));

        // Load the needs for front-end.
        //add_action ('wp_enqueue_scripts', array(&$this, 'tpe_gate_bar_plugin_frontend_head'));


        // Change the default placeholder text of the global Post input title area.
        add_filter('enter_title_here', array(&$this, 'tpe_gate_bar_plugin_change_title_placeholder'));
        add_filter('edit_form_after_title', array(&$this, 'tpe_gate_bar_plugin_add_content_after_editor'));


        // Custom table column of Apps Post type to add some informations
        add_filter('manage_'.$this->plugin_post_type.'_posts_columns', array(&$this, 'tpe_gate_bar_plugin_columns_head'),  10);
        add_action('manage_'.$this->plugin_post_type.'_posts_custom_column', array(&$this, 'tpe_gate_bar_plugin_columns_content'), 10, 2);



        add_action('woocommerce_checkout_process', array(&$this, 'twm__wc_custom_checkout_validity_field_process'));
        add_action('woocommerce_after_order_notes', array(&$this, 'twm__wc_custom_checkout_validity_field'));
        add_action('woocommerce_available_payment_gateways', array(&$this, 'twm__change_wc_gateway_if_empty'), 9999, 1 );



        // Init somme stuffs finally...
        add_action( 'init', array($this, 'init') );

	}


    /**
     * Loads the plugin textdomain for translation.
     *
     * @since 1.0.0
     */
    public function load_plugin_textdomain() {

        $domain = $this->domain;
        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

        load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
        load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    }


    /**
     * Loads the plugin into WordPress.
     *
     * @since 1.0.0
     */
    public function init() {

        // Add special image size format used with the plugin | Cover
        /*
        add_image_size('top_story_splash_format', 960); 
        add_image_size('listed_thumbnail_format', 104, 70, true); 
        add_filter( 'image_size_names_choose', function( $sizes ) { return array_merge( $sizes, 
            array( 
                'top_story_splash_format' => __( 'News Top Story splash image...', $this->domain ) , 
                'listed_thumbnail_format' => __( 'News thumbnail image...', $this->domain ) 
            )
         ); } );
        /**/

        //remove_post_type_support( $this->plugin_post_type, 'excerpt' );
    }



    public function twm__wc_custom_checkout_validity_field_process() {

            wc_add_notice( __( 'ƒ : twm__wc_custom_checkout_validity_field_process', 'mavmshop' ), 'notice' );
    }



    public function twm__wc_custom_checkout_validity_field( $checkout ) {

            $available_gateways = WC()->payment_gateways->get_available_payment_gateways();

            wc_add_notice( __( 'ƒ : twm__wc_custom_checkout_validity_field', 'mavmshop' ) . '<pre>'. print_r($available_gateways,true) .'</pre>', 'notice' );


            if ( class_exists('WC_Paybox')) {

                //$ze_WC_Paybox = WC_Paybox::get_instance();
                //$my_WC_Paybox = new WC_Paybox();

            }

    }


    public function twm__change_wc_gateway_if_empty( $allowed_gateways ) {

            wc_add_notice( __( 'ƒ : twm__change_wc_gateway_if_empty', 'mavmshop' ). '<pre>'. print_r($allowed_gateways['paybox_std']->settings,true) .'</pre>', 'notice' );

            $allowed_gateways['paybox_std']->settings['site'] = '1234567';
            $allowed_gateways['paybox_std']->settings['rank'] = '02';
            $allowed_gateways['paybox_std']->settings['identifier'] = '987654321';

            return $allowed_gateways;
    }





    /**
     * Loads the plugin behavior.
     *
     * @since 1.0.0
     */
    public function tpe_gate_bar_plugin_admin_head() {

		// Enqueue jQuery UI CSS
		//
		// UI Style
		wp_enqueue_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/themes/smoothness/jquery-ui.css', true);
        wp_enqueue_script( 'jquery-ui-script', '//code.jquery.com/ui/1.11.4/jquery-ui.js' ); // Distant git from jQuery CDN


		// Runtime | Actions and Behaviours
		wp_enqueue_script( 'plugin-runtime-tpe_gate_bar', plugins_url('/core/js/plugin-runtime.js', __FILE__), array('jquery-ui-script'), null, false);
		wp_enqueue_style( 'plugin-runtime-tpe_gate_bar', plugins_url('/core/css/plugin-runtime.css', __FILE__), array(), null );

    }





    /**
     * Register Activation hook : Plugin Activate.
     */
    public function tpe_gate_bar_plugin_activate() {
        // register taxonomies/post types here
        flush_rewrite_rules();
    }


    /**
     * Register Activation hook : Plugin Desactivate.
     */
    public function tpe_gate_bar_plugin_deactivate() {
        // register taxonomies/post types here
        flush_rewrite_rules();
    }





    /**
     * Create the related custom post type for the plugin
     *
     * @since 1.0.0
     */
    public function tpe_gate_bar_plugin_post_type() {

        //register_taxonomy_for_object_type('category', $this->plugin_post_type ); // Register Taxonomies for Category

        //register_taxonomy_for_object_type('post_tag', $this->plugin_post_type ); // Register Taxonomies for Post tag
        register_post_type( $this->plugin_post_type , // Register Custom Post Type
            array(
            'labels'        => array(
                'name'                  => __('TPE Gateways', $this->domain), // Rename these to suit
                'singular_name'         => __('TPE Gateway', $this->domain),
                'add_new'               => __('Nouvelle', $this->domain),
                'add_new_item'          => __('Ajout d\'une nouvelle passerelle de paiement', $this->domain),
                'edit'                  => __('Modifier', $this->domain),
                'edit_item'             => __('Modifier une passerelle de paiement', $this->domain),
                'new_item'              => __('Nouvelle passerelle de paiement', $this->domain),
                'view'                  => __('Voir la passerelle de paiement', $this->domain),
                'view_item'             => __('Afficher', $this->domain),
                'search_items'          => __('Rechercher une passerelle de paiement', $this->domain),
                'not_found'             => __('Aucune passerelle de paiement trouvée', $this->domain),
                'not_found_in_trash'    => __('Aucune passerelle de paiement trouvée dans la corbeille', $this->domain)
            ),
            'public'        => true,
            'hierarchical'  => true, // Allows your posts to behave like Hierarchy Pages
            'has_archive'   => true,
            'supports'      => array(
                'title',
                'editor',
                'excerpt'
            ), // Go to Dashboard Custom H5B post for supports
            'can_export'    => true, // Allows export in Tools > Export
            'menu_icon'     => 'dashicons-tickets-alt',
            'menu_position' => 41,
            'show_ui'       => true,
            'query_var'     => $this->plugin_post_type,
            'rewrite'       => array(
                'slug'                => $this->plugin_post_type,
                'with_front'          => true,
                'pages'               => true,
                'feeds'               => true,
            ),
            'taxonomies'    => array(
            /*
                'post_tag',
                'category',
                'other_taxonomy'
            /**/
            ) // Add Category and Post Tags support
        ));





        // Add admin side bar menu taxonomy
        $labels_taxonomy = array(
            'name'              => _x( $this->plugin_name_taxonomy['plural'], $this->domain ),
            'singular_name'     => _x( $this->plugin_name_taxonomy['singular'], $this->domain ),
            'search_items'      => __( 'Rechercher', $this->domain ),
            'all_items'         => __( 'Toutes', $this->domain ),
            'parent_item'       => __( 'Parent', $this->domain ),
            'parent_item_colon' => __( 'Parent:', $this->domain ),
            'edit_item'         => __( 'Edition', $this->domain ),
            'update_item'       => __( 'Mettre à jour', $this->domain ),
            'add_new_item'      => __( 'Nouvelle', $this->domain ),
            'new_item_name'     => __( 'Nouveau nom', $this->domain ),
            'menu_name'         => __( $this->plugin_name_taxonomy['plural'] ),
        );

        $args_taxonomy = array(
            'hierarchical'      => true,
            'labels'            => $labels_taxonomy,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'other_taxonomy' ),
        );

        //register_taxonomy( 'dtd_swipe_page_taxonomy', array( $this->plugin_post_type ), $args_taxonomy );

    }





    /**
     * Custom the title placeholder
     *
     * @since 1.0.0
     */
    public function tpe_gate_bar_plugin_change_title_placeholder($placeholder_title) {

        $screen = get_current_screen();
        
        if( $this->plugin_post_type == $screen->post_type ){
            $placeholder_title = __('Nom/Libellé de la banque associée', $this->domain);
        }

        return $placeholder_title;

    }



    /**
     * Adds a custom column header to the posttype table list.
     */
    public function tpe_gate_bar_plugin_columns_head($defaults) {
        $defaults['title'] = 'Libellé de la passerelle';
        $defaults['app_enabled'] = 'Etat';
        $defaults['id_zone'] = 'Id (zone)';
        $defaults['pbx_site'] = 'PBX Site';
        unset( $defaults['language'] );
        unset( $defaults['date'] );
        return $defaults;
    }


    /**
     * Adds (and fill) a custom column cell to the posttype table list.
     */
    public function tpe_gate_bar_plugin_columns_content($column_name, $post_ID) {

        if ($column_name == 'app_enabled') {

            $_tpe_gate_bar__enabled = get_post_meta( $post_ID, '_tpe_gate_bar__enabled', true );

            if( 1 == intval($_tpe_gate_bar__enabled) ){
                echo '<span class="dashicons-before dashicons-yes" style="color:#0688A2;"></span>Active';
            }else{
                echo '<span class="dashicons-before dashicons-no-alt" style="color:#A23D06;"></span> <em>(désactivée)</em>';
            }

        }

        if ($column_name == 'id_zone') {

            $_tpe_gate_bar__id = get_post_meta( $post_ID, '_tpe_gate_bar__id', true );

            if( 0 == intval($_tpe_gate_bar__id) || '' == trim($_tpe_gate_bar__id) ){
                echo '<span class="dashicons-before dashicons-no-alt" style="color:#A23D06;"></span>n.c.';
            }else{
                echo '<span class="" style="color:#0688A2;">'.$_tpe_gate_bar__id.'</span>';
            }

        }


        if ($column_name == 'pbx_site') {

            $_tpe_gate_bar__pbx_site = get_post_meta( $post_ID, '_tpe_gate_bar__pbx_site', true );

            if( 0 == intval($_tpe_gate_bar__pbx_site) || '' == trim($_tpe_gate_bar__pbx_site) ){
                echo '<span class="dashicons-before dashicons-no-alt" style="color:#A23D06;"></span>n.c.';
            }else{
                echo '<span class="" style="color:#0688A2;">'.$_tpe_gate_bar__pbx_site.'</span>';
            }

        }

    }




    /**
     * Removes the category, author, post excerpt, and slug meta boxes.
     *
     * @since    1.0.0
     *
     * @param    array    $hidden    The array of meta boxes that should be hidden for Acme Post Types
     * @param    object   $screen    The current screen object that's being displayed on the screen
     * @return   array    $hidden    The updated array that removes other meta boxes
     */
    function tpe_gate_bar_remove_meta_boxes( $hidden, $screen ) {

        $screens = array( $this->plugin_post_type ); // Allows to run with this

        foreach ( $screens as $screen ) {

            
            $hidden = array(
                'postexcerpt',
                'slugdiv'
                );
            /**/
            
        }
        return $hidden;
        
    }



    /**
     * Adds a box to the main column on the Post and Page edit screens.
     */
    public function tpe_gate_bar_plugin_add_meta_box($postType) {

		$screens = array( $this->plugin_post_type ); // Allows to run with this


		/* Special constraint !
		 * It allows to add a Meta Box only for pages using a specific template page
		 * This plugin has been built only to run with this a custom post type...
		 */
		//if ( get_page_template_slug($post->ID) != 'gateways.php' )
		//	return;

		foreach ( $screens as $screen ) {

		/*  // Memo !
		 *	add_meta_box( $id, $title, $callback, $screen, $context, $priority, $callback_args );
		 *	@param $screen ('post', 'page', 'dashboard', 'link', 'attachment' or 'custom_post_type-slug')
		 *  @param $context ('normal', 'advanced', or 'side')
		 *  @param $priority ('high', 'core', 'default' or 'low')
		 */

			add_meta_box(
				'tpe_gate_bar_plugin_sectionid', 
				'<strong style="display:block; color:#0c7cb6; font-size:23px;">'.$this->plugin_name .'</strong>' . __( 'Définition des informations caractérisant cette passerelle...', $this->domain ),
				array( $this, 'tpe_gate_bar_plugin_meta_box_callback' ),
				$screen, 
                'advanced', 
                'core'
			);

		}

	}




    /**
     * Adds a postbox after app title / before content editor.
     */
    public function tpe_gate_bar_plugin_add_content_after_editor() {
    }




	/**
	 * Prints the box content.
	 * 
	 * @param WP_Post $post The object for the current post/page.
	 */
	public function tpe_gate_bar_plugin_meta_box_callback( $post ) {

		// Add a nonce field so we can check for it later.
		wp_nonce_field( 'tpe_gate_bar_plugin_meta_box', 'tpe_gate_bar_plugin_meta_box_nonce' );

		/*
		 * Use get_post_meta() to retrieve an existing value
		 * from the database and use the value for the form.
		 */
        ?>


        <!-- WP Admin Meta boxes : CSS Hack -->
        <style type="text/css">
            #postdivrich, #postexcerpt{ display: none!important;/**/ }
        </style>


        <?php
        // Custom fieldset data elements |» Entry activation
        //
        ?>
        <fieldset class="tmg">
            <legend><strong class="dashicons-before dashicons-yes"><?php _e( 'Activation', $this->domain ); ?></strong></legend>

            <?php
            // Related field | Post enabling
            //  _tpe_gate_bar__enabled
            $_tpe_gate_bar__enabled = get_post_meta( $post->ID, '_tpe_gate_bar__enabled', true );
            ?>            
            <label class="inline_label"><input type="checkbox" <?php echo $this->is_checked( $_tpe_gate_bar__enabled, '1' ); ?> name="_tpe_gate_bar__enabled" value="1" /><?php _e('Activer cette entrée afin de la rendre fonctionnelle et disponible lors du paiement ?', $this->domain); ?></label>
            <br class="clear" />
            <em class="inline_label">
                <?php _e('Si cette icône est cochée, cette entrée sera fonctionnelle et disponible lors du paiement.', $this->domain); ?>
                <br class="clear" />
                <?php _e('Astuce : Vous pouvez la laisser non active (décochée) afin de vous en servir par la suite et la laisser en attente... Il suffira simplement de l\'activer au moment voulu.', $this->domain); ?>
            </em>
            <br class="clear" />

        </fieldset>
        <br class="clear" />



        <fieldset class="tmg">
            <legend><strong class="dashicons-before dashicons-external"><?php _e( 'Identificateur d\'origine', $this->domain ); ?></strong></legend>

            <?php
            // Related field | id
            //  _tpe_gate_bar__id
            ?>
            <label class="inline_label" for="_tpe_gate_bar__id">
            <?php _e( '#Id :', $this->domain ); ?>
            </label> 
            
            <input class="field" type="text" id="_tpe_gate_bar__id" name="_tpe_gate_bar__id" size="2" value="<?php echo get_post_meta( $post->ID, '_tpe_gate_bar__id', true ); ?>" />
            <br class="clear" />
            <em class="inline_label">
                <?php _e('Cet identificateur permet d\'ossocier l\'origine (#Id zone) de la demande de don.', $this->domain); ?>
            </em>
            <br class="clear" />

        </fieldset>
        <br class="clear" />



        <?php
        // Custom fieldset data elements |» Paramètres
        //
        ?>
        <fieldset class="tmg">
            <legend><strong class="dashicons-before dashicons-edit"><?php _e( 'Paramètres de la passerelle de paiement', $this->domain ); ?></strong></legend>

            <?php
            // Related field | pbx_site
            //  _tpe_gate_bar__pbx_site
            ?>
            <label class="inline_label" for="_tpe_gate_bar__pbx_site">
            <?php _e( '#pbx_site :', $this->domain ); ?>
            </label> 
            
            <input class="field" type="text" id="_tpe_gate_bar__pbx_site" name="_tpe_gate_bar__pbx_site" size="6" value="<?php echo get_post_meta( $post->ID, '_tpe_gate_bar__pbx_site', true ); ?>" />
            <br class="clear" />


            <?php
            // Related field | pbx_rang
            //  _tpe_gate_bar__pbx_rang
            ?>
            <label class="inline_label" for="_tpe_gate_bar__pbx_rang">
            <?php _e( '#pbx_rang :', $this->domain ); ?>
            </label> 
            
            <input class="field" type="text" id="_tpe_gate_bar__pbx_rang" name="_tpe_gate_bar__pbx_rang" size="2" value="<?php echo get_post_meta( $post->ID, '_tpe_gate_bar__pbx_rang', true ); ?>" />
            <br class="clear" />


            <?php
            // Related field | pbx_identifiant
            //  _tpe_gate_bar__pbx_identifiant
            ?>
            <label class="inline_label" for="_tpe_gate_bar__pbx_identifiant">
            <?php _e( '#pbx_identifiant :', $this->domain ); ?>
            </label> 
            
            <input class="field" type="text" id="_tpe_gate_bar__pbx_identifiant" name="_tpe_gate_bar__pbx_identifiant" size="10" value="<?php echo get_post_meta( $post->ID, '_tpe_gate_bar__pbx_identifiant', true ); ?>" />
            <br class="clear" />


            <?php
            // Related field | pbx_hmac
            //  _tpe_gate_bar__pbx_hmac
            ?>
            <label class="inline_label" for="_tpe_gate_bar__pbx_hmac">
            <?php _e( '#pbx_hmac :', $this->domain ); ?>
            </label> 
            
            <input class="field" type="text" id="_tpe_gate_bar__pbx_hmac" name="_tpe_gate_bar__pbx_hmac" size="18" value="<?php echo get_post_meta( $post->ID, '_tpe_gate_bar__pbx_hmac', true ); ?>" />
            <br class="clear" />


        </fieldset>
        <br class="clear" />


        <?php
        // Custom section data actions | #7 (Updating post data)
        //
        ?>
        <style>#minor-attachment-actions{ padding: 10px; clear: both; border-top: 1px solid #ddd; background: #f5f5f5; position: relative; margin: -12px; }</style>
        <div id="minor-attachment-actions">
            <div id="add-pdf-attachment-action">
                <?php
                $update_custom_plugin_button = ' disabled="disabled" ';
                // Ensure that this post has well been published at least the first time...
                if( isset( $_GET['post'] ) && $_GET['post'] != "" && intval($_GET['post']) != 0 ) {

                    // And check that the current post id is the same in the admin query.
                    if( intval($_GET['post']) == intval($post->ID) ) {
                        $update_custom_plugin_button = ''; // Allows the form submitting.
                    }
                }
                ?>
                <input name="save" type="submit" <?php echo $update_custom_plugin_button; ?> class="button button-primary button-large" id="publish" value="<?php _e( 'Mettre à jour...', $this->domain ); ?>">
            </div>
            <div class="clear"></div>
            
        </div>


        <?php
        // End print meta box

	}




    /**
     * Check if reference equals to the value, and return selected if verified, empty otherwise.
     *
     * @param reference, string
     * @param value, string
     * @param reference, boolean
     */
    public function is_item_selected( $reference, $value, $default ) {
        $verified = $default || ($value==$reference);
        return ( ($verified)? 'selected="selected" style="background-color:lightgray;"':'' );
    }



    /**
     * Check if reference equals to the value, and return checked if verified, empty otherwise.
     *
     * @param reference, string
     * @param value, string
     */
    public function is_checked($cible,$test){
        $verified = ($cible==$test);
        return ( ($verified)? ' checked="checked" ':'' );
    }



    /**
     * Get all Swipes stored and return a coolection.
     *
     * @param none
     */
    public function get_swipes_posttype_storedin() {

        $_swipes_collection = array();

        // Ensure that the DTD Swipes classe has been properly installed.
        if ( class_exists('DTD_Swipes')) {
            $dtd_swipes = DTD_Swipes::get_instance();
            $plugin_post_type = $dtd_swipes->plugin_post_type;

            $args = array( 
                'post_type' => array( $plugin_post_type ), 
                'posts_per_page' => -1,
                'post_status' => 'publish',
                /*
                'meta_query'            => array( // Class reference for meta value
                    array(
                        'key'           => '_dtd_swipes__enabled',
                        'value'         => '1'
                    )
                ),
                /**/
                'orderby'               => 'menu_order',
                'order'                 => 'ASC'
            );
            $loop_swipes = new WP_Query( $args );

            foreach ( $loop_swipes->posts as $swipe) {
                
                $_swipes_collection[] = array(
                    'ID'          => $swipe->ID,
                    'guid'          => $swipe->guid,
                    'post_title'    => $swipe->post_title,
                    '_dtd_swipes__defaultdisplayed'          => get_post_meta( $swipe->ID, '_dtd_swipes__defaultdisplayed', true ),
                    '_dtd_swipes__relatedformat'          => get_post_meta( $swipe->ID, '_dtd_swipes__relatedformat', true ),
                    '_dtd_swipes__enabled'          => get_post_meta( $swipe->ID, '_dtd_swipes__enabled', true )
                );

            }

        }

        return $_swipes_collection;
    }



    /**
     * Return a collection of elements depending on a data provider.
     *
     * @param reference, string
     * @param value, string
     */
    public function get_data_provider_collection($provider){
        $data_result = array();
        $className = twm_TPE_Gate_BankAccountReference::get_instance();
        $data_result = $className->{$provider}();

        return $data_result;

    }



    /**
     * Get all pdf from media library.
     *
     * @param none
     */
    public function get_documents_from_media_library() {

        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' =>'application/pdf',
            'post_status' => 'inherit',
            'posts_per_page' => -1
        );
        $query_documents = new WP_Query( $args );
        $document_coll = array();
        foreach ( $query_documents->posts as $document) {
            $document_coll[]= array('guid'=>$document->guid, 'post_title'=>$document->post_title);
        }

        return $document_coll;
    }



    /**
     * Get all page from WP Theme.
     *
     * @param none
     */
    
    public function get_pages_from_wp_theme() {

        // Do not pick pages having templates :
        // - <launch.php>, ...
        // TODO, build an admin panel to configure them from WP_Admin ;)
        $rejected_templates = array( '_launch.php', 'system.php', '_video-content.php' );

        $args = array(
            'sort_order' => 'asc',
            'sort_column' => 'post_title',
            'hierarchical' => 1,
            'exclude' => '',
            'include' => '',
            'meta_key' => '',
            'meta_value' => '',
            'authors' => '',
            'child_of' => 0,
            'parent' => -1,
            'exclude_tree' => '',
            'number' => '',
            'offset' => 0,
            'post_type' => 'page',
            'post_status' => 'publish'
        );
        $query_documents = new WP_Query( $args );
        $document_coll = array();
        foreach ( $query_documents->posts as $document) {

            // Get the template name page..
            $template_file = get_post_meta( $document->ID, '_wp_page_template', TRUE );

            // Pick this page if template name is not in haystack.
            if( !in_array( $template_file, $rejected_templates ) ){

                $document_coll[]= array('guid'=>$document->ID, 'post_title'=>$document->post_title );

            }

        }

        return $document_coll;
    }
    /**/



    /**
     * Get all page from WP Theme.
     *
     * @param none
     */
    
    public function get_posts_from_wp_theme() {

        $args = array(
            'posts_per_page'   => -1,
            'offset'           => 0,
            'category'         => '',
            'category_name'    => '',
            'orderby'          => 'date',
            'order'            => 'DESC',
            'include'          => '',
            'exclude'          => '',
            'meta_key'         => '',
            'meta_value'       => '',
            'post_type'        => 'post',
            'post_mime_type'   => '',
            'post_parent'      => '',
            'author'       => '',
            'post_status'      => 'publish',
            'suppress_filters' => true 
        );
        $query_documents = new WP_Query( $args );
        $document_coll = array();
        foreach ( $query_documents->posts as $document) {
            $document_coll[]= array('guid'=>$document->ID, 'post_title'=>$document->post_title);
        }

        return $document_coll;
    }




    /**
     * Get all video from WP Theme.
     *
     * @param none
     */
    
    public function get_video_from_wp_theme() {

        $args = array(
          'post_type' => 'attachment',
          'numberposts' => -1,
          'post_status' => null,
          'post_parent' => null, // any parent
          'post_mime_type' => 'video'
        ); 

        $query_documents = get_posts( $args );
        $document_coll = array();
        if ( $query_documents ) {
            foreach ( $query_documents as $document) {
                $document_coll[]= array('guid'=>$document->ID, 'post_title'=>$document->post_title);
            }
        }
        return $document_coll;
    }
    /**/




    /**
     * Get all video from WP Theme.
     *
     * @param none
     */
    
    public function get_media_from_wp_theme() {

        // Get the Envira_Gallery_Lite instance and retreive all stored galleries in WordPress.
        $galleries = Envira_Gallery_Lite::_get_galleries();

        $document_coll = array();

        foreach( (array) $galleries as $gallery ) {
            $document_coll[]= array('guid'=>$gallery['id'], 'post_title'=>$gallery['config']['title'] .' ('.count((array) $gallery['gallery']).' éléments)' );
        }


        return $document_coll;
    }
    /**/






	/**
	 * When the post is saved, saves our custom data.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function tpe_gate_bar_plugin_save_meta_box_data( $post_id ) {

		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */
		// Check if our nonce is set.
		if ( ! isset( $_POST['tpe_gate_bar_plugin_meta_box_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['tpe_gate_bar_plugin_meta_box_nonce'], 'tpe_gate_bar_plugin_meta_box' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'post' == $_POST['post_type'] )  {

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

		} else {

			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}

		}

		/* OK, it's safe for us to save the data now. */




		// And now...
        //
		// Save values into WP db

		// Sanitize user input.
            //  _tpe_gate_bar__enabled
            //  _tpe_gate_bar__id
            //  _tpe_gate_bar__pbx_site
            //  _tpe_gate_bar__pbx_rang
            //  _tpe_gate_bar__pbx_identifiant
            //  _tpe_gate_bar__pbx_hmac

		$save_dbarray = array(
            '_tpe_gate_bar__enabled'            => sanitize_text_field( $_POST['_tpe_gate_bar__enabled'] ),
            '_tpe_gate_bar__id'                 => sanitize_text_field( $_POST['_tpe_gate_bar__id'] ),
            '_tpe_gate_bar__pbx_site'           => sanitize_text_field( $_POST['_tpe_gate_bar__pbx_site'] ),
            '_tpe_gate_bar__pbx_rang'           => sanitize_text_field( $_POST['_tpe_gate_bar__pbx_rang'] ),
            '_tpe_gate_bar__pbx_identifiant'    => sanitize_text_field( $_POST['_tpe_gate_bar__pbx_identifiant'] ),
            '_tpe_gate_bar__pbx_hmac'           => sanitize_text_field( $_POST['_tpe_gate_bar__pbx_hmac'] )
		);

		//Save values from created array into db
		// Update the meta field in the database.
		foreach($save_dbarray as $meta_key=>$meta_value) {
		
			delete_post_meta($post_id, $meta_key);
			update_post_meta($post_id, $meta_key, $meta_value);

		}

	}






    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return object The twm_TPE_Gate_BankAccountReference object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof twm_TPE_Gate_BankAccountReference ) ) {
            self::$instance = new twm_TPE_Gate_BankAccountReference();
        }

        return self::$instance;

    }

}

// Load the main plugin class.
$twm_tpe_gate_bar = twm_TPE_Gate_BankAccountReference::get_instance();
