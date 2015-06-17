<?php
/**
 * Plugin Name: Loyaltybox
 * Plugin URI: http://www.theloyaltybox.com/
 * Description: Extension for WooCommerce adding rewards programme features by The Loyalty Box
 * Version: 1.0.1
 * Author: The Loyalty Box
 * Author URI: http://www.theloyaltybox.com/
 * Text Domain: loyaltybox
 * Domain Path: /languages.
 *
 * @author Loyaltybox
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

//Check if WooCommerce is active
if (! in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

define('LOYALTYBOX_VERSION',                 '0.0.1');
define('DEBUG_MODE',                         true);
define('REQUEST_DEBUG_MODE',                 true);
define('LOYALTYBOX_DECIMALS',                2);
define('LOYALTYBOX_COUPON_UID',              'loyaltybox_redeem');
define('LOYALTYBOX_WEBSITE',                 'https://www.tryloyaltybox.com');
define('LOYALTYBOX_BUSINESS_WEBSITE',        'https://business.tryloyaltybox.com');
define('LOYALTYBOX_PLUGIN',                  plugin_basename(__FILE__));
define('LOYALTYBOX_DEBUG_LOG',               plugin_dir_path(__FILE__).'loyaltybox.log');
define('LOYALTYBOX_ERROR_LOG',               plugin_dir_path(__FILE__).'error.log');
define('LOYALTYBOX_REQUEST_LOG',             plugin_dir_path(__FILE__).'request_log.log');
define('LOYALTYBOX_CSS_FILE',                plugin_dir_path(__FILE__).'assets/css/local.loyaltybox.css');
define('LOYALTYBOX_CSS_MASTER',              plugin_dir_path(__FILE__).'assets/css/master.loyaltybox.css');

/*
if( file_exists(LOYALTYBOX_ERROR_LOG) && filesize(LOYALTYBOX_ERROR_LOG)>100000)
    unlink(LOYALTYBOX_ERROR_LOG);

if( file_exists(LOYALTYBOX_DEBUG_LOG) && filesize(LOYALTYBOX_DEBUG_LOG)>100000)
    unlink(LOYALTYBOX_DEBUG_LOG);
*/
include_once plugin_dir_path(__FILE__).'includes/loyaltybox.inc.php';

// ---------------------------------------------------------------------------------------------------------------------

/**
 * wc_curr_version.
 *
 * @version 1.0.0
 *
 * @author Double Eye
 *
 * @since 1.0.0
 * @access public
 *
 * @return mixed
 */
function wc_curr_version()
{
    if (! function_exists('get_plugins')) {
        require_once ABSPATH.'wp-admin/includes/plugin.php';
    }
    $plugin_folder = get_plugins('/woocommerce');

    return $plugin_folder['woocommerce.php']['Version'];
}

Loyaltybox::$signature = '[CMS]Wordpress '.get_bloginfo('version').' WooCommerce '.wc_curr_version().' Woocommerce-Loyaltybox '.LOYALTYBOX_VERSION;

Loyaltybox::$fail_silently = true;

Loyaltybox::set_error_log(3, LOYALTYBOX_ERROR_LOG);

include_once plugin_dir_path(__FILE__).'includes/wc-loyaltybox-settings.php';

// ---------------------------------------------------------------------------------------------------------------------

if (! class_exists('LB_Plugin')) :

class LB_Plugin
{
    protected static $_instance = null;
    private $opt = null;

    public function __construct()
    {

        // Add hooks for action

        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('woocommerce_after_cart_table', [$this, 'render_cart_checkout_page'], 10);
        add_action('woocommerce_after_cart_table', [$this, 'render_redeem_points_form'], 10);
        add_action('woocommerce_before_checkout_form', [$this, 'render_cart_checkout_page'], 15);
        add_action('woocommerce_before_checkout_form', [$this, 'render_redeem_points_form'], 15);
        add_action('woocommerce_before_cart_table', [$this, 'check_on_cart_updated'], 10);
        add_action('woocommerce_checkout_process', [$this, 'checkout_process_started'], 10);
        add_action('woocommerce_thankyou', [$this, 'woocommerce_thankyou_page_complete_order'], 10);
        //add_action('woocommerce_checkout_order_processed', [$this, 'checkout_order_processed'], 10);
        //add_action('woocommerce_order_status_changed', [$this, 'checkout_order_processed']);
        //add_action( 'woocommerce_payment_complete',  [$this, 'mysite_woocommerce_payment_complete'] );
        //add_filter( 'woocommerce_payment_complete_order_status', [$this, 'rfvc_update_order_status'], 10, 2 );
        
        add_filter('woocommerce_cart_totals_coupon_label', [$this, 'change_coupon_label'], 10);

        // Add hooks for display

        add_thickbox();

        // registration callback

        add_action('wp_ajax_nopriv_register_user', [$this, 'register_user_callback']);
        add_action('wp_ajax_register_user', [$this, 'register_user_callback']);

        // login verify callback

        add_action('wp_ajax_nopriv_verify_user', [$this, 'verify_user_callback']);
        add_action('wp_ajax_verify_user', [$this, 'verify_user_callback']);

        // logout callback

        add_action('wp_ajax_nopriv_logout_user', [$this, 'logout_user_callback']);
        add_action('wp_ajax_logout_user', [$this, 'logout_user_callback']);

        // redeem_points callback

        add_action('wp_ajax_nopriv_redeem_points', [$this, 'redeem_points_callback']);
        add_action('wp_ajax_redeem_points', [$this, 'redeem_points_callback']);

        // initialise loyaltybox options here...
        $this->opt = get_option(WC_Loyaltybox_Settings::OPT_NAME);
        $rewardProgrammeName = $this->opt['reward_programme_name'];
        $clientId = $this->opt['client_id'];
        $locationId = $this->opt['location_id'];
        $userName = $this->opt['api_username'];
        $password = $this->opt['api_password'];
        $friendly_message = $this->opt['friendly_message'];
        
        @session_start();
        //$_SESSION['LB_Session_RequestId'] = 0;
        $lb_request_id = 0;
         if (isset($_SESSION['LB_Session_RequestId']))
         {
             if($_SESSION['LB_Session_RequestId'] > 0)
             {
                 $lb_request_id = $_SESSION['LB_Session_RequestId'];
             }
         }
        if(empty($lb_request_id)){
            
            $lb_request_id = Loyaltybox::getLbRequestId($clientId);
            $_SESSION['LB_Session_RequestId'] = $lb_request_id;
        }
        Loyaltybox::init($rewardProgrammeName,$clientId, $locationId, $userName, $password,$friendly_message,$lb_request_id);
        
    }
    
