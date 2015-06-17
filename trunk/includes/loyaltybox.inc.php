<?PHP
/**
 * Copyright 2015 Loyaltybox.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */
if (!function_exists('curl_init')) {
    trigger_error('Loyaltybox needs the CURL PHP extension.');
}

if (!function_exists('json_decode')) {
    trigger_error('Loyaltybox needs the JSON PHP extension.');
}

class LoyaltyboxException extends Exception
{
    public function __construct($error = array())
    {
        if (!isset($error['code'])) {
            $error['code'] = -1;
        }
        if (!isset($error['message'])) {
            $error['message'] = '';
        }

        parent::__construct($error['message'], $error['code']);
    }
}

class Loyaltybox
{
    /**
     * @var string
     */
    public static $signature = 'PHP API';      // Set this value to whatever the API is used for
    
    /**
     * @var bool
     */
    public static $fail_silently = false;       // Set this to false in dev env

    /**
     * @var string
     */
    public static $endpoint = "http://webservice.theloyaltybox.com/RetailConnect_LineItems.asmx?WSDL";
    
    /**
     * @var string
     */
    public static $access_token_path = 'oauth/access_token';
    
    /**
     * @var string
     */
    public static $stateAPIendpoint = "http://54.85.223.200/api/v1/";
    
    /**
     * @var string
     */
    const VERSION = '1';
    
    /**
     * @var string
     */
    const OPT_NAME = 'wc_loyaltybox_options';
    
    /**
     * @var int
     */
    private static $error_log_type = 0;         // PHP error_log: $message_type  argument
    
    /**
     * @var string
     */
    private static $error_log_destination = ''; // PHP error_log: $destination   argument
    
    /**
     * @var string
     */
    private static $error_log_extra = '';       // PHP error_log: $extra_headers argument
    
    /**
     * Set this by calling init().
     *
     * @var int
     */
    public static $rewardProgrammeName;
    
    /**
     * Set this by calling init().
     *
     * @var int
     */
    public static $clientId;
    
    /**
     * Set this by calling init().
     *
     * @var int
     */
    public static $locationId;
    
    /**
     * Set this by calling init().
     *
     * @var string
     */
    public static $userName;
    
    /**
     * Set this by calling init().
     *
     * @var string
     */
    public static $password;
    
    /**
     * Set this by calling init().
     *
     * @var string
     */
    public static $friendly_message;
    
    /**
     * Set this by calling init().
     *
     * @var int
     */
    public static $lb_request_id;

    // Consider using init instead of object construct
    public function __construct()
    {
        //
    }
    
    /**
    * debug_log.
    *
    * A method to log information to disk
    *
    * @version 1.0.0
    *
    * @author Double Eye
    *
    * @since 1.0.0
    * @access public
    *
    * @param      $info
    * @param bool $first_line
    *
    * @static
    */
    public static function debug_log($info, $first_line = false)
    {
        if (DEBUG_MODE) {
            $log = date('Y-m-d H:i:s.uP')." => ".$info.PHP_EOL;
            if ($first_line) {
                $log = PHP_EOL.PHP_EOL.$log;
            }
            file_put_contents(LOYALTYBOX_DEBUG_LOG, $log, FILE_APPEND);
        }
    }
    
    /**
    * getLbRequestId.
    *
    * Get autopopulated request id from state api use as request id for Loyalty Box API
    *
    * @version 1.0.0
    *
    * @author Double Eye
    *
    * @since 1.0.0
    * @access public
    *
    * @param string $merchantId
    *
    * @return int|mixed|void
    * @static
    */
    public static function getLbRequestId($merchantId = '')
    {
        
        $path = "lbrequest?merchantId=".$merchantId;
        $orderStatus = self::state_api_make_request($path, null, null, 'GET');
        
        if(!empty($orderStatus)){
            return $orderStatus['lbrequest_id'];
        }
        else
            return 0;
    }
    
    /**
    * getCardPoints.
    *
    * Get points balance for card number.
    *
    * @version 1.0.0
    *
    * @author Double Eye
    *
    * @since 1.0.0
    * @access public
    *
    * @param string $CardOrPhoneNumber
    *
    * @return array|int|mixed|void
    * @static
    */
    public static function getCardPoints($CardOrPhoneNumber = '')
    {                        
        if (!empty($CardOrPhoneNumber)) {
            $clientId = self::$clientId;
            $locationId = self::$locationId;
            $userName = self::$userName;
            $password = self::$password;

            $txtCardNumber = $CardOrPhoneNumber;

            $paramArray = array(
                    'Inquiry' => array(
                        'p_objRequest' => array(
                            'standardHeader' => array(
                                'requestId' => self::$lb_request_id,
                                'localeId' => '',
                                'systemId' => '',
                                'clientId' => $clientId,
                                'locationId' => $locationId,
                                'terminalId' => '',
                                'terminalDateTime' => date('Y-m-d H:i:s'),
                                'initiatorType' => '',
                                'initiatorId' => '',
                                'initiatorPassword' => '',
                                'externalId' => '',
                                'batchId' => '',
                                'batchReference' => '',
                                ),
                            'account' => array(
                                'accountId' => $txtCardNumber,//$txtPhoneNumber
                                'pin' => '',
                                'entryType' => 'W',
                                ),
                            'customerInfo' => array(
                                'customerType' => '',
                                'firstName' => '',
                                'middleName' => '',
                                'lastName' => '',
                                'address1' => '',
                                'address2' => '',
                                'city' => '',
                                'state' => '',
                                'postal' => '',
                                'country' => '',
                                'mailPref' => '',
                                'phone' => $txtCardNumber,
                                'isMobile' => 'Y',
                                'phonePref' => '',
                                'email' => '',
                                'emailPref' => '',
                                'birthday' => '',
                                'anniversary' => '',
                                'gender' => '',
                                ),
                            ),
                        'netCredentials' => array(
                            'UserName' => $userName,
                            'Password' => $password,
                            'Domain' => '',
                        ),
                    ),
                );
            $result = self::makeRequest($paramArray, 'Inquiry');

            return $result;
        } else {
            return 0;
        }
    }
    
