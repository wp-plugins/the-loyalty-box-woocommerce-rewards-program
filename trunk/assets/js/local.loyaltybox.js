 function checkLength( o, n, min, max ) {
    if ( o.val().length > max || o.val().length < min ) {
    o.addClass( "frm_error" );
    updateTips( "Length of " + n + " must be between " +
    min + " and " + max + "." );
    return false;
    } else {
    return true;
    }
}
 function checkLengthLogin( o, n, min, max ) {
    if ( o.val().length > max || o.val().length < min ) {
    o.addClass( "frm_error" );
    updateTipsLogin( "Length of " + n + " must be between " +
    min + " and " + max + "." );
    return false;
    } else {
    return true;
    }
}

 function checkLoginFixedLength( o, n, min, max ) {
    if ( o.val().length == max || o.val().length == min ) {
        return true;
    } else {
        o.addClass( "frm_error" );
        updateTipsLogin( "Length of " + n + " must be either " +
        min + " or " + max + "." );
        return false;
    }
}

function checkRegexp( o, regexp, n ) {
    if ( !( regexp.test( o.val() ) ) ) {
    o.addClass( "frm_error" );
    updateTips( n );
    return false;
    } else {
    return true;
    }
}
 function updateTips( t ) {
    tips
    .text( t )
    .addClass( "frm_error" );
    
}

function updateTipsLogin( t ) {
    tipsLogin
    .text( t )
    .addClass( "frm_error" );
    
}
 function checkFixedLen( o, n, fixLen) {
    if ( o.val().length != fixLen) {
    o.addClass( "frm_error" );
    updateTips( "Length of " + n + " must be " +
    fixLen + "." );
    return false;
    } else {
    return true;
    }
}
    function validateUser() {
                    var valid = true;
                    allFields.removeClass( "frm_error" );
                    tips.removeClass( "frm_error" );
                    tips.removeClass( "frm_success" );
                    tips.text( "All form fields are required." );
                    valid = valid && checkLength( txtName, "Full Name", 2, 50 );
                    //valid = valid && checkRegexp( txtName, alphaRegex, "Please enter alphabets, space or dash only." );
                    valid = valid && checkLength( txtEmail, "Email", 2, 100 );
                    valid = valid && checkRegexp( txtEmail, emailRegex, "Please enter valid email address." );
                    valid = valid && checkRegexp( txtPhoneNumber, numericRegex, "Please enter digits only." );
                    valid = valid && checkFixedLen( txtPhoneNumber, "Phone Number", 10);
                    return valid;
    }
    
    function validateLogin() {
           // return false;
                    var valid = true;
                    allFields.removeClass( "frm_error" );
                    tipsLogin.removeClass( "frm_error" );
                    tipsLogin.removeClass( "frm_success" );
                    tipsLogin.text( "All form fields are required." );
                    valid = valid && checkLoginFixedLength( txtCardNumber, "Card Number", 10, 15 );
                    return valid;
    }
    function setTBHeight(){
        if(jQuery("#TB_ajaxContent").length){
            jQuery("#TB_ajaxContent").css('height','auto');
        }
        else
            setTimeout(function(){ setTBHeight() }, 10);
    }
