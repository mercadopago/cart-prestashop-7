# Prestashop - Mercado Pago Module (1.7.x)
---

* [Features](#features)
* [Requirements](#requirements)
* [Installation](#installation)
* [Setup](#setup)
* [Notifications](#notifications)
* [Example features](#pictures_features)
* [Suport](#suport)
* [Social](#social)

<a name="features"></a>
## Features ##
*Available for Argentina, Brazil, Colombia, Mexico, Peru, Uruguay and Venezuela*

**Basic Checkout**

Great for merchants who want to get going quickly and easily.

* Easy website integration— no coding required.
* Limited control of buying experience — display Checkout window as redirect, modal or iframe.
* Store buyer’s card for fast checkout.
* Accept tickets, bank transfer and account money in addition to cards.
* Accept Mercado Pago's discount coupons.

<a name="requirements"></a>
## Requirements ##
Basically, the requirements of this plugin are same as you need to run Prestashop. Your machine should have:

**Platforms**

* <a href="https://www.prestashop.com/en/download">Prestashop 1.7</a> ;

**Web Server Host**

* <a href="http://php.net/">PHP</a> 5.6 or greater with CURL support;
* <a href="http://www.mysql.com/">MySQL</a> version 5.6 or greater OR <a href="https://mariadb.org/">MariaDB</a> version 10.0 or greater;
* <a href="https://httpd.apache.org/">Apache 2.x</a>.

**SSL certificate**

If you're using Custom Checkout, it is a requirement that you have a SSL certificate, and the payment form to be provided under an HTTPS page.
During the sandbox mode tests, you can operate over HTTP, but for homologation you'll need to acquire the certificate in case you don't have it.

<a name="installation"></a>
## Installation ##

1. [Download Prestashop](https://www.prestashop.com/es/versiones-para-programadores#previous-version)
  
1. [Download Plugin](https://github.com/mercadopago/cart-prestashop-7/releases)

<a name="setup"></a>
## Setup ##

1. Copy **mercadopago** folder to **modules** folder.

2. On your store administration, go to **Modules > Modules**.

3. Search by **Mercado Pago** and click install. <br />
You will receive the following message: " Module(s) installed successfully."

  ![Installation](https://raw.github.com/mercadopago/cart-prestashop-7/master/README.img/Config.jpg)<br />

4. Set your **CLIENT_ID** and **CLIENT_SECRET**. 

  Get your credentials in the following address:
  * Argentina: [https://www.mercadopago.com/mla/account/credentials](https://www.mercadopago.com/mla/account/credentials)
  * Brazil: [https://www.mercadopago.com/mlb/account/credentials](https://www.mercadopago.com/mlb/account/credentials)
  * Chile: [https://www.mercadopago.com/mlc/account/credentials](https://www.mercadopago.com/mlc/account/credentials)
  * Colombia: [https://www.mercadopago.com/mco/account/credentials](https://www.mercadopago.com/mco/account/credentials)
  * Mexico: [https://www.mercadopago.com/mlm/account/credentials](https://www.mercadopago.com/mlm/account/credentials)
  * Peru: [https://www.mercadopago.com/mlp/account/credentials](https://www.mercadopago.com/mlp/account/credentials)
  * Venezuela: [https://www.mercadopago.com/mlv/account/credentials](https://www.mercadopago.com/mlv/account/credentials)
  * Uruguay: [https://www.mercadopago.com/mlu/account/credentials](https://www.mercadopago.com/mlu/account/credentials)

5. Enable your payments methods before save your credentials. 
  ![Installation](https://raw.github.com/mercadopago/cart-prestashop-7/master/README.img/Payments.jpg)<br />

***IMPORTANT:*** *This module will only work with the following currencies:*

* Argentina:
  * **ARS** (Argentinian Peso)
* Brazil:
  * **BRL** (Brazilian Real)
* Chile:
  * **CLP** (Chilean Peso)
* Colombia:
  * **COP** (Colombian Peso)
* Mexico:
  * **MXN** (Mexican Peso)
* Peru:
  * **PEN** (Peruvian Sol)
* Uruguay:
  * **UYU** (Peso Uruguayo)
* Venezuela:
  * **VEF** (Venezuelan Bolivar)


<a name="notifications"></a>
## Notifications
Your store will automatically sync with Mercado Pago. The notification URL will be sent in each payment.

<a name="pictures_features"></a>
## Example features

**Standard Checkout**
<br/>
![pictures_features](https://raw.github.com/mercadopago/cart-prestashop-7/master/README.img/Checkout.jpg)

<a name="suport"></a>
## Suport ##

If you have some problem, [click here](https://www.mercadopago.com.br/developers/suporte).


<a name="social"></a>
## Social ##

Follow our facebook group and watch our videos
<ul>
  <li><a href="https://www.youtube.com/playlist?list=PLl8LGzRu2_sXxChIJm1e0xY6dU3Dj_tNi" target="_blank">YOUTUBE</a></li>
</ul>