    /**
     * handleError.
     *
     * This function is use to handle errors
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     *
     * @param string $error_string
     * @param bool $fail_silently
     * @return string
     * @throws LoyaltyboxException 
     * 
     * @static
     */
    public static function handleError($error_string, $fail_silently = null)
    {
        if ($fail_silently === null) {
            $fail_silently = self::$fail_silently;
        }
        $error = new LoyaltyboxException($error_string);
        if ($fail_silently) {
            $log = PHP_EOL.PHP_EOL.PHP_EOL
                          .'Exception at '.date('Y-m-d H:i:s.uP').PHP_EOL
                          .self::$signature.PHP_EOL
                          .$error->getMessage().PHP_EOL;
            error_log($log, self::$error_log_type, self::$error_log_destination, self::$error_log_extra);
            error_log($error, self::$error_log_type, self::$error_log_destination, self::$error_log_extra);

            return;
        } else {
            throw $error;
        }
        return;
    }
    
    /**
     * init.
     *
     * A method to intitialise loyaltybox options
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $clientId
     * @param int $locationId
     * @param string $userName
     * @param string $password
     * @param string $friendly_message
     *
     * @static
     */
    public static function init($rewardProgrammeName,$clientId, $locationId, $userName, $password, $friendly_message,$lb_request_id)
    {
        self::$rewardProgrammeName = filter_var($rewardProgrammeName, FILTER_SANITIZE_STRING);
        self::$clientId = filter_var($clientId, FILTER_SANITIZE_STRING);
        self::$locationId = filter_var($locationId, FILTER_SANITIZE_STRING);
        self::$userName = filter_var($userName, FILTER_SANITIZE_STRING);
        self::$password = $password;
        self::$friendly_message = filter_var($friendly_message, FILTER_SANITIZE_STRING);
        self::$lb_request_id = $lb_request_id;
    }
    
    /**
    * issuePoints.
    *
    * Submit issue points request to LoyaltyBox.
    *
    * @version 1.0.0
    *
    * @author Double Eye
    *
    * @since 1.0.0
    * @access public
    *
    * @param string $CardOrPhoneNumber
    * @param array $lineItems
    * @param double $issuePoints
    *
    * @return array|mixed|void
    * @static
    */
    public static function issuePoints($CardOrPhoneNumber,$lineItems,$issuePoints)
    {
        $clientId = self::$clientId;
        $locationId = self::$locationId;
        $userName = self::$userName;
        $password = self::$password;
        $paramArray = array(
                'UpdateSale' => array(
                    'p_objrequest' => array(
                        'transactionTypeId' => 1,
                        'tenderTypeId' => 1,
                        'standardHeader' => array(
                            'requestId' => self::$lb_request_id,
                            'localeId' => '',
                            'systemId' => '',
                            'clientId' => $clientId,
                            'locationId' => $locationId,
                            'terminalId' => '',
                            'terminalDateTime' => date('Y-m-d H:i:s'),
                            'initiatorType' => '',
                            'initiatorId' => '',
                            'initiatorPassword' => '',
                            'externalId' => '',
                            'batchId' => '',
                            'batchReference' => '',
                            ),
                        'account' => array(
                            'accountId' => $CardOrPhoneNumber,//$txtPhoneNumber
                            'pin' => '',
                            'entryType' => 'W',
                            ),
                        'activating' => 'Y',
                        'amount' => array(
                            'valueCode' => 'ZAR',
                            'enteredAmount' => $issuePoints,//,
                            //'nsfAllowed' => 'N'
                            ),
                        'lineItems' => $lineItems,
                        ),
                    'netCredentials' => array(
                        'UserName' => $userName,
                        'Password' => $password,
                        'Domain' => '',
                    ),
                ),
            );
        $result = self::makeRequest($paramArray, 'UpdateSale');
       
        return $result;
    }
    
    /**
    * logError.
    *
    * A method to log errors information to disk
    *
    * @version 1.0.0
    *
    * @author Double Eye
    *
    * @since 1.0.0
    * @access public
    *
    * @param      $info
    * @param bool $first_line
    *
    * @static
    */
    public static function logError($info, $first_line = false)
    {
        $log = date('Y-m-d H:i:s.uP')." => ".$info.PHP_EOL;
        if ($first_line) {
            $log = PHP_EOL.PHP_EOL.$log;
        }
        file_put_contents(LOYALTYBOX_ERROR_LOG, $log, FILE_APPEND);
    }
    
