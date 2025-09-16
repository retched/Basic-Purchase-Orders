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
     *  01/2025  file: purchaseorders.php
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
        }

        function update_status()
        {
            global $order, $db;
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
                    $this->enabled = false;
                }
            }

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
                'title' => $this->title,
                'fields' => [
                    [
                        'title' => MODULE_PAYMENT_PURCHASEORDER_TEXT_PURCHASEORDER_NUMBER,
                        'field' => $_POST['po_number'],
                    ],
                    [
                        'title' => '',
                        'field' => MODULE_PAYMENT_PURCHASEORDER_TEXT_EMAIL_FOOTER,
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
            $this->email_footer = MODULE_PAYMENT_PURCHASEORDER_TEXT_PURCHASEORDER_NUMBER . " " . $_POST['po_number'] . "\n" . MODULE_PAYMENT_PURCHASEORDER_TEXT_EMAIL_FOOTER;
        }

        function after_process()
        {
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
            $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Purchase Orders Module', 'MODULE_PAYMENT_PURCHASEORDER_STATUS', 'True', 'Do you want to accept Purchase Order payments?', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
            $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_PURCHASEORDER_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
            $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_PURCHASEORDER_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
            $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_PURCHASEORDER_ORDER_STATUS_ID', '1', 'Set the status of orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
        }

        function remove()
        {
            global $db;
            $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
        }

        function keys()
        {
            return ['MODULE_PAYMENT_PURCHASEORDER_STATUS', 'MODULE_PAYMENT_PURCHASEORDER_ZONE', 'MODULE_PAYMENT_PURCHASEORDER_ORDER_STATUS_ID', 'MODULE_PAYMENT_PURCHASEORDER_SORT_ORDER'];
        }
    }