    public static function checkSocketTimeOut(){
        if(isset($_SESSION['time_out_error']))
            {
                if(!empty($_SESSION['time_out_error'])){
                     echo "<div class='woocommerce-info'>".$_SESSION['time_out_error']."</div>";
                }
            }
    }

        /**
    * woocommerce_thankyou_page_complete_order.
    * 
    * This function send final cart to loyaltybox
    *
    * @version 1.0.0
    *
    * @author Double Eye
    *
    * @since 1.0.0
    * @access public
    *
    * @param type $order_id 
    *
    * @return void
    */
    public function woocommerce_thankyou_page_complete_order($order_id)
    {
        $merchantId = Loyaltybox::$clientId;
        $orderProcessed = Loyaltybox::newOrderStatus($order_id, $merchantId);
        if(!$orderProcessed)
        {
        Loyaltybox::debug_log("Order process after checkout started order id: ".$order_id, true);
            $order = new WC_Order($order_id);
            $CartContents = $order->get_items();
            $CartContentsTotal = $order->get_subtotal();
            $CartGrandTotal = $order->get_total();
            
            $usedCoupons = $order->get_used_coupons();
            $CartDiscount = $order->get_total_discount();
            $isLoyaltyIssued = 0;
            $cartCoupons = array();
            if (!empty($usedCoupons)) {
                foreach ($usedCoupons as $wp_coupon => $cvalue) {
                    $coupon_data = new WC_Coupon($cvalue);
                    if (!empty($coupon_data->id)) {
                        $cartCouponsChild = array(
                            'code' => $coupon_data->code,
                            'discount_type' => $coupon_data->discount_type,
                            'coupon_amount' => $coupon_data->coupon_amount
                        );
                        array_push($cartCoupons, $cartCouponsChild);
                    }
                }
            }

            @session_start();
            $apiserverBasketId = 0;

            $LB_Session = "";
            if (isset($_SESSION['LB_Session']))
                $LB_Session = $_SESSION['LB_Session'];

            Loyaltybox::debug_log("Order processed started", true);
            if (!empty($LB_Session)) {
                $txtPhoneNumber = $LB_Session['Phone Number'];
                $lbCustomerName = $LB_Session['Customer Name'];
                if (isset($_SESSION['LB_Session']['basketId'])) {
                    $apiserverBasketId = $_SESSION['LB_Session']['basketId'];
                }
                $_SESSION['LB_Session']['basketId'] = 0;
                $_SESSION['LB_Session']['CartContentsCount'] = 0;

                $lineItems = array();
                foreach ($CartContents as $prodKey => $prodVal) {
                    $sku = self::get_product_sku($prodVal['product_id']);
                    $lineItem = array(
                        'productCode' => $sku,
                        'categoryCode' => '',
                        'qty' => $prodVal['qty'],
                        'price' => $prodVal['line_subtotal'],
                        'discountedPrice' => 0,
                        'description' => '',
                    );
                    array_push($lineItems, $lineItem);
                }

                $CommitTransaction = 0;
                $result = Loyaltybox::sendCartFinal($txtPhoneNumber, $CartContentsTotal, $lineItems, $CommitTransaction);

                $RequestLineItemRedemptionResult = $result->RequestLineItemRedemptionResult;
                $standardHeader = $RequestLineItemRedemptionResult->standardHeader;
                Loyaltybox::debug_log("LB API called sendCartFinal(commited): RequestLineItemRedemption", true);
                if ($standardHeader->status == 'A') {
                    $balances = $RequestLineItemRedemptionResult->balances;
                    $Balance = $balances->Balance;
                    $allowedDiscount = 0;

                    foreach ($Balance as $balValue) {
                        if ($balValue->valueCode == 'Discount') {
                            $allowedDiscount = $balValue->amount;
                            $_SESSION['LB_Session']['lb_discount'] = $balValue->amount;
                            $_SESSION['LB_Session']['lb_discount_difference'] = $balValue->difference;
                            $_SESSION['LB_Session']['lb_discount_exchangeRate'] = $balValue->exchangeRate;
                        } elseif ($balValue->valueCode == 'Points') {
                            $_SESSION['LB_Session']['lb_points'] = $balValue->amount;
                            $_SESSION['LB_Session']['lb_points_difference'] = $balValue->difference;
                            $_SESSION['LB_Session']['lb_points_exchangeRate'] = $balValue->exchangeRate;
                        } elseif ($balValue->valueCode == 'ZAR') {
                            $_SESSION['LB_Session']['lb_zar'] = $balValue->amount;
                            $_SESSION['LB_Session']['lb_zar_difference'] = $balValue->difference;
                            $_SESSION['LB_Session']['lb_zar_exchangeRate'] = $balValue->exchangeRate;
                        }
                    }

                    Loyaltybox::debug_log("LB API called : got Discount " . $allowedDiscount . "%", true);
                    //if ($allowedDiscount > 0) {
                            /*
                            * // NO NEED TO COMMIT TRANSACTION AS ITS FINAL CART ALREADY COMMITED.*/
                            $CommitTransaction = 1;
                            $result = Loyaltybox::sendCartFinal($txtPhoneNumber, $CartContentsTotal, $lineItems, $CommitTransaction);
                            Loyaltybox::debug_log("LB API called sendCartFinal(commited): RequestLineItemRedemption", true);
                            /**/
                        $RequestLineItemRedemptionResult = $result->RequestLineItemRedemptionResult;
                        $identification = $RequestLineItemRedemptionResult->identification;
                        
                        // REDEEM POINTS IF ANY
                        // Check actual redeem coupons amound
                        $CartAppliedCoupons = $cartCoupons;
                        $actualRedeemPoints = 0;
                        foreach ($CartAppliedCoupons as $cval=>$ckey){
                            if(!empty($ckey['code'])){
                                $couponFor = explode('_', $ckey['code']);
                                if(isset($couponFor[0]))
                                if($couponFor[0] == 'r')
                                {
                                    if(isset($couponFor[1])){
                                        $actualRedeemPoints = $actualRedeemPoints + $couponFor[1];
                                    }
                                }
                            }
                        }
                        $_SESSION['LB_Session']['totalRedeemPoints'] = $actualRedeemPoints;
                        // end of check..
                        $totalRedeemPoints = $_SESSION['LB_Session']['totalRedeemPoints'];
                        $CardOrPhoneNumber = $LB_Session['Phone Number'];
                        if($totalRedeemPoints > 0)
                        {
                            $redeemResult = Loyaltybox::redeemPoints($CardOrPhoneNumber,$lineItems,$totalRedeemPoints);
                            $UpdateSaleResult = $redeemResult->UpdateSaleResult;
                            $standardHeader = $UpdateSaleResult->standardHeader;
                            $identification = $UpdateSaleResult->identification;
                            Loyaltybox::debug_log("LB API called : UpdateSale", true);
                            if ($standardHeader->status == 'A') {
                                $_SESSION['LB_Session']['totalRedeemPoints'] = 0;
                                $balances = $UpdateSaleResult->balances;
                                $Balance = $balances->Balance;
                                foreach ($Balance as $balValue) {
                                    if ($balValue->valueCode == 'Discount') {
                                        $_SESSION['LB_Session']['lb_discount'] = $balValue->amount;
                                        $_SESSION['LB_Session']['lb_discount_difference'] = $balValue->difference;
                                        $_SESSION['LB_Session']['lb_discount_exchangeRate'] = $balValue->exchangeRate;
                                    } elseif ($balValue->valueCode == 'Points') {
                                        $_SESSION['LB_Session']['lb_points'] = $balValue->amount;
                                        $_SESSION['LB_Session']['lb_points_difference'] = $balValue->difference;
                                        $_SESSION['LB_Session']['lb_points_exchangeRate'] = $balValue->exchangeRate;
                                    } elseif ($balValue->valueCode == 'ZAR') {
                                        $_SESSION['LB_Session']['lb_zar'] = $balValue->amount;
                                        $_SESSION['LB_Session']['lb_zar_difference'] = $balValue->difference;
                                        $_SESSION['LB_Session']['lb_zar_exchangeRate'] = $balValue->exchangeRate;
                                    }
                                }

                                Loyaltybox::debug_log("LB API called : redeem points ".$totalRedeemPoints."", true);
                            }
                        }
                        else
                            $totalRedeemPoints = 0;
                        
                        // END OF REDEEM 
                        // ISSUE DISCOUNTED AMOUNT AS LB POINTS 
                        //$issuePoints = $allowedDiscount;
                        $issueResult = Loyaltybox::issuePoints($txtPhoneNumber, $lineItems, $CartGrandTotal);
                        $UpdateSaleResult = $issueResult->UpdateSaleResult;
                        $standardHeader = $UpdateSaleResult->standardHeader;
                        $earnPoints = 0;
                        Loyaltybox::debug_log("LB API called issuePoints: UpdateSale", true);
                        if ($standardHeader->status == 'A') {
                            $balances = $UpdateSaleResult->balances;
                            $Balance = $balances->Balance;
                            foreach ($Balance as $balValue) {
                                if ($balValue->valueCode == 'Points') {
                                    $earnPoints = $balValue->difference;
                                    $_SESSION['LB_Session']['lb_points'] = $balValue->amount;
                                    $_SESSION['LB_Session']['lb_points_difference'] = $balValue->difference;
                                    $_SESSION['LB_Session']['lb_points_exchangeRate'] = $balValue->exchangeRate;
                                }
                            }
                        }
                        $isLoyaltyIssued = 1;
                        Loyaltybox::debug_log("Issued Gift Issued " . $issuePoints . ".", true);
                        Loyaltybox::debug_log("Earn loyalty points " . $earnPoints . ".", true);
                        // STATE API CALL
                        $cartId = '';
                        $merchantId = Loyaltybox::$clientId;
                        $basketTotal = $CartContentsTotal;
                        $lbRef = $identification->transactionId;
                        $discounts = array("cart_discount" => $CartDiscount, "applied_coupon" => $CartAppliedCoupons);
                        $basketState = 'Paid';

                        // REMOVED CURRENT SESSION COUPON FROM DB.
                        $coupon_code = "";
                        if (isset($_SESSION['LB_Session']["LB_COUPON"])) {
                            if (!empty($_SESSION['LB_Session']["LB_COUPON"])) {
                                $coupon_code = $_SESSION['LB_Session']["LB_COUPON"];
                                $_SESSION['LB_Session']["LB_COUPON"] = "";
                            }
                        }
                        
                        Loyaltybox::newBasketState($cartId, $merchantId, $basketTotal, $lbRef, $lbCustomerName, $discounts, $basketState, $earnPoints, $totalRedeemPoints, $apiserverBasketId,$order_id,$isLoyaltyIssued);
                        $_SESSION['LB_Session_RequestId'] = 0;
                    //}
                }
            }
            Loyaltybox::debug_log("End of order processed", true);
        }
        else
            Loyaltybox::debug_log("Order already processed. order id: ".$order_id, true);
    }


    
    /**
    * instance.
    *
    * Use to get an instance of LB_Plugin plugin
    *
    * @version 1.0.0
    *
    * @author Double Eye
    *
    * @since 1.0.0
    * @access public
    *
    * @return LB_Plugin
    * @static
    */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * initialize.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     *
     * @return void
     */
    public function initialize()
    {
        //        
    }
    
