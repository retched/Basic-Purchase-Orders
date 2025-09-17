# Basic Purchase Orders

This module provides ZenCart sellers the ability to accept "payment" by way of purchase orders.

The module won't accept the physical purchase order, but rather it will prompt the customer for a purchase order number and allow the customer to check out with the PO attached. It will be up to the ZenCart store operator to decide whether to process the PO immediately or wait for a signed copy before proceeding. This will only mark the order as PENDING until the store operator makes changes otherwise.

## Module Version

- Latest Release: [2.1.0](https://www.zen-cart.com/downloads.php?do=file&id=1356)  
_Released February 1, 2025 for ZenCart 1.5.8._
- Next Version Number: ???

### Version/Release History

- 2.1.0: [Download](https://github.com/retched/zenPurchaseOrders/releases/tag/v2.1.0)  
 Visual changes to the module. Added fields to the configuration, as well as added instructions on confirmation. Uses group functions in addition to discount pricing.
- 2.0.0: [Download](https://www.zen-cart.com/downloads.php?do=file&id=1356)
 This is the last released version of this with edits from carlwhat.
  _I'm not the original module writer; I'm just using this repository to track changes._

## Additional Links

- [retched's Repository](https://github.com/retched/Basic-Purchase-Orders/)
- [ZenCart Plugins Directory Listing](https://www.zen-cart.com/downloads.php?do=file&id=1356) (or use the Releases function on the GitHub repository)

## Setup, Install, and Upgrading

Simply copy the files from the archive into the matching directories. If you have already installed a prior version, overwrite it with the contents of the archive file. It will handle upgrading when complete.

## Uninstallation

1. Log in to your Zen-Cart admin page and under 'Modules', select 'Payment'
2. Remove Purchase Orders
   - This will also delete and unassign, if necessary, the group created during install.
3. Delete the file purchaseorders.php from ./includes/languages/english/modules/payment/purchaseorders.php
4. Delete the file purchaseorders.php from ./includes/modules/payment/purchaseorders.php

## Contributions

Contributions are welcome. Again, I'm not the original author of this module. I just provided a merger between this version of purchase orders and the original one provided on the forum. This module is broadly fully completed with very few tweaks necessary.

## Frequently Asked Questions

This won't answer all the questions you may have, but it may answer some that I thought of.

### What version of ZenCart does this module support?

Only ZenCart versions 1.5.8 and onward. This module requires the customer group and group pricing functions, which were only available in ZenCart 1.5.8 and onward.

|               |  Standard Install  |
|---------------|:------------------:|
| ZenCart 1.5.5 |         :x:        |
| ZenCart 1.5.6 |         :x:        |
| ZenCart 1.5.7 |         :x:        |
| ZenCart 1.5.8 | :white_check_mark: |
| ZenCart 2.0.0 | :white_check_mark: |
| ZenCart 2.0.1 | :white_check_mark: |
| ZenCart 2.1.0 | :white_check_mark: |

- :white_check_mark: = Fully supported
- :x: = Not supported (does not support the customer groups functionality)

### I don't see the option available?

Make sure that you are logged in as a user that is a part of the group selected in the admin area or is a part of a Pricing Group with a name that starts with PO_. If you delete the customer group that comes with the module, make sure to either reselect a group or make a new one. (If you leave the selection as `-- Any --`, ANYONE can use the module to send a purchase order.)

### How does this module actually work?

During checkout as a registered user, if the user is a part of the group authorized to use Purchase Orders, it will appear as a payment option. The cart will prompt for the Purchase Order number (not optional) and will attach that number to the order. It will arrive in the admin area, waiting for confirmation. You can use whatever operating procedure you wish with the treatment of the order. It is suggested that you do not ship the order until you have confirmed payment details with the order.

### After I get the purchase order, what do I do?

That is up to you. This module only tracks orders and the POs assigned to it. You will need to use another module to keep track of payments. Super Orders could be used for that purpose if you wish, but it is quite out of date.

## Known Limitations/Issues

- The reason why the "-- Any --" option works for unassigned customers is that all customers are a part of group `0`, which is what is represented by the `-- Any --` group selection in ZenCart. This is an innate ZenCart function and should not be overwritten. For that reason, if you would like to use Group Pricing instead of Customer Groups, leave the default Customer Group alone and do not delete it. There is no problem with leaving the group field set to an unused group. If you uninstall the module, the group "Purchase Orders: Enabled" will also be deleted.

## Credits

- Mike Lessar
- carlWhat
- retched

## File Listing

These are the file lists that should be included with this module:

``` text
- license.txt
- README.md
- includes\languages\english\modules\payment\lang.purchaseorders.php
- includes\modules\payment\purchaseorders.php
```

## License

``` text
Basic Purchase Orders for ZenCart 1.5.8, 2.0.0, 2.0.1, 2.1.0
A shipping module for ZenCart, an e-commerce platform
Copyright (C) 2025 Paul Williams (retched / retched@hotmail.com)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
```
