(function(ShoppingCart) {
        
    jQuery( ".js__shoppingcart__remove-from-cart" ).off();    
    jQuery( ".js__shoppingcart__quantity-box-cart" ).off();    
    
    jQuery(document).off('keydown', '.js__shoppingcart__quantity-box-cart');
    jQuery(document).off('keydown', '#js__shoppingcart__quantity');
    /***********************************************************/
    /* Handle removing a product from the cart
    /* #event
    /***********************************************************/
    jQuery(document).on('click tap', '.js__shoppingcart__remove-from-cart', function() {
        var element_id = jQuery(this).data('id');
        ShoppingCart.removeProduct(element_id);
    });
    
    /***********************************************************/
    /* Handle variation config changes
    /* #event
    /***********************************************************/
    jQuery(document).on('keyup change', '.js__shoppingcart__variationconfig', function() {
        ShoppingCart.calcVariations(ShoppingCart.currentProduct);
    });

    jQuery(document).on('click', '.js__display_product_variations', function() {
       $variantblock = jQuery(this).data('variantblock');
       if (jQuery('.' + $variantblock).length) {
           jQuery('.' + $variantblock).toggle();
       }
    });
    /***********************************************************/
    /* Update variation interface and handle variation config
    /* #event
    /***********************************************************/
    jQuery(document).on('change', '.group-variations-sel', function() {
        
        var $varsel = jQuery(this);        
        var $asinput = $varsel.data('groupasinput');
        var $selected = $varsel.find('option:selected');
        var $groupid = $varsel.data('groupid');
        var $varprice = 0, $varid = 0, $varmin = 0, $varmax = 0, $variation = null; 
        var $interface = '';
        
        if ($varsel.val()) {
            $varprice = $selected.data('varprice'); 
            $varid = $selected.data('varid');
            $varmin = $selected.data('handlemin');
            $varmax = $selected.data('handlemax');
            $variation = $selected.data('variation');            
            if ($asinput) {
                
                if ($varmin < $varmax) {
                    $interface += '<br>';
                    $interface += '<div class="variation-description">' + $variation['description'] + '</div>';
                    $interface += '<input type="number" data-groupid="' + $groupid + '" data-varid="' + $varid + '" data-varprice="' + $varprice + '" class="js__shoppingcart__variationconfig" min="' + $varmin + '" max="' + $varmax + '" value="' + $varmin + '">';
                    $interface += '<br>';
                    
                } else {
                    $interface += '<input type="hidden" data-groupid="' + $groupid + '" data-varid="' + $varid + '" data-varprice="' + $varprice + '" class="js__shoppingcart__variationconfig" value="' + $varmin + '" />';
                }
            }
            if ($variation['freetext']) {
                $interface += '<br><div class="variation-freetext">';
                $interface += '<input type="text" placeholder="' + window.PLUGIN_SHOPPINGCART.translations.PERSONALIZE_VARIATION_REMARK.replace('"',"'") + '" id="freetext-' + $groupid + '-' + $varid + '" class="js__shoppingcart__variationfreetext" value="" />';
                $interface += '</div>';
            }
            if ($variation['fileupload']) {                    
                $interface += '<br>';
                $interface += '<div class="variation-upload-hint">';
                $interface += window.PLUGIN_SHOPPINGCART.translations.PERSONALIZE_VARIATION_UPLOAD_HINT;
                $interface += '</div>';
            }
            jQuery('#groupmedia-' + $groupid).find('.shoppingcart-thumb').hide();
            if (jQuery('#varmedia-' + $groupid + '-' + $varid).length) {
                jQuery('#varmedia-' + $groupid + '-' + $varid).show();
            } else if (jQuery('#varmedia-' + $groupid).length) {
                jQuery('#varmedia-' + $groupid).show();
            }
            
        }
        jQuery('#grouphandler-' + $groupid).html($interface);
        ShoppingCart.calcVariations(ShoppingCart.currentProduct);
        
    });
    
    
    
    /***********************************************************/
    /* Handle change the quantity box in the cart
    /* #event
    /***********************************************************/
    jQuery(document).on('keyup change', '.js__shoppingcart__quantity-box-cart', function() {
        var element_id = jQuery(this).data('id');
        var new_quantity = jQuery(this).val();
        var isInt = function isInt(n) {
            return n % 1 == 0;
        };

        if (!isInt(new_quantity)) {
            alert(window.PLUGIN_SHOPPINGCART.translations.VALUE_NOT_ACCEPTABLE);
            jQuery(this).val(ShoppingCart.items[element_id].quantity);
            return;
        }

        if (ShoppingCart.settings.cart.maximum_total_quantity_value && (parseInt(new_quantity) > ShoppingCart.settings.cart.maximum_total_quantity_value)) {
            alert(window.PLUGIN_SHOPPINGCART.translations.QUANTITY_EXCEEDS_MAX_ALLOWED_VALUE + ': ' + ShoppingCart.settings.cart.maximum_total_quantity_value);
            jQuery(this).val(ShoppingCart.items[element_id].quantity);
            return;
        }

        ShoppingCart.items[element_id].quantity = new_quantity;
        ShoppingCart._saveCartToLocalstorage();
        ShoppingCart.renderCart();

        jQuery(".js__shoppingcart__quantity-box-cart[data-id='" + element_id + "']").focus().val(jQuery(".js__shoppingcart__quantity-box-cart[data-id='" + element_id + "']").val()); //this is to avoid browser auto-selecting text
    });
    
    /***********************************************************/
    /* Calc variations
    /***********************************************************/
    ShoppingCart.resetVariations = function resetVariations() {
        jQuery('.group-variations-sel').each(function() {            
            var $varsel = jQuery(this);        
            var $asinput = $varsel.data('groupasinput');
            var $selected = $varsel.find('option:selected');
            var $groupid = $varsel.data('groupid');
            var $varprice = 0, $varid = 0, $varmin = 0, $varmax = 0, $variation = null; 
            var $interface = '';

            if ($varsel.val()) {
                $varprice = $selected.data('varprice'); 
                $varid = $selected.data('varid');
                $varmin = $selected.data('handlemin');
                $varmax = $selected.data('handlemax');
                $variation = $selected.data('variation');            
                if ($asinput) {

                    if ($varmin < $varmax) {
                        $interface += '<br>';
                        $interface += '<div class="variation-description">' + $variation['description'] + '</div>';
                        $interface += '<input type="number" data-groupid="' + $groupid + '" data-varid="' + $varid + '" data-varprice="' + $varprice + '" class="js__shoppingcart__variationconfig input-lg" min="' + $varmin + '" max="' + $varmax + '" value="' + $varmin + '">';
                        $interface += '<br>';

                    } else {
                        $interface += '<input type="hidden" data-groupid="' + $groupid + '" data-varid="' + $varid + '" data-varprice="' + $varprice + '" class="js__shoppingcart__variationconfig" value="' + $varmin + '" />';
                    }

                }       
                if ($variation['freetext']) {
                    $interface += '<br><div class="variation-freetext">';
                    $interface += '<input type="text" placeholder="' + window.PLUGIN_SHOPPINGCART.translations.PERSONALIZE_VARIATION_REMARK.replace('"',"'") + '" id="freetext-' + $groupid + '-' + $varid + '" class="js__shoppingcart__variationfreetext input-lg" value="" />';
                    $interface += '</div>';
                }
                if ($variation['fileupload']) {                    
                    $interface += '<br>';
                    $interface += '<div class="variation-upload-hint">';
                    $interface += window.PLUGIN_SHOPPINGCART.translations.PERSONALIZE_VARIATION_UPLOAD_HINT;
                    $interface += '</div>';
                }
                jQuery('#groupmedia-' + $groupid).find('.shoppingcart-thumb').hide();
                if (jQuery('#varmedia-' + $groupid + '-' + $varid).length) {
                    jQuery('#varmedia-' + $groupid + '-' + $varid).show();
                } else if (jQuery('#varmedia-' + $groupid).length) {
                    jQuery('#varmedia-' + $groupid).show();
                }
            }
            jQuery('#grouphandler-' + $groupid).html($interface);
            ShoppingCart.calcVariations(ShoppingCart.currentProduct);
            
        });
    }
    
    /***********************************************************/
    /* Calc variations
    /***********************************************************/
    ShoppingCart.calcVariations = function calcVariations(product) {
        if (jQuery('.shoppingcart-variation-handler').length) {            
            //var quantity = jQuery('.shoppingcart-variation-handler').closest('.shoppingcart-product-container').find('#js__shoppingcart__quantity').val() || 1;
            var newPrice = parseFloat(ShoppingCart.currentProduct.baseprice);
            var variants = [];
            jQuery('.js__shoppingcart__variationconfig').each(function() {
                newPrice = newPrice + (parseFloat(jQuery(this).data('varprice')) * parseInt(jQuery(this).val()));
                
                var $groupid = jQuery(this).data('groupid');
                var $varid = jQuery(this).data('varid');
                var $variation = jQuery('#varopt-' + $groupid + '-' + $varid).data('variation');
                variants.push({
                    groupid: $groupid,
                    varid: $varid,
                    vardata: $variation,
                    varprice: jQuery(this).data('varprice'),
                    varmultiplier: jQuery(this).val(),
                    varbaseprice: ShoppingCart.currentProduct.baseprice,
                    varstepprice: newPrice,
                    varfreetext: (jQuery('#freetext-' + jQuery(this).data('groupid') + '-' + jQuery(this).data('varid')).length ? jQuery('#freetext-' + jQuery(this).data('groupid') + '-' + jQuery(this).data('varid')).val() : ''),
                });
            });
            jQuery('.group-variations-sel').each(function() {
                var $varsel = jQuery(this);        
                var $asinput = $varsel.data('groupasinput');
                if (!$asinput && $varsel.val()) {
                    var $selected = $varsel.find('option:selected');
                    var $groupid = $varsel.data('groupid');
                    var $varprice = $selected.data('varprice'); 
                    var $varid = $selected.data('varid');
                    var $varmin = $selected.data('handlemin');
                    var $variation = $selected.data('variation');
                    newPrice = newPrice + (parseFloat($varprice) * parseInt($varmin));
                    variants.push({
                        groupid: $groupid,
                        varid: $varid,
                        vardata: $variation,
                        varprice: $varprice,
                        varmultiplier: $varmin,
                        varbaseprice: ShoppingCart.currentProduct.baseprice,
                        varstepprice: newPrice,
                        varfreetext: (jQuery('#freetext-' + $groupid + '-' + $varid).length ? jQuery('#freetext-' + $groupid + '-' + $varid).val() : ''),
                    });
                }
            });
            
            ShoppingCart.currentProduct.price = parseFloat(newPrice).toFixed(2);
            ShoppingCart.currentProduct.formatted_price = ShoppingCart.formattedPrice(newPrice);   
            ShoppingCart.currentProduct.variants = variants;
            jQuery('.shoppingcart-variation-handler .shoppingcart-price').html(ShoppingCart.renderPriceWithCurrency(newPrice));  
            return ShoppingCart.currentProduct;
        }
        return product;
    }
    
    /***********************************************************/
    /* Add a product to the cart
    /***********************************************************/
    ShoppingCart.addProduct = function addProduct(product, quantity) {
        var onBeforeAddProductToCart;
        jQuery(document).trigger(onBeforeAddProductToCart = jQuery.Event('onBeforeAddProductToCart', { product: product, quantity: quantity }));
        if (onBeforeAddProductToCart.result === false) {
            return false;
        }
        
        // calc variations
        product = ShoppingCart.calcVariations(product);
        
        var existingProducts = jQuery(ShoppingCart.items).filter(function(index, item) { if (product.title == item.product.title) return true; }).toArray();

        var existingProduct = existingProducts[0];

        if (!existingProduct) {
            ShoppingCart.items.push({product: product, quantity: quantity});
        } else {
            existingProduct.quantity = parseInt(existingProduct.quantity) + parseInt(quantity);
        }

        var onAfterAddProductToCart;
        jQuery(document).trigger(onAfterAddProductToCart = jQuery.Event('onAfterAddProductToCart', { product: product, quantity: quantity } ));        

        jQuery(ShoppingCart).trigger('onAfterAddProductToCart', product);

        ShoppingCart._saveCartToLocalstorage();
        ShoppingCart.renderCart();
    };

    /***********************************************************/
    /* Remove a product from the cart
    /***********************************************************/
    ShoppingCart.removeProduct = function removeProduct(element_id) {
        var onBeforeRemoveProductFromCart;
        var existingProducts = jQuery(ShoppingCart.items).filter(function(index, item) { if (index === parseInt(element_id)) return true; }).toArray();

        var existingProduct = existingProducts.length ? existingProducts[0] : false;
        jQuery(document).trigger(onBeforeRemoveProductFromCart = jQuery.Event('onBeforeRemoveProductFromCart', { product: existingProduct }));
        if (onBeforeRemoveProductFromCart.result === false) {
            return;
        }
        ShoppingCart.items.splice(element_id, 1);
        var onAfterRemoveProductFromCart;
        jQuery(document).trigger(onAfterRemoveProductFromCart = jQuery.Event('onAfterRemoveProductFromCart', { product: existingProduct } ));        
        ShoppingCart.renderCart();
        //ShoppingCart.calculateItemsLeft();
        ShoppingCart._saveCartToLocalstorage();        
    };    

    
    /***********************************************************/
    /* Go to shop
    /* #event
    /***********************************************************/
    jQuery(document).on('click tap', '.js__shoppingcart__goto-shop', function(event) {
        ShoppingCart.returnToShop();        
    });
    
    
    /***********************************************************/
    /* Return to shop
    /***********************************************************/
    ShoppingCart.returnToShop = function returnToShop() {
        window.location.href = PLUGIN_SHOPPINGCART.settings.baseURL + PLUGIN_SHOPPINGCART.settings.urls.shop_url;
    };
    
    /***********************************************************/
    /* Calculate the shipping price
    /***********************************************************/
    ShoppingCart.generateShippingPrice = function generateShippingPrice() {
        var onBeforeGenerateShippingPrice;
        jQuery(document).trigger(onBeforeGenerateShippingPrice = jQuery.Event('onBeforeGenerateShippingPrice'));
        if (onBeforeGenerateShippingPrice.result === false) {
            return;
        }
        
        var countMethods = 0;
        for (index in ShoppingCart.settings.shipping.methods) {
            countMethods++;
        }

        if (!ShoppingCart.shippingPrice) {
            ShoppingCart.shippingPrice = 0.00;
        }

        if (countMethods === 0) {
            
            ShoppingCart.renderCart();
        } else if (countMethods === 1) {
            var method;
            for (index in ShoppingCart.settings.shipping.methods) {
                method = ShoppingCart.settings.shipping.methods[index];
            }
            
            ShoppingCart.shippingPrice = parseFloat(method.price).toFixed(2);
            ShoppingCart.renderCart();
        } else {
            var interval = setInterval(function() {
                var shippingMethodName = jQuery('.js__shipping__method').val();
                if (shippingMethodName) {
                    clearInterval(interval);

                    var method;
                    for (index in ShoppingCart.settings.shipping.methods) {
                        if (shippingMethodName == ShoppingCart.settings.shipping.methods[index].name) {
                            method = ShoppingCart.settings.shipping.methods[index];
                        }
                    }

                    var price = method.price;
                    if (isNaN(price)) {
                        price = 0;
                    }

                    price = parseFloat(price).toFixed(2);                   
                    ShoppingCart.shippingPrice = price;
                    ShoppingCart.renderCart();
                }

            }, 50);
        }
    };    
    
    /***********************************************************/
    /* Render the cart
    /***********************************************************/
    ShoppingCart.renderCart = function renderCart() {
        var $cart = jQuery('.js__shoppingcart-cart');
        var $cartTitle = jQuery('.js__shoppingcart-cart__title');

        var thead = $cart.find('thead');
        var tbody = $cart.find('tbody');

        thead.html('');
        tbody.html('');

        if (ShoppingCart.items.length === 0) {
            $cart.removeClass('has-products');
            $cartTitle.hide();
            return;
        } else {
            $cart.addClass('has-products');
            if (ShoppingCart.currentPageIsProduct) $cartTitle.text(window.PLUGIN_SHOPPINGCART.translations.SHOPPING_CART);
            if (ShoppingCart.currentPageIsProducts) $cartTitle.text(window.PLUGIN_SHOPPINGCART.translations.SHOPPING_CART);
            if (ShoppingCart.currentPageIsCheckout) $cartTitle.text(window.PLUGIN_SHOPPINGCART.translations.YOU_ARE_PURCHASING_THESE_ITEMS);
            if (ShoppingCart.currentPageIsOrder) $cartTitle.text(window.PLUGIN_SHOPPINGCART.translations.ITEMS_PURCHASED);
            if (ShoppingCart.currentPageIsOrderCancelled) $cartTitle.text(window.PLUGIN_SHOPPINGCART.translations.SHOPPING_CART);
            if (ShoppingCart.currentPageIsCart) $cartTitle.text(window.PLUGIN_SHOPPINGCART.translations.SHOPPING_CART);
            $cartTitle.show();
        }

        var row = '<tr>';
        row += '<th class="cart-product">' + window.PLUGIN_SHOPPINGCART.translations.ITEM + '</th>';
        if (!ShoppingCart.isMobile()) {
            row += '<th class="cart-product-price">' + window.PLUGIN_SHOPPINGCART.translations.PRICE + '</th>';
        }

        if (!ShoppingCart.isMobile()) {
            row += '<th class="cart-product-quantity">' + window.PLUGIN_SHOPPINGCART.translations.QUANTITY + '</th>';
        } else {
            row += '<th class="cart-product-quantity">' + window.PLUGIN_SHOPPINGCART.translations.QUANTITY_SHORT + '</th>';
        }

        row += '<th class="cart-product-total">' + window.PLUGIN_SHOPPINGCART.translations.TOTAL + '</th>';

        if (ShoppingCart.currentPageIsProductOrProductsOrCartOrExternal() || ShoppingCart.settings.cart.display_remove_option) {
            row += '<th class="cart-product-remove-button">';
            row += window.PLUGIN_SHOPPINGCART.translations.REMOVE;
            row += '</th>';
        }

        row += '</tr>';
        thead.html(row);
        var rows_html = '';

        for (var i = 0; i < ShoppingCart.items.length; i++) {
            var item = ShoppingCart.items[i];
            var row = '<tr><td class="cart-product">';

            if (ShoppingCart.settings.cart.add_product_thumbnail) {
                if (item.product.image) {
                    if (typeof ShoppingCart.settings.ui.image_container_square != 'undefined' && ShoppingCart.settings.ui.image_container_square) {
                        row += '<div class="shoppingcart-thumb" style="text-align:center;width:' + item.product.size_cart + 'px;height:' + item.product.size_cart + 'px;background-color:' + item.product.bgcolor + ';">';
                        row += '<img src="' + item.product.image + '" class="cart-product-image"> ';
                        row += '</div> ';
                    } else {
                        row += '<div class="shoppingcart-thumb">';
                        row += '<img src="' + item.product.image + '" class="cart-product-image"> ';
                        row += '</div> ';                        
                    }
                }
            }

            if (item.product.url) {
                row += '<div class="cart-product-card"><a href="' + item.product.url + '" class="cart-product-name">' + item.product.title + '</a>';
            } else {
                row += '<div class="cart-product-card"><span class="cart-product-name">' + item.product.title + '</span>';
            }
            if (typeof item.product.variants !== 'undefined' && item.product.variants.length) {                
                row += '<div class="cart-product-config"><button class="js__display_product_variations" data-variantblock="item-variants-' + item.product.id + '">' + window.PLUGIN_SHOPPINGCART.translations.PERSONALIZE_CART_VARIATIONS_HEADLINE + '</button></div>';
            }

            row += '</div></td>';

            if (!ShoppingCart.isMobile()) {
                /***********************************************************/
                /* Price
                /***********************************************************/
                row += '<td class="cart-product-price">';
                row += ShoppingCart.renderPriceWithCurrency(item.product.price);
                row += '</td>';
            }

            /***********************************************************/
            /* Quantity
            /***********************************************************/
            row += '<td class="cart-product-quantity">';
            if (ShoppingCart.settings.cart.allow_editing_quantity_from_cart && !ShoppingCart.isMobile()) {
                var istock = item.product.stock;
                if (item.product.cartmax && parseInt(item.product.cartmax) > 0 && parseInt(item.product.cartmax) <= parseInt(item.product.stock)) {
                    istock = item.product.cartmax;
                }                    

                if (ShoppingCart.currentPageIsProductOrProductsOrCartOrExternal() && istock > 1 && (typeof item.product.variants === 'undefined' || item.product.variants.length == 0)) {
                    row += '<input type="number" max="' + istock + '" min="1" value="' + item.quantity + '" class="input-sl js__shoppingcart__quantity-box-cart" data-id="' + i + '" />';
                } else {
                    row += item.quantity;
                }
            } else {
                row += item.quantity;
            }
            row += '</td>';

            /***********************************************************/
            /* Total
            /***********************************************************/
            row += '<td class="cart-product-total">';            
            row += ShoppingCart.renderPriceWithCurrency(ShoppingCart.cartSubtotalPrice(item));
            row += '</td>';

            if (ShoppingCart.currentPageIsProductOrProductsOrCartOrExternal() || ShoppingCart.settings.cart.display_remove_option) {
                row += '<td class="cart-product-remove-button">';
                row += '<a class="btn btn-small js__shoppingcart__remove-from-cart" data-id="' + i + '">' + window.PLUGIN_SHOPPINGCART.translations.REMOVE + '</a>';
                row += '</td>';
            }

            row += '</tr>';

            if (typeof item.product.variants !== 'undefined' && item.product.variants.length) {                
                row += '<tr class="item-variants-' + item.product.id + '" style="display:none;">';
                row += '<td class="cart-product">';
                row += '<div class="shoppingcart-thumb">';
                row += '&nbsp;';
                row += '</div> ';                        
                row += '<div class="cart-product-card">';
                row += '<span class="cart-product-name">' + window.PLUGIN_SHOPPINGCART.translations.PERSONALIZE_CART_VARIATIONS_BASEPRICE + '</span>';
                row += '</div>';
                row += '</td>';
                row += '<td class="cart-product-price">';
                row += ShoppingCart.renderPriceWithCurrency(item.product.variants[0].varbaseprice);
                row += '</td>';
                row += '<td class="cart-product-quantity">';
                row += item.quantity;
                row += '</td>';
                row += '<td class="cart-product-total">';            
                row += ShoppingCart.renderPriceWithCurrency(parseFloat(item.product.variants[0].varbaseprice) * parseInt(item.quantity));
                row += '</td>';
                row += '</tr>';
                
                
                for (var v = 0; v < item.product.variants.length; v++) {
                    var variant = item.product.variants[v];
                    row += '<tr class="item-variants-' + item.product.id + '" style="display:none;">';
                    row += '<td class="cart-product">';
                    row += '<div class="shoppingcart-thumb">';
                    row += '&nbsp;';
                    row += '</div> ';                        
                    row += '<div class="cart-product-card">';
                    row += '<span class="cart-product-name">' + variant.vardata.title + '</span>';
                    row += '</div>';
                    row += '</td>';
                    row += '<td class="cart-product-price">';
                    row += ShoppingCart.renderPriceWithCurrency(variant.varprice);
                    row += '</td>';
                    row += '<td class="cart-product-quantity">';
                    row += variant.varmultiplier + ' x ' + item.quantity;
                    row += '</td>';
                    row += '<td class="cart-product-total">';            
                    row += ShoppingCart.renderPriceWithCurrency(parseFloat(variant.varprice) * parseInt(variant.varmultiplier) * parseInt(item.quantity));
                    row += '</td>';
                    row += '</tr>';
                }
            }
            rows_html += row;
        }

        /***********************************************************/
        /* Additional lines after products
        /***********************************************************/

        row = '<tr>';

        if (ShoppingCart.currentPageIsProduct) {
            row += '<td class="goback"><a href="#" class="btn btn-success js__shoppingcart__continue-shopping">' + window.PLUGIN_SHOPPINGCART.translations.CONTINUE_SHOPPING + '</a></td>';
        } else {
            row += '<td class="class="cart-calc-labels""><strong>' + window.PLUGIN_SHOPPINGCART.translations.SUBTOTAL + '</strong></td>';
        }

        row += '<td class="empty"></td>';

        if (!ShoppingCart.isMobile()) {
            row += '<td class="empty"></td>';
        }

        row += '<td class="cart-product-total">';
        if (ShoppingCart.currentPageIsCheckout) {        
            if (ShoppingCart.productPriceDoesNotIncludeTaxes()) {
                row += ShoppingCart.renderPriceWithCurrency(ShoppingCart.cartTotalPrice());                
            } else {
                var taxamount = ShoppingCart.taxesApplied;                
                row += ShoppingCart.renderPriceWithCurrency(ShoppingCart.cartTotalPrice() - taxamount);
            }
        } else {
            row += ShoppingCart.renderPriceWithCurrency(ShoppingCart.cartTotalPrice());
        }
        
        row += '</td>';

        /***********************************************************/
        /* Checkout / or not yet reached minimum order level
        /***********************************************************/
        var atLeastAProductIsAdded = false;

        ShoppingCart.items.forEach(function(item) {
            if (item.quantity != "0" && item.quantity != "") {
                atLeastAProductIsAdded = true;
            }
        });

        if (atLeastAProductIsAdded) {
            if (ShoppingCart.orderAmountIsGreaterThenMinimum()) {
                if (ShoppingCart.currentPageIsProductOrProductsOrCartOrExternal() || ShoppingCart.currentPageIsOrderCancelled) {
                    row += '<td class="cart-proceed-to-checkout"><button class="btn btn-success js__shoppingcart__proceed-to-checkout">' + window.PLUGIN_SHOPPINGCART.translations.CHECKOUT + '</button></td>';
                }
            } else {
                row += '<td class="cart-needs-minimum">';
                row += window.PLUGIN_SHOPPINGCART.translations.MINIMUM_TO_PLACE_AN_ORDER;
                row += ' ' + ShoppingCart.renderPriceWithCurrency(ShoppingCart.settings.cart.minimumSumToPlaceOrder);
                row += '</td>';
            }
        }

        if (ShoppingCart.currentPageIsCheckout) {

            /***********************************************************/
            /* Product price do not include taxes, show them here
            /***********************************************************/
            if (ShoppingCart.productPriceDoesNotIncludeTaxes()) {

                row += '<tr class="cart-taxes-calculated">';

                if (ShoppingCart.checkout_form_data.country) {
                    //row += '<td><strong>' + window.PLUGIN_SHOPPINGCART.translations.INCLUDING_TAXES + '</strong></td>';

                    row += '<td class="cart-calc-labels"><strong>';
                    if (ShoppingCart.settings.cart.add_shipping_and_taxes_cost_to_total) {
                        row += window.PLUGIN_SHOPPINGCART.translations.INCLUDING_TAXES;
                    } else {
                        row += window.PLUGIN_SHOPPINGCART.translations.TAXES;
                    }
                    row += '</strong></td>';

                    row += '<td></td>';
                    row += '<td></td>';
                    row += '<td>';
                    var amount = ShoppingCart.taxesApplied;
                    if (ShoppingCart.settings.cart.add_shipping_and_taxes_cost_to_total) {
                        amount = ShoppingCart.calculateTotalPriceIncludingTaxes();
                    }
                    row += ShoppingCart.renderPriceWithCurrency(amount)
                    row += '</td>';

                } else {
                    row += '<td class="cart-calc-labels">' + window.PLUGIN_SHOPPINGCART.translations.PRICE_DO_NOT_INCLUDE_TAXES + '</td>';
                    row += '<td></td>';
                    row += '<td></td>';
                    row += '<td></td>';
                }

                row += '</tr>';
            } else {
                var amount = ShoppingCart.taxesApplied;
                row += '<tr class="cart-taxes-calculated">';
                row += '<td class="cart-calc-labels"><strong>';
                if (ShoppingCart.settings.cart.add_shipping_and_taxes_cost_to_total) {
                    row += window.PLUGIN_SHOPPINGCART.translations.INCLUDING_TAXES;
                    row += '</strong></td>';
                    row += '<td></td>';
                    row += '<td></td>';
                    row += '<td>';
                    row += ShoppingCart.renderPriceWithCurrency(amount);
                    row += '</td>';
                    row += '</tr>';
                    
                } else {
                    row += window.PLUGIN_SHOPPINGCART.translations.TAXES;
                    row += '</strong></td>';
                    row += '<td></td>';
                    row += '<td></td>';
                    row += '<td>';
                    row += ShoppingCart.renderPriceWithCurrency(amount);
                    row += '</td>';
                    row += '</tr>';
                    
                }
            }

            /***********************************************************/
            /* Shipping price
            /***********************************************************/
            if (ShoppingCart.shippingPrice) {
                row += '<tr class="cart-shipping-calculated">';
                row += '<td class="cart-calc-labels"><strong>';

                if (ShoppingCart.settings.cart.add_shipping_and_taxes_cost_to_total) {
                    row += window.PLUGIN_SHOPPINGCART.translations.INCLUDING_SHIPPING;
                } else {
                    row += window.PLUGIN_SHOPPINGCART.translations.SHIPPING;
                }

                row += '</strong></td>';
                row += '<td></td>';
                row += '<td></td>';
                row += '<td>';
                var amount = ShoppingCart.shippingPrice;
                if (ShoppingCart.settings.cart.add_shipping_and_taxes_cost_to_total) {
                    amount = ShoppingCart.calculateTotalPriceIncludingTaxesAndShipping();
                }
                row += ShoppingCart.renderPriceWithCurrency(amount);
                row += '</td>';
                row += '</tr>';
            } else {
                row += '<tr class="cart-shipping-calculated">';
                row += '<td class="cart-calc-labels"><strong>';

                row += window.PLUGIN_SHOPPINGCART.translations.NOTIFY_FREE_SHIPPING;

                row += '</strong></td>';
                row += '<td></td>';
                row += '<td></td>';
                row += '<td>';
                var amount = 0.00;//ShoppingCart.shippingPrice;
                /*
                if (ShoppingCart.settings.cart.add_shipping_and_taxes_cost_to_total) {
                    amount = ShoppingCart.calculateTotalPriceIncludingTaxesAndShipping();
                }
                */
                row += ShoppingCart.renderPriceWithCurrency(amount);
                row += '</td>';
                row += '</tr>';                
            }

            /***********************************************************/
            /* Calculate total including taxes and shipping
            /***********************************************************/
            var totalPriceIncludingTaxesAndShipping = ShoppingCart.calculateTotalPriceIncludingTaxesAndShipping();

            if (totalPriceIncludingTaxesAndShipping) {
                row += '<tr class="total-line">';
                row += '<td class="cart-calc-labels"><strong>' + window.PLUGIN_SHOPPINGCART.translations.TOTAL + '</strong></td>';
                row += '<td></td>';
                row += '<td></td>';
                row += '<td>';
                row += ShoppingCart.renderPriceWithCurrency(totalPriceIncludingTaxesAndShipping);
                row += '</td>';
                row += '</tr>';
            }
        }

        rows_html += row;

        tbody.html(tbody.html() + rows_html);
    }    
    
    /***********************************************************/
    /* Generate the selected shipping price
    /* #event
     * @todo add update for existing products 
     */
    /***********************************************************/
    jQuery(document).on('onBeforeAddProductToCart', function(event) {                
        var existingProducts = jQuery(ShoppingCart.items).filter(function(index, item) { if (item.product.id == event.product.id) {
                item.cartindex = index;
                return true;
            }
        }).toArray();
        var existingProduct = existingProducts[0];        
        if (!existingProduct) {
            
            return true;
        } else {
            
            if (typeof event.product.stock === 'undefined' || event.product.stock === null || event.product.stock === '' || parseInt(existingProduct.quantity) >= parseInt(event.product.stock)) {
                return false;
            }
            if (event.product.removeonadd) {
                ShoppingCart.removeProduct(existingProduct.cartindex);
            }
        }
        return true;
    });

    jQuery(document).on('onAfterRemoveProductFromCart', function(event) {                
        if (ShoppingCart.currentPageIsCheckout && ShoppingCart.items.length === 0) {            
            var $checkoutform = jQuery('.js__checkout__block');
            $checkoutform.hide();
            ShoppingCart.returnToShop();
        }
    });
    
    /***********************************************************/
    /* Load quote request into modal
    /* #event
    /***********************************************************/
    jQuery(document).on('click', '.js__shoppingcart__button-allow-quote', function() {

    });
    
    /***********************************************************/
    /* Setup the checkout page
    /***********************************************************/
    ShoppingCart.setupCheckout = function setupCheckout() {        
        if (!storejs.get('grav-shoppingcart-basket-data') || storejs.get('grav-shoppingcart-basket-data').length == 0) {
            jQuery('.js__checkout__block').html(window.PLUGIN_SHOPPINGCART.translations.NO_ITEMS_IN_CART);
            jQuery('.js__checkout__block').show();
            return;
        }
        
        if (!ShoppingCart.orderAmountIsGreaterThenMinimum()) {
            jQuery('.js__checkout__block').html(
                window.PLUGIN_SHOPPINGCART.translations.MINIMUM_TO_PLACE_AN_ORDER + ' ' + 
                ShoppingCart.renderPriceWithCurrency(ShoppingCart.settings.cart.minimumSumToPlaceOrder) +
                '<br>' +
                '<a href="#" class="btn btn-success js__shoppingcart__continue-shopping">' + 
                window.PLUGIN_SHOPPINGCART.translations.CONTINUE_SHOPPING + 
                '</a>'
                );
            jQuery('.js__checkout__block').show();
            return;            
        }
        
        // let store owners check stock offering an event hook
        var onSetupCheckoutPage;
        jQuery(document).trigger(onSetupCheckoutPage = jQuery.Event('onSetupCheckoutPage'));
        
        if (onSetupCheckoutPage.result === false) {
            return;
        }
        
        if (storejs.get('grav-shoppingcart-basket-data').length == 0) { 

            jQuery('.js__checkout__block').html(window.PLUGIN_SHOPPINGCART.translations.NO_ITEMS_IN_CART);
            jQuery('.js__checkout__block').show();
            return;
        } 
        //I have items in the cart, I can go on        
        jQuery('.js__checkout__block').show();

        var countries = ShoppingCart.getCountries();
        var select = document.getElementById('js__billing__country');
        if (select) {
            for (index in countries) {
                if (ShoppingCart.countryCanBuy(countries[index].code)) {
                    select.options[select.options.length] = new Option(countries[index].name, countries[index].code);
                }
            }
        }

        var states = ShoppingCart.getUSAStates();
        select = document.getElementById('js__billing__state');
        if (select) {
            for (var i = 0; i < states.length; i++) {
                select.options[select.options.length] = new Option(states[i].name, states[i].code);
            }
        }

        jQuery("#js__billing__country").val(ShoppingCart.settings.general.default_country || 'US');
        ShoppingCart.countryChanged();

        ShoppingCart.populatePaymentOptions();

        if ((ShoppingCart.settings.general.default_country || 'US') === 'US') {
            jQuery('.js__billing__state__control').show();
            ShoppingCart.stateChanged();
        } else {
            jQuery('.js__billing__province__control').show();
        }
        
    };

    /***********************************************************/
    /* Render a correctly parsed price with the currency at the right position
    /***********************************************************/
    ShoppingCart.formattedPrice = function formattedPrice(price) {
        price = parseFloat(price).toFixed(2);

        if (ShoppingCart.settings.ui.remove_cents_if_zero) {
            if (price  % 1 == 0) {
                price  = parseInt(price , 10);
            }
        }
        if (ShoppingCart.settings.ui.currency_decimal_comma) {
            price  = price.toString().replace(".", ",");
        }
        return price;
    };
    
    
    /***********************************************************/
    /* Render a correctly parsed price with the currency at the right position
    /***********************************************************/
    ShoppingCart.renderPriceWithCurrency = function renderPriceWithCurrency(price) {
        var currency_symbol = ShoppingCart.currentCurrencySymbol();

        price = parseFloat(price).toFixed(2);

        if (ShoppingCart.settings.ui.remove_cents_if_zero) {
            if (price  % 1 == 0) {
                price  = parseInt(price , 10);
            }
        }
        if (ShoppingCart.settings.ui.currency_decimal_comma) {
            price  = price.toString().replace(".", ",");
        }

        if (ShoppingCart.showCurrencyBeforePrice()) {
            return '<span class="currency">' + currency_symbol + '</span> ' + price;
        } else {
            return price + ' <span class="currency">' + currency_symbol + '</span>';
        }
    };

    ShoppingCart.isMobile = function isMobile() {
        var isAndroid = function() {
            return navigator.userAgent.match(/Android/i);
        };

        var isBlackBerry = function() {
            return navigator.userAgent.match(/BlackBerry/i);
        };

        var isiOS = function() {
            return navigator.userAgent.match(/iPhone|iPad|iPod/i);
        };

        var isOpera = function() {
            return navigator.userAgent.match(/Opera Mini/i);
        };

        var isWindows = function() {
            return navigator.userAgent.match(/IEMobile/i);
        };

        var isAny = function() {
            if (isAndroid() || isBlackBerry() || isiOS() || isOpera() || isWindows()) {
                return true;
            } else if (jQuery(window).width() <= parseInt(ShoppingCart.settings.ui.short_labels_breakpoint)) {
                return true;
            }
            return false;
        };

        return isAny();
    };    

    /***********************************************************/
    /* Run in our own function for countryCanBuy as the implementation in ShoppingCart is a stub
     * 
     * @todo this may not work in older browsers like IE 11
     * @todo could be optimized as we repeat the object to array conversion for each check
     * @param {type} countryCode
     * @returns {Boolean}
     */
    /***********************************************************/
    ShoppingCart.countryCanBuy = function countryCanBuy(countryCode) {
        if (typeof ShoppingCart.restrictedCountries === 'undefined' && typeof ShoppingCart.settings.shop.restrict_countries !== 'undefined') {
            ShoppingCart.restrictedCountries = Object.values(ShoppingCart.settings.shop.restrict_countries);
        }
        if (ShoppingCart.restrictedCountries.length && ShoppingCart.restrictedCountries.includes(countryCode)) {
            return true;
        } else if(ShoppingCart.restrictedCountries.length && ShoppingCart.restrictedCountries.includes("*")) {
            return true;
        }
        return false;
    };

})(window.ShoppingCart);