    /**
     * change_coupon_label.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     *
     * @return string
     */
    function change_coupon_label() {
        echo 'Discount Applied';
    }
    
    /**
     * checkout_process_started.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $product_id
     *
     * @return void
     */
    function checkout_process_started() {
        @session_start();
        $LB_Session = "";
        if(isset($_SESSION['LB_Session']))
        $LB_Session = $_SESSION['LB_Session'];
        if (!empty($LB_Session)) 
        {
            $apiserverBasketId = 0;
            if(isset($_SESSION['LB_Session']['basketId'])){
                $apiserverBasketId = $_SESSION['LB_Session']['basketId'];
            }
            global $woocommerce;

            $CartContentsTotal = $woocommerce->cart->subtotal;
            $CartAppliedCoupons = $woocommerce->cart->coupons;
            $CartDiscount = $woocommerce->cart->discount_cart;
            $cartId = '';
            $merchantId = Loyaltybox::$clientId;
            $basketTotal = $CartContentsTotal;
            $lbRef = '--NA--';
            $discounts = array("cart_discount"=>$CartDiscount,"applied_coupon"=>$CartAppliedCoupons);
            $basketState = 'Checkout';
            $lbCustomerName = $LB_Session['Customer Name'];
            $result = Loyaltybox::newBasketState($cartId, $merchantId, $basketTotal, $lbRef, $lbCustomerName, $discounts, $basketState,0,0,$apiserverBasketId);
            if(!empty($result)){
                if(array_key_exists('basket_id', $result)){
                    $_SESSION['LB_Session']['basketId'] = $result['basket_id'];
                }
            }
        }
    } 
    
