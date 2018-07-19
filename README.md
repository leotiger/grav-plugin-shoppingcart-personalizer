# [Grav](http://getgrav.org) Shoppingcart Personlizer

**If you encounter any issues, please don't hesitate
to [report
them](https://github.com/leotiger/grav-plugin-shoppingcart-personalizer/issues).**

> The Personalizer Add-On for [Shoppingcart](https://github.com/flaviocopes/grav-plugin-shoppingcart)

## What does this add-on plugin offer?

This add-on has two purposes, one is to show people how to write add-ons for the shoppingcart plugin and the second one
is to offer some enhancements and fixes for the shoppingcart plugin itself. Apart from this plugin I offer a second add-on
paying a small fee, the Shopping Cart Notify Add-On. The Shopping Cart Notify Add-On offers task automizations and multi-language
support and additional features.

## Introduction

At the time of publishing, GRAV offers two plugins to create and run online shops inside of a GRAV website and the only native
solution that does not require third party services is the former mentioned shoppingcart plugin that provides a good starting 
point for developers but not for site owners and administrators as it comes without many of the most basic features expected, e.g.
stock management, country restriction, single product, service vs. shippable product, etc. The incomplete list of features comprises

* stock configuration
* maximum quantity of a product allowed in cart
* support for unique products (nice for artists, etc.)
* mark product as service product (service products need no shipping)
* editable product id in admin
* option to add remove button in checkout cart
* show add to cart button in catalogue pages
* display decimal separator as comma
* option to equalize image sizes
* define product image
* define catalogue image
* show multiple product images
* activate fancybox support for item images
* background colors for image containers (defaults to transparent)
* refactor javascript to allow optimized js load in the body region
* fix shoppingcart configuration injection problem
* inject checkout form into shoppingcart checkout page (related with configuration injection)

Addtionally I've prepared a fork of the shoppingcart plugin that includes more options for the checkout
form configuration adding support for textarea, re-captcha, etc. A Pull Request for the shoppingcart plugin, v.1.2.2 is
placed but you may update your shoppingcart plugin using the [fork](https://github.com/leotiger/grav-plugin-shoppingcart) as long as the proposals are not integrated into
the shoppingcart plugin.

The Shoppingcart-Notify Add-on allows you to:

* restrict countries
* checks product availablility if checkout is called
* adds additional security checks server-side to prevent cart manipulations in the front-end
* sends confirmation email to customer with customizable template
* offers basic mailchimp integration (will be improved and enhanced)
* automizes stock management with multi-language support
* digital downloads
* etc.

## Further customization

You can customize the look and feel in several ways:

* by adding your own css definitions in your theme, etc.
* by copying and modifying the templates to your theme

If you copy templates to your theme in most cases it's sufficient to copy only the base templates without including templates in the partials folder. If you modify templates you do it at your own risk as you may break things.

## Installation

Download the [ZIP
archive](https://github.com/leotiger/grav-plugin-shoppingcart-personalizer/archive/master.zip)
from GitHub and extract it to the `user/plugins` directory in your Grav
installation or use the install option provided in the tools section of the GRAV admin plugin. If GRAV approves the plugin, it may appear on the GRAV plugin site with automated installation support through cli and/or the Plugins section in the admin interface.

## Credits

Thanks to @flaviocopes for the shoppingcart plugin. 
This plugin includes the [Jquery Fancybox](http://fancyapps.com/fancybox/3/) plugin. You can activate support in the configuration of the plugin.

## Known Issues

Hopefully this plugin will obtain some feedback and suggestions. I will try to enhance the plugin with additional
basic features in the future as I believe that GRAV needs better support for online shops.