    /**
     * makeRequest.
     *
     * Builds a request from an array and sends it to Loyalty Box
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $paramArray
     * @param string $methodCall
     * @param bool $getDetails
     * 
     * @return array|mixed|void
     * 
     * @static
     *
     */
    public static function makeRequest($paramArray = null, $methodCall = null, $getDetails = FALSE)
    {
        $url = self::$endpoint;
        $time_start = microtime(true);
        //ini_set('default_socket_timeout',1);
        @session_start();
        $_SESSION['time_out_error'] = "";
        try {
            
            $client = new SoapClient($url, array('trace' => 1));
            if ($methodCall == 'Enrollment') {
                $resultInquiry = $client->__call('Inquiry', $paramArray);
                $resultUpdateClient = $client->__call('UpdateClient', $paramArray);

                return array('Inquiry' => $resultInquiry, 'UpdateClient' => $resultUpdateClient);
            } else {
                $result = $client->__call($methodCall, $paramArray);
                if(REQUEST_DEBUG_MODE){
                    @ob_start();
                        print_r($result);
                        $resultContent = ob_get_contents();
                    @ob_clean();
                    $request = $client->__getLastRequest();
                    file_put_contents(LOYALTYBOX_REQUEST_LOG, " ".$methodCall."-".date('Y-m-d H:i:s.uP')." ".PHP_EOL." ", FILE_APPEND);
                    file_put_contents(LOYALTYBOX_REQUEST_LOG, $request, FILE_APPEND);
                    file_put_contents(LOYALTYBOX_REQUEST_LOG, $resultContent, FILE_APPEND);
                }
                if ($getDetails) {
                    $request = $client->__getLastRequest();
                    return array('request' => $request,'response' => $result);
                } else {
                    return $result;
                }
            }
        } catch (Exception $e) {
            $time_request = (microtime(true)-$time_start);
            if(ini_get('default_socket_timeout') < $time_request) {
                $error = array('message' => "Soap client Timeout error! ".$e->getMessage(),'code' => $e->getCode());
                $_SESSION['time_out_error'] = "Unable to reach Loyalty Box - please check that port 80 is open.";
                return self::handleError($error);
            } else {
                $error = array('message' => $e->getMessage(),'code' => $e->getCode());
                return self::handleError($error);
            }
            
        }
    }
    
    /**
    * newBasketState.
    *
    * Create basket state.
    *
    * @version 1.0.0
    *
    * @author Double Eye
    *
    * @since 1.0.0
    * @access public
    *
    * @param int $cartId
    * @param int $merchantId
    * @param double $basketTotal
    * @param int $lbRef
    * @param double $discounts
    * @param string $basketState
    * @param double $earnPoints
    * @param double $redeemPoints
    * @param int $basketId
    *
    * @static
    */
    public static function newBasketState($cartId, $merchantId, $basketTotal, $lbRef, $lbCustomerName, $discounts, $basketState, $earnPoints =0, $redeemPoints =0, $basketId = 0,$orderId = 0,$isLoyaltyIssued = 0)
    {
        $data = array(
            'cartId' =>$cartId,
            'merchantId' =>$merchantId,
            'basketTotal' =>$basketTotal,
            'lbRef' =>$lbRef,
            'lbCustomerName' =>$lbCustomerName,
            'earnPoints' =>$earnPoints,
            'redeemPoints' =>$redeemPoints,
            'discounts' =>$discounts,
            'basketState' =>$basketState,
            'basketId' =>$basketId,
            'orderId' =>$orderId,
            'isLoyaltyIssued' =>$isLoyaltyIssued
        );
        $resultData = array();
        if($basketId > 0)
        {
            $path = 'basket/'.$basketId;
            $resultData = self::state_api_make_request($path, $data, null, 'PUT');
        }
        else
            $resultData = self::state_api_make_request('basket', $data, null, 'POST');
        self::debug_log('CALLED STATE API: Status-'.$resultData['status'].' Message-'.$resultData['message'],true);
        return $resultData;
    }
    
    /**
    * newOrderStatus.
    *
    * Get order status for new order after checkout completed successfully.
    *
    * @version 1.0.0
    *
    * @author Double Eye
    *
    * @since 1.0.0
    * @access public
    *
    * @param int $orderId
    * @param int $merchantId
    *
    * @static
    * 
    * @return bool 
    */
    public static function newOrderStatus($orderId,$merchantId){
        $path = "basket/order/".$orderId."/".$merchantId;
        $orderStatus = self::state_api_make_request($path, null, null, 'GET');
        if(!empty($orderStatus)){
            return $orderStatus['loyalty_issued'];
        }
        else
            return 0;
    }