    /**
     * get_product_sku.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $product_id
     *
     * @return mixed
     * @static
     */
    public static function get_product_sku($product_id = 0)
    {
        global $wpdb;
        $sku = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_sku' AND post_id='%s' LIMIT 1", $product_id));

        return $sku;
    }

    /**
     * check_on_cart_updated.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     *
     * @return void
     */
    public function check_on_cart_updated()
    {
        @session_start();
        $LB_Session = $_SESSION['LB_Session'];
        //print_r($_SESSION['LB_Session']);
        //die;
        if (!empty($LB_Session)) {
            $txtPhoneNumber = $LB_Session['Phone Number'];
            $txtName = $LB_Session['Customer Name'];
            $txtEmail = $LB_Session['Email'];

            $CartContents = WC()->cart->cart_contents;
            $CartContentsTotal = WC()->cart->subtotal;
            $CartContentsCount = WC()->cart->cart_contents_count;

            // Checked here if there is no change in cart
            if (isset($_SESSION['LB_Session']['CartContentsCount'])) {
                if ($_SESSION['LB_Session']['CartContentsCount'] == $CartContentsCount
                    && $_SESSION['LB_Session']['CartContentsTotal'] == $CartContentsTotal) {
                    return;
                } else {
                    $_SESSION['LB_Session']['CartContentsCount'] = $CartContentsCount;
                    $_SESSION['LB_Session']['CartContentsTotal'] = $CartContentsTotal;
                }
            } else {
                $_SESSION['LB_Session']['CartContentsCount'] = $CartContentsCount;
                $_SESSION['LB_Session']['CartContentsTotal'] = $CartContentsTotal;
            }

            $lineItems = array();

            foreach ($CartContents as $prodKey => $prodVal) {
                $sku = self::get_product_sku($prodVal['product_id']);
                $lineItem = array(
                        'productCode' => $sku,
                        'categoryCode' => '',
                        'qty' =>  $prodVal['quantity'],
                        'price' =>  $prodVal['data']->price,
                        'discountedPrice' => 0,
                        'description' =>  '',
                    );

                //print_r($lineItem);
                    array_push($lineItems, $lineItem);
            }
            
            
            $allowedDiscount = Loyaltybox::sendCartUpdate($txtPhoneNumber,$CartContentsTotal,$lineItems, 0);
            self::checkSocketTimeOut();
            if ($allowedDiscount > 0) {
                self::generate_discount_coupon($allowedDiscount, 'fixed_cart');
            }
        }
    }

    /**
     * generate_discount_coupon.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     *
     * @param int    $discount
     * @param string $discount_type
     * @param string $type
     *
     * @static
     */
    public static function generate_discount_coupon($discount = 0, $discount_type = "fixed_cart", $type = '')
    {
        global $woocommerce;
        @session_start();
        $LB_Session = $_SESSION['LB_Session'];
        $coupon_code = "";
        if(isset($_SESSION['LB_Session']["LB_COUPON"])){
            if(!empty($_SESSION['LB_Session']["LB_COUPON"]))
                $coupon_code = $_SESSION['LB_Session']["LB_COUPON"];
            else{
                $coupon_code = "LB_". rand(1111,9999);//Generate name for coupon for current session. 
                $_SESSION['LB_Session']["LB_COUPON"] = $coupon_code;
            }
        }
        else
        {
            $coupon_code = "LB_". rand(1111,9999);//Generate name for coupon for current session. 
            $_SESSION['LB_Session']["LB_COUPON"] = $coupon_code;
        }
        
        if ($type == "REDEEM") {
            $coupon_code = "R_".$discount."_".time();
            $amount = $discount; // Amount
            //$discount_type = 'fixed_cart'; // Type: fixed_cart, percent, fixed_product, percent_product
            $coupon = array(
            'post_title' => $coupon_code,
            'post_content' => '',
            'post_status' => 'publish',
            //'post_author' => 1,
            'post_type' => 'shop_coupon',
            );
            
            $new_coupon_id = wp_insert_post($coupon);
            Loyaltybox::debug_log("Created New Coupon for redeem LB Points:".$coupon_code, true);

            update_post_meta($new_coupon_id, 'discount_type', $discount_type);
            update_post_meta($new_coupon_id, 'coupon_amount', $amount);
            update_post_meta($new_coupon_id, 'individual_use', 'no');
            update_post_meta($new_coupon_id, 'product_ids', '');
            update_post_meta($new_coupon_id, 'exclude_product_ids', '');
            update_post_meta($new_coupon_id, 'usage_limit', '1');
            update_post_meta($new_coupon_id, 'expiry_date', date("Y-m-d H:i:s",  strtotime(date('Y-m-d H:i:s')." +5 hours")));
            update_post_meta($new_coupon_id, 'apply_before_tax', 'yes');
            update_post_meta($new_coupon_id, 'free_shipping', 'no');
            $woocommerce->cart->add_discount($coupon_code);
        }
        else
        {
            $amount = $discount; // Amount
            //$discount_type = 'fixed_cart'; // Type: fixed_cart, percent, fixed_product, percent_product
            $coupon = array(
            'post_title' => $coupon_code,
            'post_content' => '',
            'post_status' => 'publish',
            //'post_author' => 1,
            'post_type' => 'shop_coupon',
            );
            $new_coupon_id = 0;
            $posts = new WC_Coupon($coupon_code);
           
            //create new coupon
            if (empty($posts->exists)) {
                //add new post
                $new_coupon_id = wp_insert_post($coupon);
                Loyaltybox::debug_log("Created New Coupon for cart:".$coupon_code, true);
                
            } else {
                // Add meta
                $new_coupon_id = $posts->id;
                Loyaltybox::debug_log("Updated Coupon for cart:".$coupon_code, true);
            }

            update_post_meta($new_coupon_id, 'discount_type', $discount_type);
            update_post_meta($new_coupon_id, 'coupon_amount', $amount);
            update_post_meta($new_coupon_id, 'individual_use', 'no');
            update_post_meta($new_coupon_id, 'product_ids', '');
            update_post_meta($new_coupon_id, 'exclude_product_ids', '');
            update_post_meta($new_coupon_id, 'usage_limit', '1');
            update_post_meta($new_coupon_id, 'expiry_date', '');
            update_post_meta($new_coupon_id, 'apply_before_tax', 'yes');
            update_post_meta($new_coupon_id, 'free_shipping', 'no');
            if ($woocommerce->cart->has_discount($coupon_code)) {
                $woocommerce->cart->remove_coupons($coupon_code);
                $woocommerce->cart->add_discount($coupon_code);
                echo "<div class='woocommerce-info'>
                    ".Loyaltybox::$rewardProgrammeName." Discount of ".$amount." is applied on your cart.</div>";
            } else {
                $woocommerce->cart->add_discount($coupon_code);
                echo "<div class='woocommerce-info'>
                    ".Loyaltybox::$rewardProgrammeName." Discount of ".$amount." is applied on your cart.</div>";
            }
        }
        
    }

