<?php

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (! class_exists('WC_Loyaltybox_Settings')) :

/**
 * Loyaltybox Settings.
 */
class WC_Loyaltybox_Settings
{
    const OPT_NAME = 'wc_loyaltybox_options';
    
    const OPT_DB_VERSION_NAME = 'loyaltybox_db_version';
    
    const OPT_TABLE_ACCOUNT = 'loyaltybox_accounts';
    
    const OPT_DB_VERSION = '1';

    /**
     * @var bool
     */
    protected $updated = false;
    
    /**
     * @var null
     */
    protected $errors = null;
    
    /**
     * @var string
     */
    protected $message = '';
    
    /**
     * @var null
     */
    private $opt = null;

    public function __construct()
    {
        $this->install();
        // Hooks
        // Add loyaltybox sub-menu in woocommerce menu
        register_activation_hook(__FILE__,                                     array($this, 'install'));
        add_action('plugin_loaded',                                            array($this, 'install'));
        add_action('admin_notices',                                            array($this, 'admin_notice'));
        // This will add left side bar 
        //add_action( 'admin_menu',                                               array($this, 'admin_menu'),     100);
        add_action('wp_loaded',                                                array($this, 'form_post_handler' ), 30);
        add_filter('woocommerce_settings_tabs_array', __CLASS__.'::add_settings_tab', 50);
        add_action('woocommerce_settings_tabs_settings_tab_loyaltybox', __CLASS__.'::settings_tab');
        add_action('woocommerce_update_options_settings_tab_loyaltybox', array($this, 'update_settings'));
        $this->opt = get_option(self::OPT_NAME);
    }

    /**
    * add_settings_tab.
    *
    * Add a new settings tab to the WooCommerce settings tabs array.
    *
    * @version 1.0.0
    *
    * @author Double Eye
    *
    * @since 1.0.0
    * @access public
    *
    * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
    *
    * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
    * @static
    */
    public static function add_settings_tab($settings_tabs)
    {
        $settings_tabs['settings_tab_loyaltybox'] = __('Loyalty Box', 'woocommerce-settings-tab-loyaltybox');

        return $settings_tabs;
    }

    /**
    * settings_tab.
    *
    * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
    *
    * @version 1.0.0
    *
    * @author Double Eye
    *
    * @since 1.0.0
    * @access public
    *
    * @uses woocommerce_admin_fields()
    * @uses self::get_settings()
    *
    * @static
    */
    public static function settings_tab()
    {
        woocommerce_admin_fields(self::get_settings());
    }

    /**
    * update_settings_loyaltybox.
    *
    * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
    *
    * @version 1.0.0
    *
    * @author Double Eye
    *
    * @since 1.0.0
    * @access public
    *
    * @uses woocommerce_update_options()
    * @uses self::get_settings()
    *
    * @static
    */
    public static function update_settings_loyaltybox()
    {
        woocommerce_update_options(self::get_settings());
    }