        /**
    * registerUser.
    *
    * Calls LoyaltyBox and attempts to register a new user with the supplied details for this store.
    *
    * @version 1.0.0
    *
    * @author Double Eye
    *
    * @since 1.0.0
    * @access public
    *
    * @param $txtName
    * @param $txtEmail
    * @param $txtPhoneNumber
    *
    * @return array|mixed|void
    * @static
    */
    public static function registerUser($txtName,$txtEmail,$txtPhoneNumber)
    {
        $returnArr = array();
        $clientId = self::$clientId;
        $locationId = self::$locationId;
        $userName = self::$userName;
        $password = self::$password;
        $paramArray = array(
                    'Inquiry' => array(
                        'p_objRequest' => array(
                            'standardHeader' => array(
                                'requestId' => self::$lb_request_id,
                                'localeId' => '',
                                'systemId' => '',
                                'clientId' => $clientId,
                                'locationId' => $locationId,
                                'terminalId' => '',
                                'terminalDateTime' => date('Y-m-d H:i:s'),
                                'initiatorType' => '',
                                'initiatorId' => '',
                                'initiatorPassword' => '',
                                'externalId' => '',
                                'batchId' => '',
                                'batchReference' => '',
                                ),
                            'account' => array(
                                'accountId' => $txtPhoneNumber,//$txtPhoneNumber
                                'pin' => '',
                                'entryType' => 'W',
                                ),
                            'customerInfo' => array(
                                'customerType' => '',
                                'firstName' => $txtName,
                                'middleName' => '',
                                'lastName' => '',
                                'address1' => '',
                                'address2' => '',
                                'city' => '',
                                'state' => '',
                                'postal' => '',
                                'country' => '',
                                'mailPref' => '',
                                'phone' => $txtPhoneNumber,
                                'isMobile' => 'Y',
                                'phonePref' => '',
                                'email' => $txtEmail,
                                'emailPref' => '',
                                'birthday' => '',
                                'anniversary' => '',
                                'gender' => '',
                                ),
                            ),
                        'netCredentials' => array(
                            'UserName' => $userName,
                            'Password' => $password,
                            'Domain' => '',
                        ),
                    ),
                );
            $result = self::makeRequest($paramArray, 'UpdateClient');
            
            self::debug_log("LB API Called : UpdateClient to register new user", true);
            
            $UpdateClientResult = $result->UpdateClientResult;
            $standardHeader = $UpdateClientResult->standardHeader;

            if ($standardHeader->status == 'A') {
                //$result = 'You are successfully registered with Loyalty Box and your Card number is:8888888888.';
                self::debug_log("Response : New user is successfully registered with Loyalty Box. ", true);
                $customerInfo = $UpdateClientResult->customerInfo;
                $returnArr = array('status' => 1, 'message' => self::$friendly_message);
                
                // perform login for current registered user
                    $balances = $UpdateClientResult->balances;
                    $firstName = "Guest";
                    if (!empty($customerInfo->firstName)) {
                        $firstName = $customerInfo->firstName;
                    }
                    
                    $lb_discount = 0;
                    $lb_discount_difference = 0;
                    $lb_discount_exchangeRate = 0;

                    $lb_points = 0;
                    $lb_points_difference = 0;
                    $lb_points_exchangeRate = 0;

                    $lb_zar = 0;
                    $lb_zar_difference = 0;
                    $lb_zar_exchangeRate = 0;

                    if (!empty($balances)) {
                        foreach ($balances->balance as $lb_bal) {
                            if ($lb_bal->valueCode == 'Discount') {
                                $lb_discount = $lb_bal->amount;
                                $lb_discount_difference = $lb_bal->difference;
                                $lb_discount_exchangeRate = $lb_bal->exchangeRate;
                            } elseif ($lb_bal->valueCode == 'Points') {
                                $lb_points = $lb_bal->amount;
                                $lb_points_difference = $lb_bal->difference;
                                $lb_points_exchangeRate = $lb_bal->exchangeRate;
                            } elseif ($lb_bal->valueCode == 'ZAR') {
                                $lb_zar = $lb_bal->amount;
                                $lb_zar_difference = $lb_bal->difference;
                                $lb_zar_exchangeRate = $lb_bal->exchangeRate;
                            }
                        }
                    }

                    @session_start();
                    $_SESSION['LB_Session'] = array(
                        'Customer Name' => $firstName,
                        'Phone Number' => $customerInfo->phone,
                        'Customer_email' => $customerInfo->email,
                        'lb_discount' => $lb_discount,
                        'lb_discount_difference' => $lb_discount_difference,
                        'lb_discount_exchangeRate' => $lb_discount_exchangeRate,
                        'lb_points' => $lb_points,
                        'lb_points_difference' => $lb_points_difference,
                        'lb_points_exchangeRate' => $lb_points_exchangeRate,
                        'lb_zar' => $lb_zar,
                        'lb_zar_difference' => $lb_zar_difference,
                        'lb_zar_exchangeRate' => $lb_zar_exchangeRate,
                    );
                    //print_r($_SESSION['LB_Session']);
                    $replaceBtn = "<div class='loyaltybox-info-contain'><strong>Hi " . $_SESSION['LB_Session']['Customer Name'] . "</strong>,</br>You have " . $_SESSION['LB_Session']['lb_points'] . " Points in your <strong>" . self::$rewardProgrammeName . "</strong> account.</div>";
                    $returnArr = array('status' => 1, 'message' => self::$friendly_message, 'replaceBtn' => $replaceBtn);

            // end login
                
                
            } else {
                $errorMessage = $UpdateClientResult->errorMessage;
                $errorCode = $errorMessage->errorCode;
                self::debug_log("Response : ".$errorMessage->briefMessage, true);
                if ($errorCode == 6) {
                    // DO IT AGAIN TO ENROL
                    //echo "do enrollment";
                    $resultInquiry = self::makeRequest($paramArray, 'Inquiry');
                    self::debug_log("LB API Called : Inquiry - to enroll new user", true);
                    
                    $result = self::makeRequest($paramArray, 'UpdateClient');
                    self::debug_log("LB API Called : UpdateClient - to enroll new user", true);
                    
                    $UpdateClientResult = $result->UpdateClientResult;
                    $standardHeader = $UpdateClientResult->standardHeader;
                    if ($standardHeader->status == 'A') {
                        //$result = 'You are successfully registered with Loyalty Box and your Card number is:8888888888.';
                        $customerInfo = $UpdateClientResult->customerInfo;
                        $returnArr = array('status' => 1, 'message' => self::$friendly_message);
                        // perform login for current registered user
                                $balances = $UpdateClientResult->balances;
                                $firstName = "Guest";
                                if (!empty($customerInfo->firstName)) {
                                    $firstName = $customerInfo->firstName;
                                }

                                $lb_discount = 0;
                                $lb_discount_difference = 0;
                                $lb_discount_exchangeRate = 0;

                                $lb_points = 0;
                                $lb_points_difference = 0;
                                $lb_points_exchangeRate = 0;

                                $lb_zar = 0;
                                $lb_zar_difference = 0;
                                $lb_zar_exchangeRate = 0;

                                if (!empty($balances)) {
                                    foreach ($balances->balance as $lb_bal) {
                                        if ($lb_bal->valueCode == 'Discount') {
                                            $lb_discount = $lb_bal->amount;
                                            $lb_discount_difference = $lb_bal->difference;
                                            $lb_discount_exchangeRate = $lb_bal->exchangeRate;
                                        } elseif ($lb_bal->valueCode == 'Points') {
                                            $lb_points = $lb_bal->amount;
                                            $lb_points_difference = $lb_bal->difference;
                                            $lb_points_exchangeRate = $lb_bal->exchangeRate;
                                        } elseif ($lb_bal->valueCode == 'ZAR') {
                                            $lb_zar = $lb_bal->amount;
                                            $lb_zar_difference = $lb_bal->difference;
                                            $lb_zar_exchangeRate = $lb_bal->exchangeRate;
                                        }
                                    }
                                }

                                @session_start();
                                $_SESSION['LB_Session'] = array(
                                    'Customer Name' => $firstName,
                                    'Phone Number' => $customerInfo->phone,
                                    'Customer_email' => $customerInfo->email,
                                    'lb_discount' => $lb_discount,
                                    'lb_discount_difference' => $lb_discount_difference,
                                    'lb_discount_exchangeRate' => $lb_discount_exchangeRate,
                                    'lb_points' => $lb_points,
                                    'lb_points_difference' => $lb_points_difference,
                                    'lb_points_exchangeRate' => $lb_points_exchangeRate,
                                    'lb_zar' => $lb_zar,
                                    'lb_zar_difference' => $lb_zar_difference,
                                    'lb_zar_exchangeRate' => $lb_zar_exchangeRate,
                                );
                                //print_r($_SESSION['LB_Session']);
                                $replaceBtn = "<div class='loyaltybox-info-contain'><strong>Hi " . $_SESSION['LB_Session']['Customer Name'] . "</strong>,</br>You have " . $_SESSION['LB_Session']['lb_points'] . " Points in your <strong>" . self::$rewardProgrammeName . "</strong> account.</div>";
                                $returnArr = array('status' => 1, 'message' => self::$friendly_message, 'replaceBtn' => $replaceBtn);

                        // end login
                    } else {
                        $errorMessage = $UpdateClientResult->errorMessage;
                        $errorCode = $errorMessage->errorCode;
                        self::debug_log("Response : ".$errorMessage->briefMessage, true);
                        $returnArr = array('status' => 0, 'message' => $errorMessage->briefMessage.$_SESSION['time_out_error']);
                    }
                    // END OF ENROL
                } else {
                    self::debug_log("Response : ".$errorMessage->briefMessage, true);
                    $returnArr = array('status' => 0, 'message' => $errorMessage->briefMessage.$_SESSION['time_out_error']);
                }
                
            }
        return $returnArr;
    }
    