    /**
     * register_user_callback.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     */
    public function register_user_callback()
    {
        if (check_ajax_referer('register_user', 'hidd_nonce')) {
            $txtName = $_POST['txtName'];
            $txtEmail = $_POST['txtEmail'];
            $txtPhoneNumber = $_POST['txtPhoneNumber'];

            $result = Loyaltybox::registerUser($txtName,$txtEmail,$txtPhoneNumber);
            echo json_encode($result);
            die;
        }
    }

    /**
     * verify_user_callback.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     */
    public function verify_user_callback()
    {
        if (check_ajax_referer('login_user', 'hidd_login_nonce')) {
            
            $txtCardNumber = $_POST['txtCardNumber'];
            $result = Loyaltybox::verifyUser($txtCardNumber);
            echo json_encode($result);
            die;
        }
    }
    
    /**
     * logout_user_callback.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     */
    public function logout_user_callback(){
        global $woocommerce;
        @session_start();
        $coupon_code = "";
        if(isset($_SESSION['LB_Session']["LB_COUPON"])){
            if(!empty($_SESSION['LB_Session']["LB_COUPON"]))
                $coupon_code = $_SESSION['LB_Session']["LB_COUPON"];
        }
        if(!empty($coupon_code))
        if ($woocommerce->cart->has_discount($coupon_code)) {
                $woocommerce->cart->remove_coupon($coupon_code);
                $coupon_data = new WC_Coupon($coupon_code);
                if(isset($coupon_data->id))
                if(!empty($coupon_data->id))
                {
                    wp_delete_post($coupon_data->id);
                }
        }
        $_SESSION['LB_Session'] = NULL;
        $_SESSION['LB_Session_RequestId'] = 0;
        echo json_encode(array('status' => 1, 'message' => "You have successfully logged out with ".Loyaltybox::$rewardProgrammeName."."));
        die;
    }

    /**
     * enqueue_scripts.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     */
    public function enqueue_scripts()
    {
        if (is_admin()) {
            return;
        }
        wp_enqueue_script('jqeury-ui-script', '//code.jquery.com/ui/1.11.4/jquery-ui.js', array( 'jquery' ));
        wp_enqueue_script('loyaltybox-script', plugins_url('assets/js/local.loyaltybox.js', __FILE__), array( 'jquery' ));
        wp_localize_script('loyaltybox-script', 'loyaltybox_data',
            array( 'ajax_url' => admin_url('admin-ajax.php')));
        wp_enqueue_style('loyaltybox-style1', plugins_url('assets/css/local.loyaltybox.css', __FILE__));
        wp_enqueue_style('loyaltybox-style1', plugins_url('assets/css/master.loyaltybox.css', __FILE__));
    }

