<?php

/**
 * Purchase Orders Payment Module
 *
 * @package   paymentMethod
 * @copyright Copyright 2003-2010 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license   http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 *
 * /**
 *  some portions developed, copyrighted and brought to you by @proseLA (github)
 *  https://mxworks.cc
 *  copyright 2025 proseLA
 *
 *  consider a donation.  payment modules are the core of any shopping cart.
 *
 *  released under GPU
 *  https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 *
 *  use of this software constitutes acceptance of license
 *  mxworks will vigilantly pursue any violations of this license.
 *
 *  some portions of code may be copyrighted and licensed by www.zen-cart.com
 *
 *  09/2025  file: purchaseorders.php
 **/

class purchaseorders
{
    public $code, $title, $description, $enabled;
    private  $_check;
    public $sort_order, $order_status, $email_footer;

    function __construct()
    {
        global $order;

        $this->code = 'purchaseorders';
        $this->title = MODULE_PAYMENT_PURCHASEORDER_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_PURCHASEORDER_TEXT_DESCRIPTION;
        if (!defined('MODULE_PAYMENT_PURCHASEORDER_SORT_ORDER')) {
            $this->enabled = false;
            return;
        }
        $this->sort_order = MODULE_PAYMENT_PURCHASEORDER_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_PURCHASEORDER_STATUS == 'True') ? true : false);

        if ((int)MODULE_PAYMENT_PURCHASEORDER_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_PURCHASEORDER_ORDER_STATUS_ID;
        }

        if (is_object($order)) {
            $this->update_status();
        }

        global $db, $messageStack;
        if (!defined('MODULE_PAYMENT_PURCHASEORDER_ORDER_GROUP_ID')) {
            // If the new group limiter is not defined, add it and notify the admin
            // Add a group for Purchase Orders
            $insert_id = zen_create_customer_group("Purchase Orders: Enabled", "This group is authorized to use purchase orders.");

            if (is_numeric($insert_id)) $messageStack->add_session('Added customer group: <strong>Purchase Orders: Enabled</strong>.', 'success');

            $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Allowed Customer Group', 'MODULE_PAYMENT_PURCHASEORDER_ORDER_GROUP_ID', '$insert_id', 'Members of which customer group are allowed to use this method of payment?', '6', '0', 'zen_cfg_select_customer_group(', 'zen_get_customer_group_name', now())");
            $messageStack->add_session('Added new configuration value: <strong>Allowed Customer Group</strong>', 'success');
        }

        if (!defined('MODULE_PAYMENT_PURCHASEORDER_PO_EMAIL')) {
            $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Email address to receive POs:', 'MODULE_PAYMENT_PURCHASEORDER_PO_EMAIL', '" . STORE_OWNER_EMAIL_ADDRESS ."', 'Where should the Purchase Orders be emailed to?', '6', '2', now());");
            $messageStack->add_session('Added new configuration value: <strong>Email address to receive POs</strong>', 'success');
        }

        if (!defined('MODULE_PAYMENT_PURCHASEORDER_PO_FAX')) {
            $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Fax number to receive POs:', 'MODULE_PAYMENT_PURCHASEORDER_PO_FAX', '', 'Where should the Purchase Orders be faxed to?', '6', '2', now());");
            $messageStack->add_session('Added new configuration value: <strong>Fax number to receive POs</strong>', 'success');
        }

        if (!defined('MODULE_PAYMENT_PURCHASEORDER_PAYTO')) {
            $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Make payable to:', 'MODULE_PAYMENT_PURCHASEORDER_PAYTO', '" . STORE_OWNER ."', 'Who should payments be made payable to?', '6', '2', now());");
            $messageStack->add_session('Added new configuration value: <strong>Make POs Payable to:</strong>', 'success');
        }
    }

    function update_status()
    {
        global $order, $db;

        $cust_group = false;
        $pricing_group = false;

        if (($this->enabled == true) && defined('MODULE_PAYMENT_PURCHASEORDER_ORDER_GROUP_ID')) {
            if (MODULE_PAYMENT_PURCHASEORDER_ORDER_GROUP_ID > 0) {
                $cust_group = zen_customer_belongs_to_group((int)$_SESSION['customer_id'], MODULE_PAYMENT_PURCHASEORDER_ORDER_GROUP_ID, true);
            }
        }

        $group_query = $db->Execute("select customers_group_pricing from " . TABLE_CUSTOMERS . " where customers_id = '" . (int)$_SESSION['customer_id'] . "'");
        if ($this->enabled == true) {
            $check_flag_group = false;
            $check = $db->Execute("select group_name from " . TABLE_GROUP_PRICING . " where group_id = '" . $group_query->fields['customers_group_pricing'] . "'");
            if ($check->RecordCount() == 1) {
                $purchaseorder_prefix = "PO_";
                $check_prefix = substr($check->fields['group_name'], 0, strlen($purchaseorder_prefix));
                if (strcasecmp($check_prefix, $purchaseorder_prefix) == 0) {
                    $check_flag_group = true;
                }
            }
            if ($check_flag_group == false) {
                $pricing_group = false;
            }
        }

        // If the customer is a part of either authorized group, enable this module.
        $this->enabled = $pricing_group || $cust_group;

        if (($this->enabled == true) && ((int)MODULE_PAYMENT_PURCHASEORDER_ZONE > 0)) {
            $check_flag = false;
            $check = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PURCHASEORDER_ZONE . "' and zone_country_id = '" . $order->delivery['country']['id'] . "' order by zone_id");
            while (!$check->EOF) {
                if ($check->fields['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check->fields['zone_id'] == $order->delivery['zone_id']) {
                    $check_flag = true;
                    break;
                }
                $check->MoveNext();
            }
            if ($check_flag == false) {
                $this->enabled = false;
            }
        }

        // disable the module if the order only contains virtual products
        if ($this->enabled == true) {
            if ($order->content_type != 'physical') {
                $this->enabled = false;
            }
        }
    }

    function javascript_validation()
    {
        $validation_string = 'if (payment_value == "' . $this->code . '") {' . "\n" .
            '  var po_number = document.checkout_payment.po_number.value;' . "\n" .
            '  if (po_number.length == 0) {' . "\n" .
            '    error_message = error_message + "' . MODULE_PAYMENT_PURCHASEORDER_TEXT_JS_PURCHASEORDER_NUMBER . '";' . "\n" .
            '    error = 1;' . "\n" .
            '  }' . "\n" .
            '}' . "\n";
        return $validation_string;
    }

    function selection()
    {
        $onFocus = ' onfocus="methodSelect(\'pmt-' . $this->code . '\')"';
        $selection = [
            'id' => $this->code,
            'module' => $this->title,
            'fields' => [
                [
                    'title' => MODULE_PAYMENT_PURCHASEORDER_TEXT_PURCHASEORDER_NUMBER,
                    'field' => zen_draw_input_field('po_number', '', 'id="' . $this->code . '-po-number"' . $onFocus . ' autocomplete="off"'),
                    'tag' => $this->code . '-po-number',
                ],
            ],
        ];
        return $selection;
    }

    function pre_confirmation_check()
    {
        return false;
    }

    function confirmation()
    {
        $confirmation = [
            'title' => '',
            'fields' => [
                [
                    'title' => MODULE_PAYMENT_PURCHASEORDER_TEXT_PURCHASEORDER_NUMBER,
                    'field' => $_POST['po_number'],
                ],
                [
                    'title' => '',
                    'field' => MODULE_PAYMENT_PURCHASEORDER_TEXT_EMAIL_HTML,
                ],
            ],
        ];

        return $confirmation;
    }

    function process_button()
    {
        $process_button_string = zen_draw_hidden_field('po_number', $_POST['po_number']);
        return $process_button_string;
    }

    function before_process()
    {
        global $order;
        $order->info['payment_method'] .= ': ' . $_POST['po_number'];
        $this->email_footer = MODULE_PAYMENT_PURCHASEORDER_TEXT_PURCHASEORDER_NUMBER . " " . $_POST['po_number'] . "\n\n" . MODULE_PAYMENT_PURCHASEORDER_TEXT_EMAIL_FOOTER;
    }

    function after_process()
    {
        global $insert_id;

        // Adding instructions to the Order Status History table, will be visible to the customer in the backend but not generate a new email.
        $comments = MODULE_PAYMENT_PURCHASEORDER_TEXT_EMAIL_FOOTER;
        zen_update_orders_history($insert_id, $comments, "System", -1, 0);

        return false;
    }

    function get_error()
    {
        return false;
    }

    function check()
    {
        global $db;

        if (!isset($this->_check)) {
            $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PURCHASEORDER_STATUS'");
            $this->_check = $check_query->RecordCount();
        }
        return $this->_check;
    }

    function install()
    {
        global $db, $messageStack;
        if (defined('MODULE_PAYMENT_PURCHASEORDER_STATUS')) {
            $messageStack->add_session('PURCHASEORDER module already installed.', 'error');
            zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=purchaseorders', 'NONSSL'));
            return 'failed';
        }
        $new_group_id = zen_create_customer_group("Purchase Orders: Enabled", "This group is authorized to use purchase orders.");

        if (is_numeric($new_group_id)) $messageStack->add_session('Added customer group: <strong>Purchase Orders: Enabled</strong>.', 'success');

        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Purchase Orders Module', 'MODULE_PAYMENT_PURCHASEORDER_STATUS', 'True', 'Do you want to accept Purchase Order payments?', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_PURCHASEORDER_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_PURCHASEORDER_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Make payable to:', 'MODULE_PAYMENT_PURCHASEORDER_PAYTO', '" . STORE_OWNER ."', 'Who should payments be made payable to?', '6', '2', now());");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Allowed Customer Group', 'MODULE_PAYMENT_PURCHASEORDER_ORDER_GROUP_ID', $new_group_id, 'Members of which customer group are allowed to use this method of payment?', '6', '0', 'zen_cfg_select_customer_group(', 'zen_get_customer_group_name', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_PURCHASEORDER_ORDER_STATUS_ID', '1', 'Set the status of orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Email address to receive POs:', 'MODULE_PAYMENT_PURCHASEORDER_PO_EMAIL', '" . STORE_OWNER_EMAIL_ADDRESS ."', 'Where should the Purchase Orders be emailed to?', '6', '2', now());");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Fax number to receive POs:', 'MODULE_PAYMENT_PURCHASEORDER_PO_FAX', '', 'Where should the Purchase Orders be faxed to?', '6', '2', now());");
    }

    function remove()
    {
        global $db;
        $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");

        // Also remove the group that was made and unassign it.
        $group_id = zen_get_customer_group_id_from_name("Purchase Orders: Enabled");
        zen_delete_customer_group($group_id);

    }

    function keys()
    {
        return ['MODULE_PAYMENT_PURCHASEORDER_STATUS', 'MODULE_PAYMENT_PURCHASEORDER_ZONE', 'MODULE_PAYMENT_PURCHASEORDER_ORDER_STATUS_ID', 'MODULE_PAYMENT_PURCHASEORDER_ORDER_GROUP_ID', 'MODULE_PAYMENT_PURCHASEORDER_PAYTO', 'MODULE_PAYMENT_PURCHASEORDER_PO_EMAIL', 'MODULE_PAYMENT_PURCHASEORDER_PO_FAX' , 'MODULE_PAYMENT_PURCHASEORDER_SORT_ORDER'];
    }
}

if(!function_exists('zen_get_customer_group_id_from_name')) {
    function zen_get_customer_group_id_from_name($group_name) {
        global $db;

        $sql = "SELECT group_id FROM " . TABLE_CUSTOMER_GROUPS . " WHERE group_name = :group_name:";
        $sql = $db->bindVars($sql, ':group_name:', $group_name, 'stringIgnoreNull');

        return $db->Execute($sql, 1)->fields['group_id'] ?? -1; // Don't assign 0, that's the "Everyone" default, -1 should NEVER match
    }
}