    /**
    * redeemPoints.
    *
    * Submit redeem points for cash to LoyaltyBox.
    *
    * @version 1.0.0
    *
    * @author Double Eye
    *
    * @since 1.0.0
    * @access public
    *
    * @param string $CardOrPhoneNumber
    * @param array $lineItems
    * @param double $txtRedeemPoints
    *
    * @return array|mixed|void
    * @static
    */
    public static function redeemPoints($CardOrPhoneNumber,$lineItems,$txtRedeemPoints)
    {
        $clientId = self::$clientId;
        $locationId = self::$locationId;
        $userName = self::$userName;
        $password = self::$password;
        $paramArray = array(
                'UpdateSale' => array(
                    'p_objrequest' => array(
                        'transactionTypeId' => 4,
                        'tenderTypeId' => 5,
                        'standardHeader' => array(
                            'requestId' => self::$lb_request_id,
                            'localeId' => '',
                            'systemId' => '',
                            'clientId' => $clientId,
                            'locationId' => $locationId,
                            'terminalId' => '',
                            'terminalDateTime' => date('Y-m-d H:i:s'),
                            'initiatorType' => '',
                            'initiatorId' => '',
                            'initiatorPassword' => '',
                            'externalId' => '',
                            'batchId' => '',
                            'batchReference' => '',
                            ),
                        'account' => array(
                            'accountId' => $CardOrPhoneNumber,//$txtPhoneNumber
                            'pin' => '',
                            'entryType' => 'K',
                            ),
                        'activating' => '',
                        'amount' => array(
                            'valueCode' => 'Points',
                            'enteredAmount' => $txtRedeemPoints,//,
                            //'nsfAllowed' => 'N'
                            ),
                        'customerInfo' => array(
                            'customerType' => '',
                            'firstName' => '',
                            'middleName' => '',
                            'lastName' => '',
                            'address1' => '',
                            'address2' => '',
                            'city' => '',
                            'state' => '',
                            'postal' => '',
                            'country' => '',
                            'mailPref' => '',
                            'phone' => '',
                            'isMobile' => '',
                            'phonePref' => '',
                            'email' => '',
                            'emailPref' => '',
                            'birthday' => '',
                            'anniversary' => '',
                            'gender' => '',
                            ),
                        'lineItems' => $lineItems,
                        ),
                    'netCredentials' => array(
                        'UserName' => $userName,
                        'Password' => $password,
                        'Domain' => '',
                    ),
                ),
            );
        $result = self::makeRequest($paramArray, 'UpdateSale');
       
        return $result;
    }
    
