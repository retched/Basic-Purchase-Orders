<?php
/**
 *  developed, copyrighted and brought to you by @proseLA (github)
 *  https://mxworks.cc
 *  copyright 2025 proseLA
 *  consider a donation.  payment modules are the core of any shopping cart.
 *  released under GPU
 *  https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 *  use of this software constitutes acceptance of license
 *  mxworks will vigilantly pursue any violations of this license.
 *  some portions of code may be copyrighted and licensed by www.zen-cart.com
 *  09/2025  project: purchaseorders v3.0.0 file: lang.purchaseorders.php
 */

$define = [
    'MODULE_PAYMENT_PURCHASEORDER_TEXT_TITLE' => 'Purchase Order',
    'MODULE_PAYMENT_PURCHASEORDER_TEXT_DESCRIPTION' => 'Enables your cart to accept to purchase orders. The module will allow the transaction to be processed, while collecting the number of the Purchase Order.',
    'MODULE_PAYMENT_PURCHASEORDER_TEXT_PURCHASEORDER_NUMBER' => 'Purchase Order Number:',
    'MODULE_PAYMENT_PURCHASEORDER_TEXT_JS_PURCHASEORDER_NUMBER' => 'You must enter your Purchase Order number.',
];

if (defined('MODULE_PAYMENT_PURCHASEORDER_PAYTO')) {
    $define['MODULE_PAYMENT_PURCHASEORDER_TEXT_EMAIL_HTML'] = '<br>
    <h4 style="color:red;">PLEASE READ! Important Payment Information:</h3>
    <p><strong>Your order will not be processed until we have received your purchase order.</strong><p>
    Authorize your PO payment to:<br>' . MODULE_PAYMENT_PURCHASEORDER_PAYTO . '<br><br>
    Alternatively, you may send us a <strong>signed copy</strong> of your P.O. by fax or email for faster order processing.
    Be sure to display your <strong>invoice number</strong> prominently somewhere on the document,
    or include a copy of your invoice (e-mailed to you after your order is placed).';

    $extra_info = '';
    if(defined('MODULE_PAYMENT_PURCHASEORDER_PO_FAX') && zen_not_null(MODULE_PAYMENT_PURCHASEORDER_PO_FAX)) {
        $extra_info .= "Fax POs to: " . MODULE_PAYMENT_PURCHASEORDER_PO_FAX;
    }

    if(defined('MODULE_PAYMENT_PURCHASEORDER_PO_EMAIL') && zen_not_null(MODULE_PAYMENT_PURCHASEORDER_PO_EMAIL)) {
        if (zen_not_null($extra_info)) $extra_info .= "\n";
        $extra_info .= "Email POs to: " . MODULE_PAYMENT_PURCHASEORDER_PO_EMAIL;
    }

    $define['MODULE_PAYMENT_PURCHASEORDER_TEXT_EMAIL_FOOTER'] = 'IMPORTANT PAYMENT INFORMATION:' . "\n" .
    'Your order will not be processed until we have received your purchase order.' . "\n\n" .
    'Authorize your PO payment to:' . "\n" . MODULE_PAYMENT_PURCHASEORDER_PAYTO ."\n\n" .
    'Mail your payment to:' . "\n" . STORE_NAME_ADDRESS . "\n\n" .
    'Alternatively, you may send us a *SIGNED* copy of your P.O. by fax or email for immediate order processing. Be sure to display your invoice number prominently somewhere on the document, or include a copy of your invoice.';

    if (zen_not_null($extra_info)) {
        $define['MODULE_PAYMENT_PURCHASEORDER_TEXT_EMAIL_FOOTER'] .= "\n\n" . $extra_info;
    }

}

return $define;