    /**
     * update_settings.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     */
    public function update_settings()
    {
        $this->errors = array();
        if (isset($_POST['wc_settings_tab_loyaltybox_client_id']) 
                && isset($_POST['wc_settings_tab_loyaltybox_location_id'])) 
        {
            if (!$_POST['wc_settings_tab_loyaltybox_reward_prog_name']
                     || !$_POST['wc_settings_tab_loyaltybox_client_id']
                     || !$_POST['wc_settings_tab_loyaltybox_location_id']
                     || !$_POST['wc_settings_tab_loyaltybox_api_username']
                     || !$_POST['wc_settings_tab_loyaltybox_api_password']) {
                $this->errors[] = __('Loyaltybox Reward Programme Name is mandatory.', 'woocommerce-loyaltybox-admin');
                $this->errors[] = __('Loyaltybox Client Id is mandatory.', 'woocommerce-loyaltybox-admin');
                $this->errors[] = __('Loyaltybox Location Id is mandatory.', 'woocommerce-loyaltybox-admin');
                $this->errors[] = __('Loyaltybox API Username is mandatory.', 'woocommerce-loyaltybox-admin');
                $this->errors[] = __('Loyaltybox API Password is mandatory.', 'woocommerce-loyaltybox-admin');
            }

            $reward_programme_name = trim($_POST['wc_settings_tab_loyaltybox_reward_prog_name']);
            $client_id = trim($_POST['wc_settings_tab_loyaltybox_client_id']);
            $location_id = trim($_POST['wc_settings_tab_loyaltybox_location_id']);
            $api_username = trim($_POST['wc_settings_tab_loyaltybox_api_username']);
            $api_password = trim($_POST['wc_settings_tab_loyaltybox_api_password']);

                try {
                    $this->opt['reward_programme_name'] = $reward_programme_name;
                    $this->opt['client_id'] = $client_id;
                    $this->opt['location_id'] = $location_id;
                    $this->opt['api_username'] = $api_username;
                    $this->opt['api_password'] = $api_password;
                } catch (LoyaltyboxException $e) {
                    //echo "in submit";echo $e->getMessage();die;
                    Loyaltybox::logError($e->getMessage());
                    $this->errors[] = $e->getMessage();
                }
        }
        if (isset($_POST['wc_settings_tab_loyaltybox_friendlymessage'])) {
            $this->opt['friendly_message'] = $_POST['wc_settings_tab_loyaltybox_friendlymessage'];
        }
        
        if (empty($this->errors)) {
            update_option(self::OPT_NAME, $this->opt);
            woocommerce_update_options(self::get_settings());
            Loyaltybox::debug_log('Settings updated');
        }
            
    }

    /**
     * get_settings.
     *
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     *
     * @return mixed
     * @static
     */
    public static function get_settings()
    {
        $settings = array(
                'section_title' => array(
                'name' => __('Loyalty Box Settings', 'woocommerce-settings-tab-loyaltybox'),
                'type' => 'title',
                'desc' => '',
                'id' => 'wc_settings_tab_loyaltybox_section_title',
            ),
                'reward_prog_name' => array(
                'name' => __('Reward Programme Name', 'woocommerce-settings-tab-loyaltybox'),
                'type' => 'text',
                'custom_attributes' => array('required' => true),
                'desc' => __('Please enter merchant reward programme name here.', 'woocommerce-settings-tab-demo'),
                'id' => 'wc_settings_tab_loyaltybox_reward_prog_name',
            ),
                'client_id' => array(
                'name' => __('Client Id', 'woocommerce-settings-tab-loyaltybox'),
                'type' => 'text',
                'custom_attributes' => array('required' => true),
                'desc' => __('Please enter your Client Id here.', 'woocommerce-settings-tab-loyaltybox'),
                'id' => 'wc_settings_tab_loyaltybox_client_id',
            ),
                'location_id' => array(
                'name' => __('Location Id', 'woocommerce-settings-tab-loyaltybox'),
                'type' => 'text',
                'custom_attributes' => array('required' => true),
                'desc' => __('Please enter your Location Id here.', 'woocommerce-settings-tab-loyaltybox'),
                'id' => 'wc_settings_tab_loyaltybox_location_id',
            ),
                'api_username' => array(
                'name' => __('API Username', 'woocommerce-settings-tab-loyaltybox'),
                'type' => 'text',
                'custom_attributes' => array('required' => true),
                'desc' => __('Please enter your API Username here.', 'woocommerce-settings-tab-loyaltybox'),
                'id' => 'wc_settings_tab_loyaltybox_api_username',
            ),
                'api_password' => array(
                'name' => __('API Password', 'woocommerce-settings-tab-loyaltybox'),
                'type' => 'text',
                // 'custom_attributes' => array('required' => true),
                'desc' => __('Please enter your API Password here.', 'woocommerce-settings-tab-loyaltybox'),
                'id' => 'wc_settings_tab_loyaltybox_api_password',
            ),
                'friendlymessage' => array(
                'name' => __('Friendly Message', 'woocommerce-settings-tab-loyaltybox'),
                'type' => 'textarea',
                'css' => 'width: 405px; height: 98px;',
                'desc' => __('Please enter friendly message here.', 'woocommerce-settings-tab-demo'),
                'id' => 'wc_settings_tab_loyaltybox_friendlymessage',
            ),
                'section_end' => array(
                'type' => 'sectionend',
                'id' => 'wc_settings_tab_loyaltybox_section_end',
            ),
        );

        return apply_filters('wc_settings_tab_loyaltybox_settings', $settings);
    }