    /**
     * set_error_log.
     *
     * This function is use to set error options parameters
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     *
     * @param int $type
     * @param string $destination
     * @param string $extra 
     *
     * @static
     */
    public static function set_error_log($type, $destination = '', $extra = '')
    {
        self::$error_log_type = $type;
        self::$error_log_destination = $destination;
        self::$error_log_extra = $extra;
    }
    
    /**
    * state_api_make_request.
    *
    * Builds a request from an array and sends it to State API Server
    *
    * @version 1.0.0
    *
    * @author Double Eye
    *
    * @since 1.0.0
    * @access public
    *
    * @param string $path
    * @param array $data
    * @param bool $fail_silently
    * @param string $method
    * 
    * @return array|mixed|void
    * 
    * @static
    *
    * @throws LoyaltyboxException
    */
    public static function state_api_make_request($path, $data=null, $fail_silently=null, $method=null)
    {
        $url = self::$stateAPIendpoint.$path;
        if(!$data) $data = array();
        $ua = array(
            'bindings_version'  => self::VERSION,
            'application'       => self::$signature,
            'lang'              => 'PHP',
            'lang_version'      => phpversion(),
            'publisher'         => 'Loaylty Box',
        );  
        $data_string = json_encode($data);
        
        // Set Request Options
        $curlConfig = array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $data_string,
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            //CURLOPT_USERPWD        => self::$secret.':',
            CURLOPT_HTTPHEADER     => array(                                                                          
                'Content-Type: application/json',                                                                                
                'Content-Length: ' . strlen($data_string),
                'X-LB-Client-User-Agent: '. json_encode($ua),
            ),
        );
        
        //Make HTTP Request
        $ch = curl_init();
        curl_setopt_array($ch, $curlConfig);
        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        // Check for HTTP Error
        if($content_type!='application/json'){
            $error = array(
                'code' => $http_status,
                'message' => 'HTTP Error: '.$http_status,
            );
            return self::handleError($error, $fail_silently);
        }
        
        // Load response
        $response = json_decode($response, TRUE);
        if(array_key_exists('error', $response))
        if($response['error'])
            return self::handleError($response['error'], $fail_silently);
        