    /**
     * redeem_points_callback.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     */
    public function redeem_points_callback()
    {
        if (check_ajax_referer('redeem_points', 'hidd_redeem_nonce')) {
            @session_start();
            $LB_Session = $_SESSION['LB_Session'];
            if (!empty($LB_Session)) {
                // confirm loyalty points available or not and update to session if available.
                $CardOrPhoneNumber = $LB_Session['Phone Number'];
                $CardPoints = LoyaltyBox::getCardPoints($CardOrPhoneNumber);
                if($CardPoints){
                $InquiryResult = $CardPoints->InquiryResult;
                $balances = $InquiryResult->balances;
                $Balance = $balances->balance;
                foreach ($Balance as $balValue) {
                    if ($balValue->valueCode == 'Discount') {
                        $_SESSION['LB_Session']['lb_discount'] = $balValue->amount;
                        $_SESSION['LB_Session']['lb_discount_difference'] = $balValue->difference;
                        $_SESSION['LB_Session']['lb_discount_exchangeRate'] = $balValue->exchangeRate;
                    } elseif ($balValue->valueCode == 'Points') {
                        $_SESSION['LB_Session']['lb_points'] = $balValue->amount;
                        $_SESSION['LB_Session']['lb_points_difference'] = $balValue->difference;
                        $_SESSION['LB_Session']['lb_points_exchangeRate'] = $balValue->exchangeRate;
                    } elseif ($balValue->valueCode == 'ZAR') {
                        $_SESSION['LB_Session']['lb_zar'] = $balValue->amount;
                        $_SESSION['LB_Session']['lb_zar_difference'] = $balValue->difference;
                        $_SESSION['LB_Session']['lb_zar_exchangeRate'] = $balValue->exchangeRate;
                    }
                }
                Loyaltybox::debug_log("LB API called : Inquiry to check Points Balance", true);

                $txtRedeemPoints = $_POST['txtRedeemPoints'];
                
                $CartContents = WC()->cart->cart_contents;
                $CartContentsTotal = WC()->cart->subtotal;
                $CartTotal = WC()->cart->total;
                $CartContentsCount = WC()->cart->cart_contents_count;
                $totalRedeemPoints = 0;
                if(isset($_SESSION['LB_Session']['totalRedeemPoints']))
                {
                    if($_SESSION['LB_Session']['redeemPoints'] > 0){
                        $totalRedeemPoints = $_SESSION['LB_Session']['totalRedeemPoints'] + $txtRedeemPoints;
                    }
                }
                if($totalRedeemPoints == 0){
                    $totalRedeemPoints = $txtRedeemPoints;
                }
                
                if ($txtRedeemPoints > 0 && $totalRedeemPoints <= $CartTotal && is_numeric($txtRedeemPoints)) {
                    if ($_SESSION['LB_Session']['lb_points'] >= $totalRedeemPoints) {
                        // generate a coupon for allowedRedeemAmt and apply to the cart.
                        self::generate_discount_coupon($txtRedeemPoints, 'fixed_cart', 'REDEEM');
                        echo json_encode(array('status' => 1, 'message' => "LB Points are redeem successfully and applied discount to your cart."));
                        if(isset($_SESSION['LB_Session']['totalRedeemPoints']))
                        {
                            if($_SESSION['LB_Session']['totalRedeemPoints'] > 0){
                                $_SESSION['LB_Session']['totalRedeemPoints'] = $_SESSION['LB_Session']['totalRedeemPoints'] + $txtRedeemPoints;
                            }
                        }
                        else
                        {
                            $_SESSION['LB_Session']['totalRedeemPoints'] = $txtRedeemPoints;
                        }
                        
                        /*$lineItems = array();
                        foreach ($CartContents as $prodKey => $prodVal) {
                            $sku = self::get_product_sku($prodVal['product_id']);
                            $lineItem = array(
                                'productCode' => $sku,
                                'categoryCode' => '',
                                'qty' =>  $prodVal['quantity'],
                                'price' =>  $prodVal['data']->price,
                                'discountedPrice' => 0,
                                'description' =>  '',
                            );
                        //print_r($lineItem);
                            array_push($lineItems, $lineItem);
                        }
                        $result = Loyaltybox::redeemPoints($CardOrPhoneNumber,$lineItems,$txtRedeemPoints);

                        $UpdateSaleResult = $result->UpdateSaleResult;
                        $standardHeader = $UpdateSaleResult->standardHeader;
                        $identification = $UpdateSaleResult->identification;
                        Loyaltybox::debug_log("LB API called : UpdateSale", true);
                        if ($standardHeader->status == 'A') {
                            $balances = $UpdateSaleResult->balances;
                            $Balance = $balances->Balance;
                            $allowedRedeemAmt = 0;

                            foreach ($Balance as $balValue) {
                                if ($balValue->valueCode == 'Discount') {
                                    $_SESSION['LB_Session']['lb_discount'] = $balValue->amount;
                                    $_SESSION['LB_Session']['lb_discount_difference'] = $balValue->difference;
                                    $_SESSION['LB_Session']['lb_discount_exchangeRate'] = $balValue->exchangeRate;
                                } elseif ($balValue->valueCode == 'Points') {
                                    $allowedRedeemAmt = $txtRedeemPoints;
                                    $_SESSION['LB_Session']['lb_points'] = $balValue->amount;
                                    $_SESSION['LB_Session']['lb_points_difference'] = $balValue->difference;
                                    $_SESSION['LB_Session']['lb_points_exchangeRate'] = $balValue->exchangeRate;
                                } elseif ($balValue->valueCode == 'ZAR') {
                                    $_SESSION['LB_Session']['lb_zar'] = $balValue->amount;
                                    $_SESSION['LB_Session']['lb_zar_difference'] = $balValue->difference;
                                    $_SESSION['LB_Session']['lb_zar_exchangeRate'] = $balValue->exchangeRate;
                                }
                            }

                            Loyaltybox::debug_log("LB API called : redeem points ".$allowedRedeemAmt."", true);
                            if ($allowedRedeemAmt > 0) {
                                // generate a coupon for allowedRedeemAmt and apply to the cart.
                                self::generate_discount_coupon($allowedRedeemAmt, 'fixed_cart', 'REDEEM');
                                echo json_encode(array('status' => 1, 'message' => "LB Points are redeem successfully and applied discount to your cart."));
                                // CALL THE STATE API HERE
                                // STATE API CALL
                                $apiserverBasketId = 0;
                                if(isset($_SESSION['LB_Session']['basketId'])){
                                    $apiserverBasketId = $_SESSION['LB_Session']['basketId'];
                                }
                                $CartAppliedCoupons = WC()->cart->coupons;
                                $CartDiscount = WC()->cart->discount_cart;
                                $cartId = '';
                                $merchantId = Loyaltybox::$clientId;
                                $basketTotal = $CartContentsTotal;
                                $lbRef = $identification->transactionId;
                                $discounts = array("cart_discount"=>$CartDiscount,"applied_coupon"=>$CartAppliedCoupons);
                                $basketState = 'Browsing';
                                $lbCustomerName = $LB_Session['Customer Name'];
                                $basketResult = Loyaltybox::newBasketState($cartId, $merchantId, $basketTotal, $lbRef, $lbCustomerName, $discounts, $basketState,0, $allowedRedeemAmt,$apiserverBasketId);
                                if(!empty($basketResult)){
                                    if(array_key_exists('basket_id', $basketResult)){
                                        $_SESSION['LB_Session']['basketId'] = $basketResult['basket_id'];
                                    }
                                }
                            }
                        } else {
                            $errorMessage = $UpdateSaleResult->errorMessage;
                            $errorCode = $errorMessage->errorCode;
                            Loyaltybox::debug_log("Response : ".$errorMessage->briefMessage, true);
                            echo json_encode(array('status' => 0, 'message' => $errorMessage->briefMessage));
                            die;
                        }*/
                    } else {
                        echo json_encode(array('status' => 0, 'message' => "You don't have sufficient Loyalty Points to redeem."));
                    }
                } else {
                    echo json_encode(array('status' => 0, 'message' => "Please enter valid Loyalty Points."));
                }
                } else {
                    echo json_encode(array('status' => 0, 'message' => $_SESSION['time_out_error']));
                }
            } else {
                echo json_encode(array('status' => 0, 'message' => "Please login to the ".Loyaltybox::$rewardProgrammeName."."));
            }
        } else {
            echo json_encode(array('status' => 0, 'message' => 'Please try again.'));
        }
        die;
    }

