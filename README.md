<p align="center">
  <img src="https://www.pay.nl/uploads/1/brands/main_logo.png" />
</p>
<h1 align="center">PAY. CS-Cart plugin</h1>

# Description

With the PAY. plugin you can easily add different payment methods to your CS-Cart webshop. Go to https://www.pay.nl (Dutch) for an overview of all our features and services, or visit https://docs.pay.nl/plugins#cs-cart for more information.

- [Description](#description)
- [Available payment methods](#available-payment-methods)
- [Installation](#installation)
- [Usage](#usage)
- [Support](#support)

# Available payment methods

Bank Payments  |   Creditcards    | Gift cards & Vouchers | Pay by invoice | Others | 
:-----------: |:----------------:| :-----------: |:--------------:| :-----------: |
iDEAL + QR |       Visa       | VVV Cadeaukaart |    Riverty     | PayPal |
Bancontact + QR |    Mastercard    | Webshop Giftcard |    Billink     | WeChatPay | 
Giropay | American Express | FashionCheque |         Klarna       | AmazonPay |
MyBank |  Carte Bancaire  | Podium Cadeaukaart |      In3       | Przelewy24 | 
SOFORT |     PostePay     | Gezondheidsbon |      SprayPay          | Pay Fixed Price (phone) |
Maestro |     Dankort      | Fashion Giftcard |     Creditclick     | Instore Payments (POS) |
Bank Transfer |       Nexi       | GivaCard |        |  | 
|  |                  | YourGift |     | | 
| |                  | Paysafecard |

# Requirements

    Minimum PHP Version: PHP 8.1
    Tested up to CSCart 4.17.1


# Installation
#### Installing

You can install the plugin from the CS-Cart marketplace, just search for Pay.nl and install it the plugin that way.<br/>
Otherwise you can do it manually by downloading the latest .zip release and upload it to *Add-ons* > *Manage add-ons* and click on the *+* button on the top right of the page. <br/>
Choose for a local installation of the plugin and upload the .zip here. <br/>
There is also an extended installation <a href="https://github.com/paynl/cscart-plugin/blob/master/Installatie%20handleiding%20CS-Cart.pdf">manual</a> (Dutch).

##### Setup

1. Log into the CS-Cart admin
2. Go to *Add-ons* > *Manage add-ons*
3. Scroll down or search for Pay.nl
4. Activate the plugin
5. Click on the blue pay.nl link
6. Enter the API token and serviceID (these can be found in the <a href="https://admin.pay.nl/programs/programs">PAY. Admin Panel</a> )
7. Save the settings
8. Go to *Administration* > *Payment methods*
8. On the top right of the page click on the *+* button
9. By "Processor" choose for Pay.nl
10. By "Payment category" choose for "Internet Payments"
11. Click on "Configure" on the top left of the pop-up
12. By "Option" choose the payment method you want to make available
13. Click on "General" top left of the pop-up to return and configure the rest of the payment method.
14. Save the settings
15. Repeat this for every payment method you want to use

Go to the *Manage* > *Services* tab in the Pay.nl Admin Panel to enable extra payment methods

# Usage

More information on this plugin can be found on https://docs.pay.nl/plugins#cs-cart

# Support
https://www.pay.nl

Contact us: support@pay.nl

