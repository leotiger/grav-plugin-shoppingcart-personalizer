<?php
namespace Grav\Plugin;

use Grav\Common\File\CompiledYamlFile;
use Grav\Common\GPM\GPM;
use Grav\Common\Grav;
use Grav\Common\Config\Config;
use Grav\Common\Inflector;
use Grav\Common\Language\Language;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;
use Grav\Common\Plugin;
use Grav\Common\Uri;
use Grav\Common\User\User;
use RocketTheme\Toolbox\File\File;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\Session\Session;
use Grav\Plugin\Shortcodes\BlockShortcode;



/**
 * Class ShoppingcartPersonalizerPlugin
 * @package Grav\Plugin
 */
class ShoppingcartPersonalizerPlugin extends Plugin
{
    protected $plugin_name = 'shoppingcart-personalizer';
    protected $terms_url_modal; 
    protected $baseURL;
    protected $shoppingcart;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0],
            'onGetPageBlueprints'     => ['onGetPageBlueprints', 0],
            'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],            
            'onGetPageTemplates'      => ['onGetPageTemplates', 0],
        ];
    }

    /**
     * Add page blueprints
     *
     * @param Event $event
     */
    public function onGetPageBlueprints(Event $event)
    {
        /** @var Types $types */
        $types = $event->types;
        $types->scanBlueprints('plugins://shoppingcart-personalizer/blueprints/pages/');
    }

    /**
     * Add page template types.
     *
     * @param Event $event
     */
    public function onGetPageTemplates(Event $event)
    {
        /** @var Types $types */
        $types = $event->types;
        $types->scanTemplates('plugins://shoppingcart-personalizer/templates');
    }
    
    /**
     * Add current directory to twig lookup paths.
     */
    public function onTwigTemplatePaths()
    {
      // Register Shopping Cart Studio Twig templates
      $this->grav['twig']->twig_paths[] = __DIR__ . '/templates';

    }

    /**
     * Add translations needed in JavaScript code
     */
    private function addTranslationsToFrontend()
    {
        $assets = $this->grav['assets'];
        $translations = '';
        $strings = $this->getTranslationStringsForFrontend();
        foreach ($strings as $string) {
            $translations .= 'PLUGIN_SHOPPINGCART.translations.' . $string . ' = "' . $this->grav['language']->translate(['PLUGIN_SHOPPINGCART.' . $string]) . '"; ' . PHP_EOL;
        }

        $assets->addInlineJs($translations);
    }

    /**
     * Get an array containing the strings used in the frontend (js) translations
     *
     * @return array
     */
    protected function getTranslationStringsForFrontend()
    {
        return [
            'CHECKOUT_TERMS_LINK',
            'BILLING_VAT_NUMBER',
            'CHECKOUT_HEADLINE_SHIPPING_ADDRESS',
            'CHECKOUT_HEADLINE_BILLING_ADDRESS',                       
            'PERSONALIZE_SOLD',
            'PERSONALIZE_TERMS',
            'PERSONALIZE_BILLING_VAT_NUMBER',
            'PERSONALIZE_SHIPPING_ADDRESS',
            'PERSONALIZE_SHOP_PAGE',
            'PERSONALIZE_USE_BREADCRUMB',
            'PERSONALIZE_CHECKOUT_HEADLINE_SHIPPING_ADDRESS',
            'PERSONALIZE_CHECKOUT_HEADLINE_BILLING_ADDRESS',
            'PERSONALIZE_CHECKOUT_CHOOSE_SHIPPING_METHOD_DESC',
            'PERSONALIZE_CART_IMAGE_EQUALIZER',
            'PERSONALIZE_SHOP_TEST_MODE',
            'PERSONALIZE_ALLOW_QUOTE',
            'PERSONALIZE_ALLOW_QUOTE_SEND',
            'PERSONALIZE_ALLOW_QUOTE_CLOSE',
            'PERSONALIZE_MODAL_CLOSE',
            'PERSONALIZE_ALLOW_QUOTE_TITLE',
            'PERSONALIZE_PLACE_OFFER_MESSAGE',
            'PERSONALIZE_PLACE_OFFER_NAME',
            'PERSONALIZE_CAPTCHA_NOT_VALID',
            'PERSONALIZE_PLACE_OFFER_THANKS',
            'PERSONALIZE_PLACE_OFFER_SUBMIT',
            'PERSONALIZE_PLACE_OFFER_PRODUCT',
            'PERSONALIZE_PLACE_OFFER_PRODUCT_URL',
            'PERSONALIZE_BILLING_DATA',
            'PERSONALIZE_CHECKOUT_ADDRESS_CONTINUED',
            'PERSONALIZE_CHECKOUT_INDICATIONS',
            'PERSONALIZE_HEADLINE_MODAL_TERMS',
            
        ];
    }    
    
    /**
     */
    public function mergeShoppingCartPluginConfig()
    {
        $config = $this->config->get('plugins.' . $this->plugin_name);
        unset($config['enabled']);
        $this->config->set('plugins.shoppingcart', array_replace_recursive($this->config->get('plugins.shoppingcart'), $config));
    }
        
    /**
     * Enable search only if url matches to the configuration.
     */
    public function onPluginsInitialized()
    {
        // Create ShoppingCart.

        require_once(realpath(__DIR__ . '/../shoppingcart/classes/shoppingcart.php'));
        $this->shoppingcart = new ShoppingCart\ShoppingCart();
        
        if (!$this->isAdmin()) {
            $this->mergeShoppingCartPluginConfig();
            // Add translations needed in JavaScript code
            $this->enable([
                'onPageInitialized'                       => ['onPageInitialized', -1000],
                'onPagesInitialized'      => ['onPagesInitialized', 10],                
            ]);
        } else {
            $this->enable([
                'onTwigTemplatePaths'                        => ['onTwigAdminTemplatePaths', 1000],
            ]);
        }
    }

    /**
     * Dynamically push in our place order form into product pages if desired
     */
    public function onPagesInitialized($event)
    {
        $uri = $this->grav['uri'];
        $pages = $this->grav['pages'];
        $page = $pages->dispatch($uri->path());
        
        if (!$page instanceof Page) {
            return false;
        }
        
        $pageLang = $page->language();
        $pageFile = $page->file()->basename() . '.md';
        $placeOfferForm = $this->config->get('plugins.shoppingcart-personalizer.placeoffer_form');
        
        // Add hidden fields with the product data...
        $productLabel = $this->grav['language']->translate('PLUGIN_SHOPPINGCART.PERSONALIZE_PLACE_OFFER_PRODUCT');
        $urlLabel = $this->grav['language']->translate('PLUGIN_SHOPPINGCART.PERSONALIZE_PLACE_OFFER_PRODUCT_URL');
        array_push($placeOfferForm['fields'], ['name' => 'productTitle', 'label' => $productLabel, 'type' => 'hidden', 'classes' => 'hide', 'default' => $page->header()->title]);
        array_push($placeOfferForm['fields'], ['name' => 'productUrl', 'label' => $urlLabel, 'type' => 'hidden', 'classes' => 'hide', 'default' => $page->url(true)]);
        if ($pageFile === 'shoppingcart_product.' . $pageLang . '.md' || $pageFile === 'shoppingcart_product.md' && isset($page->header()->allowquote) && $page->header()->allowquote && $placeOfferForm) {
            $page->modifyHeader('form', $placeOfferForm);
        }
    }
    
    
    /**
     * Initialize configuration
     */
    public function onPageInitialized()
    {
        /** @var Page $page */
        $page = $this->grav['page'];
        
        // if I'm not in a Shop page, and I don't need to add JS globally, return
        if (!$this->config->get('plugins.shoppingcart.general.load_js_globally')) {
            if (!in_array($page->template(), $this->shoppingcart->getOwnPageTypes())) {
                return;
            }
        }
        
        // Fix shoppingcart mis-configurations while PR is not accepted, approved by Flavio Copes
        if (isset($page->header()->shoppingcart)) {
            unset($page->header()->shoppingcart);
        }
        if ($page->template() == "shoppingcart_checkout" && !isset($page->header()->form)) {
            $checkoutForm = $this->config->get('plugins.shoppingcart.checkout_form');
            $page->header()->form = $checkoutForm;
        }        
        if ($terms_url = $this->config->get('plugins.shoppingcart.urls.terms_url', false)) {
            $twig = $this->grav['twig'];            
            $twig->twig_vars['terms_url'] = $this->config->get('plugins.shoppingcart.urls.terms_url');
        }
        
        if ($terms_url = $this->config->get('plugins.shoppingcart.urls.terms_url', false)) {
            $twig = $this->grav['twig'];            
            $twig->twig_vars['terms_url'] = $this->config->get('plugins.shoppingcart.urls.terms_url');
        }
        
        $this->grav['assets']->addJs('plugin://' . $this->plugin_name . '/assets/js/shoppingcart-personalizer.js');
        $this->grav['assets']->addCss('plugin://' . $this->plugin_name . '/assets/css/shoppingcart-personalizer.css');        
        // Add translations needed in JavaScript code
        $this->addTranslationsToFrontend();
    }        

    /**
     * Add plugin templates path
     */
    public function onTwigAdminTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/admin/templates';
    }
    
    
}