    /**
     * render_redeem_points_form.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     */
    public function render_redeem_points_form()
    {
        @session_start();
        $LB_Session = $_SESSION['LB_Session'];
        if (!empty($LB_Session)) {
            if ($LB_Session['lb_points'] > 0) {
                ?>
            <div class="woocommerce-info">Want to Redeem Loyalty Points?
            <a class="showRedeemBox" href="javascript:void(0);">Click here to redeem</a></div>
            <div id="redeemBox">
                <div class="loyaltybox-info-div"><strong>Enter Loyalty Points</strong></div>
                <div class="loyaltybox-info-div">
                    <form onsubmit="return false;">
                        <fieldset>
                            <input style="width:65%" type="text" name="txtPoints" id="txtPoints" placeholder="Enter Points" value="" class="text ui-widget-content ui-corner-all">
                            <input style="width:30%" type="submit" value="Redeem" id="btnRedeem" name="btnRedeem" />
                        </fieldset>
                        <?php
                            $ajax_nonce = wp_create_nonce("redeem_points");
                ?>
                        <input type="hidden" id="hidd_redeem_nonce" name="hidd_redeem_nonce" value="<?php echo $ajax_nonce;
                ?>" />
                    </form>
                    <div id="redeemMsg"></div>
                    <div class="lb_loading_redeem">
                            <img src="<?php echo plugins_url('assets/img/lb_loading.gif', __FILE__); ?>"/>
                    </div>
                </div>
            </div>
        <?php
                Loyaltybox::debug_log("Rendered redeem point form", true);
            }
        }
    }

    /**
     * checkout_order_processed.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     */
    public function checkout_order_processed()
    {
        @session_start();
        $apiserverBasketId = 0;
        
        $LB_Session = "";
        if(isset($_SESSION['LB_Session']))
            $LB_Session = $_SESSION['LB_Session'];
        Loyaltybox::debug_log("Order processed started", true);
        if (!empty($LB_Session)) {
            $txtPhoneNumber = $LB_Session['Phone Number'];
            $lbCustomerName = $LB_Session['Customer Name'];
            $txtEmail = $LB_Session['Email'];
            
            if(isset($_SESSION['LB_Session']['basketId'])){
                $apiserverBasketId = $_SESSION['LB_Session']['basketId'];
            }
            $_SESSION['LB_Session']['basketId'] = 0;
            $_SESSION['LB_Session']['CartContentsCount'] = 0;
            
            $CartContents = WC()->cart->cart_contents;
            $CartContentsTotal = WC()->cart->subtotal;
            $CartTotal = WC()->cart->total;
            $CartAppliedCoupons = WC()->cart->coupons;
            $CartDiscount = WC()->cart->discount_cart;
            $CartContentsCount = WC()->cart->cart_contents_count;
            
            /*$order = new WC_Order( $order_id );
                $items = $order->get_items();*/
            $lineItems = array();
            foreach ($CartContents as $prodKey => $prodVal) {
                $sku = self::get_product_sku($prodVal['product_id']);
                $lineItem = array(
                        'productCode' => $sku,
                        'categoryCode' => '',
                        'qty' =>  $prodVal['quantity'],
                        'price' =>  $prodVal['data']->price,
                        'discountedPrice' => 0,
                        'description' =>  '',
                    );
                array_push($lineItems, $lineItem);
            }
            
            $CommitTransaction = 1;
            $result = Loyaltybox::sendCartFinal($txtPhoneNumber,$CartContentsTotal,$lineItems,$CommitTransaction);
            
            $RequestLineItemRedemptionResult = $result->RequestLineItemRedemptionResult;
            $standardHeader = $RequestLineItemRedemptionResult->standardHeader;
            Loyaltybox::debug_log("LB API called sendCartFinal(commited): RequestLineItemRedemption", true);
            if ($standardHeader->status == 'A') {
                $balances = $RequestLineItemRedemptionResult->balances;
                $Balance = $balances->Balance;
                $allowedDiscount = 0;

                foreach ($Balance as $balValue) {
                    if ($balValue->valueCode == 'Discount') {
                        $allowedDiscount = $balValue->amount;
                        $_SESSION['LB_Session']['lb_discount'] = $balValue->amount;
                        $_SESSION['LB_Session']['lb_discount_difference'] = $balValue->difference;
                        $_SESSION['LB_Session']['lb_discount_exchangeRate'] = $balValue->exchangeRate;
                    } elseif ($balValue->valueCode == 'Points') {
                        $_SESSION['LB_Session']['lb_points'] = $balValue->amount;
                        $_SESSION['LB_Session']['lb_points_difference'] = $balValue->difference;
                        $_SESSION['LB_Session']['lb_points_exchangeRate'] = $balValue->exchangeRate;
                    } elseif ($balValue->valueCode == 'ZAR') {
                        $_SESSION['LB_Session']['lb_zar'] = $balValue->amount;
                        $_SESSION['LB_Session']['lb_zar_difference'] = $balValue->difference;
                        $_SESSION['LB_Session']['lb_zar_exchangeRate'] = $balValue->exchangeRate;
                    }
                }

                Loyaltybox::debug_log("LB API called : got Discount ".$allowedDiscount."%", true);
                if ($allowedDiscount > 0) {
                    //self::generate_discount_coupon($allowedDiscount, 'fixed_cart');
                    // NOT NEED TO COMMIT TRANSACTION AS ITS FINAL CART ALREADY COMMITED.
                    /*
                     * $CommitTransaction = 1;
                    $result = Loyaltybox::sendCartFinal($txtPhoneNumber,$CartContentsTotal,$lineItems,$CommitTransaction);
                    Loyaltybox::debug_log("LB API called sendCartFinal(commited): RequestLineItemRedemption", true);
                     */
                    $RequestLineItemRedemptionResult = $result->RequestLineItemRedemptionResult;
                    $identification = $RequestLineItemRedemptionResult->identification;
                    // ISSUE DISCOUNTED AMOUNT AS LB POINTS 
                    $issuePoints = $allowedDiscount;
                    Loyaltybox::issuePoints($txtPhoneNumber,$lineItems,$issuePoints);
                    Loyaltybox::debug_log("Issued loyalty points ".$issuePoints.".", true);
                    // STATE API CALL
                    $cartId = '';
                    $merchantId = Loyaltybox::$clientId;
                    $basketTotal = $CartContentsTotal;
                    $lbRef = $identification->transactionId;
                    $discounts = array("cart_discount"=>$CartDiscount,"applied_coupon"=>$CartAppliedCoupons);
                    $basketState = 'Paid';

                    // REMOVED CURRENT SESSION COUPON FROM DB.
                    $coupon_code = "";
                        if(isset($_SESSION['LB_Session']["LB_COUPON"])){
                            if(!empty($_SESSION['LB_Session']["LB_COUPON"]))
                            {
                                $coupon_code = $_SESSION['LB_Session']["LB_COUPON"];
                                $_SESSION['LB_Session']["LB_COUPON"] = "";
                            }
                        }
                        if(!empty($coupon_code))
                        {
                            $coupon_data = new WC_Coupon($coupon_code);
                            if(isset($coupon_data->id))
                            if(!empty($coupon_data->id))
                            {
                                wp_delete_post($coupon_data->id);
                            }
                        }
                    Loyaltybox::newBasketState($cartId, $merchantId, $basketTotal, $lbRef, $lbCustomerName, $discounts, $basketState,0,0,$apiserverBasketId);
                }
            }
        }
        Loyaltybox::debug_log("End of order processed", true);
    }