    /**
     * admin_notice.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     */
    public function admin_notice()
    {
        if (!$this->opt['location_id'] || !$this->opt['client_id'] || !$this->opt['api_username'] || !$this->opt['api_password']) {
            echo '<div class="error"><p>'.
                     __('Loyaltybox is not properly configured. ', 'woocommerce-loyaltybox-admin').
                     '<a href="'.admin_url('admin.php?page=wc-settings&tab=settings_tab_loyaltybox').'">'.
                     __('Set up', 'woocommerce-loyaltybox-admin').
                     '</a>'.
                 '</p></div>';
        }
    }

    /**
     * form_post_handler.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     */
    public function form_post_handler()
    {
        if (isset($_POST['update_settings'])) {
            $this->update_settings();
        }

        if (isset($_POST['reset_settings'])) {
            delete_option(WC_Loyaltybox_Settings::OPT_NAME);
        }
    }

    /**
     * render_settings_page.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     */
    public function render_settings_page()
    {
        ?>

            <div class="wrap">

                <?php if ($this->errors || $this->message) : ?>
                    <div id="setting-error-settings_updated" class="<?php if ($this->errors): echo "error"; elseif ($this->message): echo "updated";
                endif;
                ?> ">
                <?php if ($this->errors) : ?>
                    <ul>
                <?php  foreach ($this->errors as $error) {
                    echo "<li>$error</li>";
                }
                ?>
                    </ul>
                <?php else : ?>
                    <p><?php  echo $this->message ?></p>
                <?php endif;
                ?>
                    </div>
                <?php endif;
                ?>

                    <div style="margin-bottom: 40px">
                    <h2>
                    Loyalty Box
                    </h2>
                    </div>
                    <div style="margin-top: 40px">
                    <form method="post" action="">
                <?php wp_nonce_field("wc_loyaltybox_settings");
                ?>
                    <input type="hidden" name="update_settings" value="1">
                <?php woocommerce_admin_fields(self::get_settings());
                ?>
                <?php submit_button();
                ?>
                </form>
                </div>
            </div>
        <?php

    }

    /**
     * admin_menu.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     */
    public function admin_menu()
    {
        if (current_user_can('manage_woocommerce')) {
            add_submenu_page('woocommerce', __('Loyaltybox Settings', 'woocommerce-loyaltybox-admin'),
                             'Loyalty Box', 'manage_woocommerce',
                             'wc-loyaltybox', array($this, 'render_settings_page'));
        }
    }

    /**
     * add_meta_boxes.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     */
    public function add_meta_boxes()
    {
        //$color = "<span style='color: #00aa00'>&#9632;</span>";
         add_meta_box('woocommerce-loyaltybox-order-info', "Loyaltybox Details", 'WC_Loyaltybox_Settings::render_order_meta', 'shop_order', 'normal', 'high');
    }

    /**
     * install.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     */
    public function install()
    {
        // Install CSS file
        if (!file_exists(LOYALTYBOX_CSS_FILE) && file_exists(LOYALTYBOX_CSS_MASTER)) {
            copy(LOYALTYBOX_CSS_MASTER, LOYALTYBOX_CSS_FILE);
        }
    }
    
}
endif;

// Load Loyaltybox Settings
if (is_admin()) {
    $GLOBALS['wc-loyaltybox-settings'] = new WC_Loyaltybox_Settings();
}