        return $response['result'];
    }
    
    /**
    * sendCartUpdate.
    *
    * @version 1.0.0
    *
    * @author Double Eye
    *
    * @since 1.0.0
    * @access public
    *
    * @param string $txtPhoneNumber
    * @param double $CartContentsTotal
    * @param array $lineItems
    * @param bool $CommitTransaction
    *
    * @return array|mixed|void
    * @static
    */
    public static function sendCartUpdate($txtPhoneNumber,$CartContentsTotal,$lineItems = array(), $CommitTransaction = 0)
    {
        @session_start();
        $allowedDiscount = 0;
        $clientId = self::$clientId;
        $locationId = self::$locationId;
        $userName = self::$userName;
        $password = self::$password;

        $paramArray = array(
                    'RequestLineItemRedemption' => array(
                        'p_objRequest' => array(
                            'standardHeader' => array(
                                'requestId' => self::$lb_request_id,
                                'localeId' => '',
                                'systemId' => '',
                                'clientId' => $clientId,
                                'locationId' => $locationId,
                                'terminalId' => '',
                                'terminalDateTime' => date('Y-m-d H:i:s'),
                                'initiatorType' => '',
                                'initiatorId' => '',
                                'initiatorPassword' => '',
                                'externalId' => '',
                                'batchId' => '',
                                'batchReference' => '',
                                ),
                            'account' => array(
                                'accountId' => $txtPhoneNumber,//$txtPhoneNumber
                                'pin' => '',
                                'entryType' => 'K',
                                ),
                            'activating' => '',
                            'amount' => array(
                                'valueCode' => 'Discount',
                                'enteredAmount' => $CartContentsTotal,//,
                                //'nsfAllowed' => 'N'
                                ),
                            'customerInfo' => array(
                                'customerType' => '',
                                'firstName' => '',
                                'middleName' => '',
                                'lastName' => '',
                                'address1' => '',
                                'address2' => '',
                                'city' => '',
                                'state' => '',
                                'postal' => '',
                                'country' => '',
                                'mailPref' => '',
                                'phone' => '',
                                'isMobile' => '',
                                'phonePref' => '',
                                'email' => '',
                                'emailPref' => '',
                                'birthday' => '',
                                'anniversary' => '',
                                'gender' => '',
                                ),
                            'lineItems' => $lineItems,
                            'CommitTransaction' => $CommitTransaction,
                            ),
                        'netCredentials' => array(
                            'UserName' => $userName,
                            'Password' => $password,
                            'Domain' => '',
                        ),
                    ),
                );
        $result = self::makeRequest($paramArray, 'RequestLineItemRedemption');

        $RequestLineItemRedemptionResult = $result->RequestLineItemRedemptionResult;
        $standardHeader = $RequestLineItemRedemptionResult->standardHeader;
        self::debug_log("LB API called : RequestLineItemRedemption", true);
        if ($standardHeader->status == 'A') {
            $balances = $RequestLineItemRedemptionResult->balances;
            $Balance = $balances->Balance;

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

            self::debug_log("LB API called : got Discount".$allowedDiscount."%", true);
            /* NO NEED TO COMMITE TRANSACTIONS BEFORE ACTUAL PAYMENT 
                $paramArray['RequestLineItemRedemption']['p_objRequest']['CommitTransaction'] = 1;
                $result = self::makeRequest($paramArray, 'RequestLineItemRedemption');
            */
        }
        return $allowedDiscount;
    }

    /**
    * sendCartFinal.
    *
    * Submit final cart to Loyalty Box.
    *
    * @version 1.0.0
    *
    * @author Double Eye
    *
    * @since 1.0.0
    * @access public
    * 
    * @param string $txtPhoneNumber
    * @param double $CartContentsTotal
    * @param array $lineItems
    * @param bool $CommitTransaction
    *
    * @return array|mixed|void
    * @static
    */
    public static function sendCartFinal($txtPhoneNumber,$CartContentsTotal,$lineItems,$CommitTransaction)
    {
        $clientId = self::$clientId;
        $locationId = self::$locationId;
        $userName = self::$userName;
        $password = self::$password;

        $paramArray = array(
                    'RequestLineItemRedemption' => array(
                        'p_objRequest' => array(
                            'standardHeader' => array(
                                'requestId' => self::$lb_request_id,
                                'localeId' => '',
                                'systemId' => '',
                                'clientId' => $clientId,
                                'locationId' => $locationId,
                                'terminalId' => '',
                                'terminalDateTime' => date('Y-m-d H:i:s'),
                                'initiatorType' => '',
                                'initiatorId' => '',
                                'initiatorPassword' => '',
                                'externalId' => '',
                                'batchId' => '',
                                'batchReference' => '',
                                ),
                            'account' => array(
                                'accountId' => $txtPhoneNumber,//$txtPhoneNumber
                                'pin' => '',
                                'entryType' => 'K',
                                ),
                            'activating' => '',
                            'amount' => array(
                                'valueCode' => 'Discount',
                                'enteredAmount' => $CartContentsTotal,//,
                                //'nsfAllowed' => 'N'
                                ),
                            'customerInfo' => array(
                                'customerType' => '',
                                'firstName' => '',
                                'middleName' => '',
                                'lastName' => '',
                                'address1' => '',
                                'address2' => '',
                                'city' => '',
                                'state' => '',
                                'postal' => '',
                                'country' => '',
                                'mailPref' => '',
                                'phone' => '',
                                'isMobile' => '',
                                'phonePref' => '',
                                'email' => '',
                                'emailPref' => '',
                                'birthday' => '',
                                'anniversary' => '',
                                'gender' => '',
                                ),
                            'lineItems' => $lineItems,
                            'CommitTransaction' => $CommitTransaction,
                            ),
                        'netCredentials' => array(
                            'UserName' => $userName,
                            'Password' => $password,
                            'Domain' => '',
                        ),
                    ),
                );
        $result = self::makeRequest($paramArray, 'RequestLineItemRedemption');

        return $result;
    }
    
    /**
     * verifyUser.
     *
     * Verify user.
     *
     * @version 1.0.0
     *
     * @author Double Eye
     *
     * @since 1.0.0
     * @access public
     *
     * @param txtCardNumber
     *
     * @return array|mixed|void
     * @static
     */
    public static function verifyUser($txtCardNumber)
    {
        $returnArr = array();
        $clientId = self::$clientId;
        $locationId = self::$locationId;
        $userName = self::$userName;
        $password = self::$password;
        $paramArray = array(
                    'Inquiry' => array(
                        'p_objRequest' => array(
                            'standardHeader' => array(
                                'requestId' => self::$lb_request_id,
                                'localeId' => '',
                                'systemId' => '',
                                'clientId' => $clientId,
                                'locationId' => $locationId,
                                'terminalId' => '',
                                'terminalDateTime' => date('Y-m-d H:i:s'),
                                'initiatorType' => '',
                                'initiatorId' => '',
                                'initiatorPassword' => '',
                                'externalId' => '',
                                'batchId' => '',
                                'batchReference' => '',
                                ),
                            'account' => array(
                                'accountId' => $txtCardNumber,
                                'pin' => '',
                                'entryType' => 'K',
                                ),
                            'customerInfo' => array(
                                'customerType' => '',
                                'firstName' => '',
                                'middleName' => '',
                                'lastName' => '',
                                'address1' => '',
                                'address2' => '',
                                'city' => '',
                                'state' => '',
                                'postal' => '',
                                'country' => '',
                                'mailPref' => '',
                                'phone' => $txtCardNumber,
                                'isMobile' => 'Y',
                                'phonePref' => '',
                                'email' => '',
                                'emailPref' => '',
                                'birthday' => '',
                                'anniversary' => '',
                                'gender' => '',
                                ),
                            ),
                        'netCredentials' => array(
                            'UserName' => $userName,
                            'Password' => $password,
                            'Domain' => '',
                        ),
                    ),
                );

        $result = self::makeRequest($paramArray, 'Inquiry');
        $InquiryResult = $result->InquiryResult;
            $standardHeader = $InquiryResult->standardHeader;
            if ($standardHeader->status == 'A') {
                                
                $customerInfo = $InquiryResult->customerInfo;
                $balances = $InquiryResult->balances;

                $firstName = "Guest";
                if (!empty($customerInfo->firstName)) {
                    $firstName = $customerInfo->firstName;
                }

                $lb_discount = 0;
                $lb_discount_difference = 0;
                $lb_discount_exchangeRate = 0;

                $lb_points = 0;
                $lb_points_difference = 0;
                $lb_points_exchangeRate = 0;

                $lb_zar = 0;
                $lb_zar_difference = 0;
                $lb_zar_exchangeRate = 0;

                if (!empty($balances)) {
                    foreach ($balances->balance as $lb_bal) {
                        if ($lb_bal->valueCode == 'Discount') {
                            $lb_discount = $lb_bal->amount;
                            $lb_discount_difference = $lb_bal->difference;
                            $lb_discount_exchangeRate = $lb_bal->exchangeRate;
                        } elseif ($lb_bal->valueCode == 'Points') {
                            $lb_points = $lb_bal->amount;
                            $lb_points_difference = $lb_bal->difference;
                            $lb_points_exchangeRate = $lb_bal->exchangeRate;
                        } elseif ($lb_bal->valueCode == 'ZAR') {
                            $lb_zar = $lb_bal->amount;
                            $lb_zar_difference = $lb_bal->difference;
                            $lb_zar_exchangeRate = $lb_bal->exchangeRate;
                        }
                    }
                }

                @session_start();
                $_SESSION['LB_Session'] = array(
                            'Customer Name' => $firstName,
                            'Phone Number' => $customerInfo->phone,
                            'Customer_email' => $customerInfo->email,
                            'lb_discount' => $lb_discount,
                            'lb_discount_difference' => $lb_discount_difference,
                            'lb_discount_exchangeRate' => $lb_discount_exchangeRate,
                            'lb_points' => $lb_points,
                            'lb_points_difference' => $lb_points_difference,
                            'lb_points_exchangeRate' => $lb_points_exchangeRate,
                            'lb_zar' => $lb_zar,
                            'lb_zar_difference' => $lb_zar_difference,
                            'lb_zar_exchangeRate' => $lb_zar_exchangeRate,
                        );
                //print_r($_SESSION['LB_Session']);
                $replaceBtn = "<div class='loyaltybox-info-contain'><strong>Hi ".$_SESSION['LB_Session']['Customer Name']."</strong>,</br>You have ".$_SESSION['LB_Session']['lb_points']." Points in your <strong>".self::$rewardProgrammeName."</strong> account.</div>";
                $returnArr = array('status' => 1, 'message' => "You have successfully logged in with ".self::$rewardProgrammeName.".", 'replaceBtn' => $replaceBtn);
            } else {
                $errorMessage = $InquiryResult->errorMessage;
                $errorCode = $errorMessage->errorCode;
                $returnArr = array('status' => 0, 'message' => $errorMessage->briefMessage.$_SESSION['time_out_error']);
            }
        return $returnArr;
    }

}