    /**
     * render_account_page.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     *
     * @param $page
     */
    public function render_account_page()
    {
        $this->render_cart_checkout_page();
    }

    /**
     * render_cart_checkout_page.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     */
    public function render_cart_checkout_page()
    {
        ?>
            <div class="loyaltybox-div-cart-page">
                <div class="loyaltybox-cart-page-contain">
                    <div class="loyaltybox-info-div">
                        <?php
                        @session_start();
                        //$_SESSION['LB_Session'] = null;
                        $LB_Session = $_SESSION['LB_Session'];

                        if (!empty($LB_Session)) {
                            Loyaltybox::debug_log("Rendered session user with his LB Points", true);
                            ?>
                                        <div class="loyaltybox-info-contain"><?php echo "<strong>Hi ".$LB_Session['Customer Name']."</strong>,| <a id='lbLogout' href='javascript:void(0);'>Logout(".Loyaltybox::$rewardProgrammeName.")</a></br>You have ".$LB_Session['lb_points']." Points in your <strong>".Loyaltybox::$rewardProgrammeName."</strong> account.";
                            ?></div>
                                        <?php

                        } else {
                            Loyaltybox::debug_log("Rendered Connect with Loyalty Box button", true);
                            ?>
                                    <div class="loyaltybox-info-contain">
                                        Connect with <?php echo Loyaltybox::$rewardProgrammeName;?> to get rewarded when you make a purchase and more...
                                    </div>
                                    <input type="button" id="btnConnect" name="btnConnect" style="font-size: 12px; " class="thickbox" title="Connect with <?php echo Loyaltybox::$rewardProgrammeName;?>" alt="#TB_inline?height=450&amp;width=400&amp;inlineId=registerUserPop" value="Connect with <?php echo Loyaltybox::$rewardProgrammeName;?>" />
                                    <?php 
                        }
                        ?>
                    </div>
                </div>
            </div>


        <div id="registerUserPop" title="Create new user" style="display:none">
            <div id="registerLB">
            <p class="validateTips">All form fields are required.</p>
                <form onsubmit="return false;">
                <fieldset>
                    <label for="txtName">Full Name</label>
                    <input type="text" name="txtName" id="txtName" value="" class="text ui-widget-content ui-corner-all">
                    <label for="txtEmail">Email</label>
                    <input type="text" name="txtEmail" id="txtEmail" value="" class="text ui-widget-content ui-corner-all">
                    <label for="txtPhoneNumber">Phone number</label>
                    <input type="text" name="txtPhoneNumber" id="txtPhoneNumber" value="" class="text ui-widget-content ui-corner-all">
                    <!-- Allow form submission with keyboard without duplicating the dialog button -->
                    <input type="submit" value="Register" id="btnConnectLB" name="btnConnectLB" />
                </fieldset>
                    <?php
                        $ajax_nonce = wp_create_nonce("register_user");
        ?>
                <input type="hidden" id="hidd_nonce" name="hidd_nonce" value="<?php echo $ajax_nonce;
        ?>" />
                </form>

                <div style="margin-top: 5px;">
                Or <a style="color:burlywood;" id="lnkLBLogin" href="javascript:void(0);" >login</a> if already registered.
                </div>
            <div>
                download our <a style="color:burlywood;" id="lnkDownloadApp" href="javascript:void(0);" >mobile application</a>
            </div>
            </div>
            <div id="loginLB">
                <p class="validateTipsloginLB">All form fields are required.</p>
                <form onsubmit="return false;">
                <fieldset>
                    <label for="txtCardNumber">Card number / Cell Number / OTP</label>
                    <input type="text" name="txtCardNumber" id="txtCardNumber" value="" class="text ui-widget-content ui-corner-all">
                    <input type="submit" value="Login" id="btnLoginLB" name="btnLoginLB" />
                </fieldset>
                    <?php
                        $ajax_nonce = wp_create_nonce("login_user");
        ?>
                    <input type="hidden" id="hidd_login_nonce" name="hidd_login_nonce" value="<?php echo $ajax_nonce;
        ?>" />
                </form>
                <div style="margin-top: 5px;">
                Or <a style="color:burlywood;" id="lnkLBRegister" href="javascript:void(0);" >click here</a> to register.
                </div>
            </div>
            <div class="lb_loading">
                <img src="<?php echo plugins_url('assets/img/lb_loading.gif', __FILE__);
        ?>"/>
            </div>
        </div>
    <?php

    }
   
}
endif;

/**
 * lb_plugin_instance.
 *
 * Use instance to avoid multiple api call so Loyaltybox can be super fast.
 *
 * @version 1.0.0
 *
 * @author Double Eye
 *
 * @since 1.0.0
 * @access public
 *
 * @return LB_Plugin|null
 */
function lb_plugin_instance()
{
    return LB_Plugin::instance();
}

$GLOBALS['lb_plugin'] = lb_plugin_instance();