jQuery(document).ready(function($){
    
    //emailRegex = /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/,
    emailRegex = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i,
    alphaRegex = /^[a-zA-Z\-_\s]+$/,
    numericRegex = /^[0-9]+$/,
    txtCardNumber = $( "#txtCardNumber" ),
    txtName = $( "#txtName" ),
    txtEmail = $( "#txtEmail" ),
    txtPhoneNumber = $( "#txtPhoneNumber" ),
    allFields = $( [] ).add( txtCardNumber ).add( txtName ).add( txtEmail ).add( txtPhoneNumber ),
    tipsLogin = $( ".validateTipsloginLB" ),
    tips = $( ".validateTips" );
    
    $("#btnConnect").click(function(){
        setTBHeight();
        txtCardNumber.val('');
        txtName.val('');
        txtEmail.val('');
        txtPhoneNumber.val('');
        tips.text( "All form fields are required." );
        tipsLogin.text( "All form fields are required." );
        allFields.removeClass( "frm_error" );
        tipsLogin.removeClass( "frm_error" );
        tips.removeClass( "frm_error" );
        tipsLogin.removeClass( "frm_success" );
        tips.removeClass( "frm_success" );
    });
    
    $("#lbLogout").click(function(){
        var data = {
                        'action': 'logout_user'
                };
        $(".lb_loading").show();
        jQuery.post(loyaltybox_data.ajax_url, data, function(response) {
                if(response.status == '1'){
                    $(".lb_loading").hide();
                    $("#lbLogout").parent().html(response.message);
                    setTimeout(function(){ window.location.reload(); }, 500);
                }
                else{
                    $(".lb_loading").hide();
                }
        },'JSON');
    });
    
    var handleSubmit = true;
        $("#btnConnectLB").click(function(){
             if(validateUser()){
                $(".lb_loading").show();
                var data = {
                        'action': 'register_user',
                        'txtName': txtName.val(),
                        'txtEmail': txtEmail.val(),
                        'txtPhoneNumber': txtPhoneNumber.val(),
                        'hidd_nonce': $("#hidd_nonce").val()
                };
                //if(handleSubmit){
                    handleSubmit = false;
                    jQuery.post(loyaltybox_data.ajax_url, data, function(response) {
                        handleSubmit = true;
                            if(response.status == '1'){
                                $(".lb_loading").hide();
                                tips.text(response.message).addClass( "frm_success" );
                                txtName.val('');
                                txtEmail.val('');
                                txtPhoneNumber.val('');
                                setTimeout(function(){self.parent.tb_remove();},2000);
                                $("#btnConnect").parent().html(response.replaceBtn);
                                window.location.reload();
                            }
                            else{
                                tips.text(response.message).addClass( "frm_error" );
                                $(".lb_loading").hide();
                            }
                    },'JSON');
                //}
             }
        });
        
    $("#loginLB").hide();
    $("#lnkLBLogin").click(function(){
        $("#loginLB").show();
        $("#registerLB").hide();
    });
    
    $("#lnkLBRegister").click(function(){
        $("#loginLB").hide();
        $("#registerLB").show();
    });
    
    $("#btnLoginLB").click(function(){
        
            if(validateLogin()){
                $(".lb_loading").show();
                var data = {
                        'action': 'verify_user',
                        'txtCardNumber': txtCardNumber.val(),
                        'hidd_login_nonce': $("#hidd_login_nonce").val()
                };
                jQuery.post(loyaltybox_data.ajax_url, data, function(response) {
                        if(response.status == '1'){
                            tipsLogin.text(response.message).addClass( "frm_success" );
                            $("#btnConnect").parent().html(response.replaceBtn);
                            txtCardNumber.val('');
                            $(".lb_loading").hide();
                            setTimeout(function(){self.parent.tb_remove();},2000);
                            window.location.reload();
                        }
                        else{
                            tipsLogin.text(response.message).addClass( "frm_error" );
                            $(".lb_loading").hide();
                        }
                },'JSON');
            }
    });
    
    $(".showRedeemBox").click(function(){
        $("#redeemBox").toggle('display');
    });
    
    $("#btnRedeem").click(function(){
        $("#redeemMsg").removeClass('frm_error').text('');
        var txtPoints = $("#txtPoints").val();
        if(txtPoints.length > 0 && parseInt(txtPoints) > 0){
            $(".lb_loading_redeem").show();
                var data = {
                        'action': 'redeem_points',
                        'txtRedeemPoints': txtPoints,
                        'hidd_redeem_nonce': $("#hidd_redeem_nonce").val()
                };
                jQuery.post(loyaltybox_data.ajax_url, data, function(response) {
                        $(".lb_loading_redeem").hide();
                        if(response.status == '1'){
                            $("#redeemMsg").addClass('frm_success').text(response.message);
                            $("#txtPoints").val('');
                            setTimeout(function(){ window.location.reload(); }, 1000);
                        }
                        else{
                            $("#redeemMsg").addClass('frm_error').text(response.message);
                            $("#txtPoints").focus();
                        }
                },'JSON');
        }
        else{
            $("#redeemMsg").addClass('frm_error').text('Please enter point(s) greater than 0(zero).');
            $("#txtPoints").focus();
        }
    });
});