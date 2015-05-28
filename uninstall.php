<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

include_once plugin_dir_path(__FILE__).'loyaltybox.php';

//WC_Loyaltybox_Settings::db_uninstall();
delete_option(WC_Loyaltybox_Settings::OPT_NAME);
