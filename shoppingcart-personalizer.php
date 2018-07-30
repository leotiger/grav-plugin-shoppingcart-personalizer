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
use Grav\Common\Filesystem\Folder;
use Grav\Common\Data\Blueprints;
use Symfony\Component\Yaml\Yaml;
use Grav\Common\Utils;

/**
 * Class ShoppingcartPersonalizerPlugin
 * @package Grav\Plugin
 */
class ShoppingcartPersonalizerPlugin extends Plugin
{
    const BYTES_TO_MB = 1048576;
    
    protected $plugin_name = 'shoppingcart-personalizer';
    protected $terms_url_modal; 
    protected $baseURL;
    protected $shoppingcart;
    protected $route = 'shoppingcart/shoppingcart_order';    
    protected $shoppingcart_route = 'shoppingcart';
    protected $personalize_url;
    
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
            'onBlueprintCreated' => ['onBlueprintCreated', 1000],
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
            'PERSONALIZE_VARIATION_REMARK',
            'PERSONALIZE_CART_VARIATIONS_HEADLINE',
            'PERSONALIZE_CART_VARIATIONS_BASEPRICE',
            'PERSONALIZE_VARIATION_UPLOAD_HINT',
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
        require_once(realpath(__DIR__ . '/../shoppingcart/classes/shoppingcart.php'));
        $this->shoppingcart = new ShoppingCart\ShoppingCart();        
        $uri = $this->grav['uri'];
        if (!$this->isAdmin()) {
            $this->mergeShoppingCartPluginConfig();            
            // Add translations needed in JavaScript code
            $this->enable([
                'onPageInitialized'                       => ['onPageInitialized', -1000],
                'onPagesInitialized'      => ['onPagesInitialized', 9],                
                'onFormProcessed' => ['onFormProcessed', 0],
            ]);
            $this->personalize_url = $this->config->get('plugins.shoppingcart-personalizer.urls.personalize_url');
            if ($this->personalize_url && $this->personalize_url == $uri->path()) {
                $this->enable([
                    'onPagesInitialized' => ['addPersonalizePage', 0]
                ]);
            }
            
        } else {
            $this->enable([
                'onTwigTemplatePaths'                        => ['onTwigAdminTemplatePaths', 1000],
                'onAdminMenu'                                => ['onAdminMenu', -100],                
                'onPageInitialized'                       => ['onAdminPageInitialized', 0],
                'onAdminSave' => ['onAdminSave', 1000],	
            ]);
        }
    }

    /**
     * Dynamically add the personalizer page
     */
    public function addPersonalizePage()
    {
        $url = $this->personalize_url;
        $filename = 'shoppingcart_personalize.md';
        $page = $this->createPersonalizePage($url, $filename);
    }    
    
    /**
     * @param $url
     * @param $filename
     */
    protected function createPersonalizePage($url, $filename)
    {
        $pages = $this->grav['pages'];
        $page = $pages->dispatch($url);

        if (!$page) {
            /** @var Uri $uri */
            $uri = $this->grav['uri'];
            $order = $this->findOrder($uri->query('id'), $uri->query('token'));                
            /** @var Twig $twig */
            $twig = $this->grav['twig'];
            $twig->twig_vars['order'] = $order;
            $twig->twig_vars['currency'] = $this->config->get('plugins.shoppingcart.general.currency');
            
            $page = new Page;
            $page->init(new \SplFileInfo(__DIR__ . "/pages/" . $filename));
            $page->slug(basename($url));
            $addForm = false;
            if (!isset($order['personalized'])) {
                $personalizeOrderForm = $this->config->get('plugins.shoppingcart-personalizer.personalizeorder_form', []);
                $personalizeOrderForm['action'] = $this->personalize_url;
                $personalizeOrderForm['fields'] = [];
                $personalizeOrderForm['buttons'] = [
                    ['type' => 'submit', 'classes' => 'btn btn-primary', 'value' => 'PLUGIN_SHOPPINGCART.PERSONALIZE_PRODUCTS_SUBMIT']
                ];
                $personalizeOrderForm['process'] = [
                    ['personalizeorder' => ['personalizeorder' => true]],
                    ['redirect' => $this->personalize_url],
                ];
                
                array_push($personalizeOrderForm['fields'], ['name' => 'order_token', 'type' => 'hidden', 'default' => $order['token']]);
                array_push($personalizeOrderForm['fields'], ['name' => 'order_created_on', 'type' => 'hidden', 'default' => $order['created_on']]);

                if (isset($order['products'])) {
                    foreach($order['products'] as $product) {
                        if (isset($product['product']['variants'])) {
                            foreach($product['product']['variants'] as $variant) {
                                if (isset($variant['vardata']['fileupload']) && $variant['vardata']['fileupload'] && $variant['vardata']['fileupload'] != 'false') {
                                    $addForm = true;
                                    $this->removePersonalizationUploads('orderfile_' . $order['created_on'] . '-' . $order['token'] . '_' . $variant['groupid'] . '-' . $variant['varid']);
                                    array_push($personalizeOrderForm['fields'], ['label' => $variant['vardata']['title'], 'name' => 'orderfile_' . $order['created_on'] . '-' . $order['token'] . '_' . $variant['groupid'] . '-' . $variant['varid'], 'type' => 'file', 'multiple' => false]);                            
                                }
                                if (isset($variant['vardata']['freetext']) && $variant['vardata']['freetext'] && $variant['vardata']['freetext'] != 'false') {
                                    $freetext = $variant['varfreetext'];
                                    array_push($personalizeOrderForm['fields'], ['label' => $variant['vardata']['title'], 'name' => 'freetext_' . $order['created_on'] . '-' . $order['token'] . '_' . $variant['groupid'] . '-' . $variant['varid'], 'type' => 'text', 'default' => $freetext]);                            
                                    $addForm = true;
                                }
                                
                            }
                        }
                    }
                }
                if ($addForm) {
                    $page->modifyHeader('form', $personalizeOrderForm);        
                }
            }
            $pages->addPage($page, $url);
            if ($uri->post('__form-file-uploader__') && $uri->extension() === 'json') {
                $this->json_response = $this->uploadFiles($page);
            }

        }
        return $page;
    }
    
    
    /**
     * Add the Personalize form handler
     * @param Event $event
     */
    public function onFormProcessed(Event $event)
    {
        switch ($event['action']) {
            case 'personalizeorder':
                $this->handlePersonalize($event);
        }
    }
    
    /**
     * Handle cart personalization
     * 
     * @param Event $event
     */
    protected function handlePersonalize(Event $event)
    {
        $action = $event['action'];
        $form = $event['form'];
        $params = $event['params'];
        $post = !empty($_POST) ? $_POST['data'] : [];
        $files = !empty($_FILES) ? $_FILES : [];
        if (isset($post['_json'])) {
            $post = array_replace_recursive($post, $this->jsonDecode($post['_json']));
            unset($post['_json']);
        }
        $post = $this->cleanDataKeys($post);        
        if (isset($post['order_created_on']) && isset($post['order_token'])) {            
            $order = $this->findOrder($post['order_created_on'], $post['order_token']);
            if ($order) {
                $order['personalized'] = true;
                
                if (isset($order['products'])) {
                    array_walk($order['products'], function(&$product, $key, $data) {
                        $order = $data[0];
                        $post = $data[1];
                        if (isset($product['product']['variants'])) {
                            array_walk($product['product']['variants'], function(&$variant, $varkey, $data) {            
                                $order = $data[0];
                                $post = $data[1];
                                if (isset($variant['vardata']['freetext']) && $variant['vardata']['freetext'] && $variant['vardata']['freetext'] != 'false') {
                                    $postfield = 'freetext_' . $order['created_on'] . '-' . $order['token'] . '_' . $variant['groupid'] . '-' . $variant['varid'];
                                    if (isset($post[$postfield])) {
                                        $variant['varfreetext'] = $post[$postfield];
                                    }
                                }
                                if (isset($variant['vardata']['fileupload']) && $variant['vardata']['fileupload'] && $variant['vardata']['fileupload'] != 'false') {
                                    $files = $this->getPersonalizationUploads('orderfile_' . $order['created_on'] . '-' . $order['token'] . '_' . $variant['groupid'] . '-' . $variant['varid']);
                                    if ($files) {
                                        $variant['varuploads'] = $files;
                                    }
                                }
                            }, [$order, $post]);
                        }
                    }, [$order, $post]);
                }
                
                $orderfile = $this->getOrderFilename($post['order_created_on'], $post['order_token']);
                if ($orderfile) {
                    $this->savePersonalizedOrderToFilesystem($order, $orderfile);
                    $this->grav['log']->info('we have loaded our order on post');
                    
                }
            }
        } else {
            
        }
    }
    
    /**
     * Saves the personalized order to the filesystem
     *
     * @param $order
     *
     * @return string
     */
    private function savePersonalizedOrderToFilesystem($order, $filename)
    {
        $body = Yaml::dump($order);

        $file = File::instance(DATA_DIR . 'shoppingcart' . '/' . $filename);
        $file->save($body);
    }    
    /**
     * Recursively JSON decode data.
     *
     * @param  array $data
     *
     * @return array
     */
    protected function jsonDecode(array $data)
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                $value = $this->jsonDecode($value);
            } else {
                $value = json_decode($value, true);
            }
        }

        return $data;
    }

    protected function cleanDataKeys($source = [])
    {
        $out = [];

        if (is_array($source)) {
            foreach ($source as $key => $value) {
                $key = str_replace(['%5B', '%5D'], ['[', ']'], $key);
                if (is_array($value)) {
                    $out[$key] = $this->cleanDataKeys($value);
                } else {
                    $out[$key] = $value;
                }
            }
        }

        return $out;
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
        $template = $page->template();
        if ($template === 'shoppingcart_product' && isset($page->header()->allowquote) && $page->header()->allowquote && $placeOfferForm) {
            // Add hidden fields with the product data...            
            $productLabel = $this->grav['language']->translate('PLUGIN_SHOPPINGCART.PERSONALIZE_PLACE_OFFER_PRODUCT');
            $urlLabel = $this->grav['language']->translate('PLUGIN_SHOPPINGCART.PERSONALIZE_PLACE_OFFER_PRODUCT_URL');
            array_push($placeOfferForm['fields'], ['name' => 'productTitle', 'label' => $productLabel, 'type' => 'hidden', 'classes' => 'hide', 'default' => $page->header()->title]);
            array_push($placeOfferForm['fields'], ['name' => 'productUrl', 'label' => $urlLabel, 'type' => 'hidden', 'classes' => 'hide', 'default' => $page->url(true)]);
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
        
        if ($this->config->get('plugins.shoppingcart.ui.loadfancybox', false)) {
            $this->grav['assets']->addJs('plugin://' . $this->plugin_name . '/assets/js/jquery.fancybox.js');
            $this->grav['assets']->addCss('plugin://' . $this->plugin_name . '/assets/css/jquery.fancybox.css');        
        }
        //$this->grav['assets']->addJs('plugin://' . $this->plugin_name . '/assets/js/dropzone.js');        
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
    
    /**
     * Extend page blueprints with configuration options.
     *
     * @param Event $event
     *
     */
    public function onBlueprintCreated(Event $event)
    {
        $blueprint = $event['blueprint'];
        $testblueprints = ['shoppingcart_product', 'shoppingcart_products', 'shoppingcart_categories'];
        $bluetest = $this->config->get('plugins.shoppingcart-personalizer.ui.' . $event['type'] . '_blueprint', 'default');
        if (!in_array($event['type'], $testblueprints)) {
            return;
        }
        $available = Pages::types();
        $blueprints = new Blueprints(Pages::getTypes());
        if (array_key_exists($bluetest, $available)) {
            $extents = $blueprints->get($bluetest);                    
        } else {                    
            $extents = $blueprints->get('default');
        }
        $blueprint->extend($extents);                
    }
    
    /**
     * Called when a page is saved from the admin plugin.
     * Assure consistent ids for groups and variations
     */
    public function onAdminSave($event)
    {
        $page = $event['object'];

        if (!$page instanceof Page || $page->template() !== 'shoppingcart_product') {
            return false;
        }
        if (isset($page->header()->groups) && is_array($page->header()->groups)) {
            array_walk($page->header()->groups, function(&$group, $key, &$groupids) {            
                if (in_array($group['groupid'], $groupids)) {
                    $group['groupid'] = $group['groupid'] . '-' . $key;                
                }
                if (isset($group['variations']) && is_array($group['variations'])) {
                    array_walk($group['variations'], function(&$variation, $varkey, &$varids) {            
                        if (in_array($variation['variationid'], $varids)) {
                            $variation['variationid'] = $variation['variationid'] . '-' . $varkey;                
                        }
                        $varids[] = $variation['variationid'];
                    }, []);
                }
                $groupids[] = $group['groupid'];
            }, []);
        }
    }    
    
    /**
     * Remove personalization uploads
     *
     * We need a workaround for the inconsistency bug in shoppingcart
     * see issue 
     * 
     * @param string $id order id
     * @param string $token order token
     * @return order
     */
    private function removePersonalizationUploads($path)
    {        
        $files = false;
        if ($path) {
            $orderinfo = explode('_', $path)[1];
            $varinfo = explode('_', $path)[2];
            
            $path = DATA_DIR . '/scp/' . $orderinfo . '_' . $varinfo;

            if (file_exists($path)) {
                //$files = [];
                //$list = Folder::all($path);
                array_map('unlink', glob($path . DS . "*.*"));
                rmdir($path);
            }
        }
        return $files;
    }    
    /**
     * Get personalization uploads
     *
     * We need a workaround for the inconsistency bug in shoppingcart
     * see issue 
     * 
     * @param string $id order id
     * @param string $token order token
     * @return order
     */
    private function getPersonalizationUploads($path)
    {        
        $files = false;
        if ($path) {
            $orderinfo = explode('_', $path)[1];
            $varinfo = explode('_', $path)[2];
            
            $path = DATA_DIR . '/scp/' . $orderinfo . '_' . $varinfo;

            if (file_exists($path)) {
                $files = [];
                $list = Folder::all($path);
                foreach ($list as $filename) {
                    $files[] = '/user/data/scp/' . $orderinfo . '_' . $varinfo . '/' . $filename;
                }
            }
        }
        return $files;
    }    

    /**
     * Find order
     *
     * We need a workaround for the inconsistency bug in shoppingcart
     * see issue 
     * 
     * @param string $id order id
     * @param string $token order token
     * @return order
     */
    private function findOrder($id, $token)
    {        
        $order = false;
        if ($id && $token) {
            $path = DATA_DIR . 'shoppingcart';

            if (!file_exists($path)) {
                Folder::mkdir($path);
            }

            $list = Folder::all($path);
            $splitid = explode('-', $id);            
            $find = 'order-' . $splitid[0] . '-' . $splitid[1] . '-';            
            foreach ($list as $filename) {
                $yaml = Yaml::parse(file_get_contents($path . DS . $filename));            
                if (stripos($filename, $find) === 0 && $yaml['token'] === $token) {
                    $order = $yaml;
                }
            }
        }
        return $order;
    }        
    
   /**
     * Get order filename
     *
     * We need a workaround for the inconsistency bug in shoppingcart
     * see issue 
     * 
     * @param string $id order id
     * @param string $token order token
     * @return order
     */
    private function getOrderFilename($id, $token)
    {        
        $orderfile = false;
        if ($id && $token) {
            $path = DATA_DIR . 'shoppingcart';

            if (!file_exists($path)) {
                Folder::mkdir($path);
            }

            $list = Folder::all($path);
            $splitid = explode('-', $id);
            $find = 'order-' . $splitid[0] . '-' . $splitid[1] . '-';
            foreach ($list as $filename) {
                $yaml = Yaml::parse(file_get_contents($path . DS . $filename));            
                if (stripos($filename, $find) === 0 && $yaml['token'] === $token) {
                    $orderfile = $filename;
                }
            }
        }
        return $orderfile;
    }        

    /**
     * Add navigation item to the admin plugin
     */
    public function onAdminPageInitialized()
    {
        $uri = $this->grav['uri'];
        $page = $this->grav['page'];
        if (strpos($uri->path(), $this->config->get('plugins.admin.route') . '/' . $this->route) !== false && $uri->param('id') && $uri->param('token')) {
            $page->template('shoppingcart_order');
            $twig = $this->grav['twig'];
            
            $order = $this->findOrder($uri->param('id'), $uri->param('token'));
            $twig->twig_vars['currency_symbol'] = $this->shoppingcart->getSymbolOfCurrencyCode($this->config->get('plugins.shoppingcart.general.currency'));
            $twig->twig_vars['personalize_url'] = $this->config->get('plugins.shoppingcart-personalizer.urls.personalize_url');            
            $twig->twig_vars['order'] = $order;
        }
    }
    
    /**
     * Add navigation item to the admin plugin
     */
    public function onAdminMenu()
    {
        //$uri = $this->grav['uri'];
            
        //if (strpos($uri->path(), $this->config->get('plugins.admin.route') . '/' . $this->route) !== false) {
        $this->grav['twig']->plugins_hooked_nav['PLUGIN_SHOPPINGCART.SHOPPING_CART'] = [
            'route' => $this->shoppingcart_route,
            'icon'  => 'fa-shopping-cart'
        ];
        //}
    }
    
    /**
     * Handles ajax upload for files.
     * Stores in a flash object the temporary file and deals with potential file errors.
     *
     * @return mixed True if the action was performed.
     */
    public function uploadFiles($page)
    {
        $post = $_POST;
        $grav = Grav::instance();
        $uri = $grav['uri']->url;
        $config = $grav['config'];
        $session = $grav['session'];
        if (stripos($post['name'], 'orderfile_') !== false) {
            $orderinfo = explode('_', $post['name'])[1];
            $varinfo = explode('_', $post['name'])[2];
            
            $settings = [
                'destination' => 'user/data/scp/' . $orderinfo . '_' . $varinfo,
                'accept' => ['image/*','application/zip','text/plain','application/x-rar-compressed'],
            ];
            //$this->data->blueprints()->schema()->getProperty($post['name']);
            $settings = (object) array_merge(
                ['destination' => $config->get('plugins.form.files.destination', 'self@'),
                 'avoid_overwriting' => $config->get('plugins.form.files.avoid_overwriting', false),
                 'random_name' => $config->get('plugins.form.files.random_name', false),
                 'accept' => $config->get('plugins.form.files.accept', ['image/*']),
                 'limit' => $config->get('plugins.form.files.limit', 10),
                 'filesize' => $this->getMaxFilesize(),
                ],
                (array) $settings,
                ['name' => $post['name']]
            );

            $upload = $this->normalizeFiles($_FILES['data'], $settings->name);

            // Handle errors and breaks without proceeding further
            if ($upload->file->error != UPLOAD_ERR_OK) {
                // json_response
                return [
                    'status' => 'error',
                    'message' => sprintf($grav['language']->translate('PLUGIN_FORM.FILEUPLOAD_UNABLE_TO_UPLOAD', null, true), $upload->file->name, $this->upload_errors[$upload->file->error])
                ];
            }

            // Handle bad filenames.
            $filename = $upload->file->name;
            if (strtr($filename, "\t\n\r\0\x0b", '_____') !== $filename || rtrim($filename, ". ") !== $filename || preg_match('|\.php|', $filename)) {
                $this->admin->json_response = [
                    'status'  => 'error',
                    'message' => sprintf($this->admin->translate('PLUGIN_ADMIN.FILEUPLOAD_UNABLE_TO_UPLOAD', null),
                        $filename, 'Bad filename')
                ];

                return false;
            }

            // Remove the error object to avoid storing it
            unset($upload->file->error);


            // Handle Accepted file types
            // Accept can only be mime types (image/png | image/*) or file extensions (.pdf|.jpg)
            $accepted = false;
            $errors = [];
            foreach ((array) $settings->accept as $type) {
                // Force acceptance of any file when star notation
                if ($type === '*') {
                    $accepted = true;
                    break;
                }

                $isMime = strstr($type, '/');
                $find = str_replace('*', '.*', $type);

                $match = preg_match('#'. $find .'$#', $isMime ? $upload->file->type : $upload->file->name);
                if (!$match) {
                    $message = $isMime ? 'The MIME type "' . $upload->file->type . '"' : 'The File Extension';
                    $errors[] = $message . ' for the file "' . $upload->file->name . '" is not an accepted.';
                    $accepted |= false;
                } else {
                    $accepted |= true;
                }
            }

            if (!$accepted) {
                // json_response
                return [
                    'status' => 'error',
                    'message' => implode('<br/>', $errors)
                ];
            }


            // Handle file size limits
            $settings->filesize *= self::BYTES_TO_MB; // 1024 * 1024 [MB in Bytes]
            if ($settings->filesize > 0 && $upload->file->size > $settings->filesize) {
                // json_response
                return [
                    'status'  => 'error',
                    'message' => $grav['language']->translate('PLUGIN_FORM.EXCEEDED_GRAV_FILESIZE_LIMIT')
                ];
            }


            // we need to move the file at this stage or else
            // it won't be available upon save later on
            // since php removes it from the upload location
            $tmp_dir = $grav['locator']->findResource('tmp://', true, true);
            $tmp_file = $upload->file->tmp_name;
            $tmp = $tmp_dir . '/uploaded-files/' . basename($tmp_file);

            Folder::create(dirname($tmp));
            if (!move_uploaded_file($tmp_file, $tmp)) {
                // json_response
                return [
                    'status' => 'error',
                    'message' => sprintf($grav['language']->translate('PLUGIN_FORM.FILEUPLOAD_UNABLE_TO_MOVE', null, true), '', $tmp)
                ];
            }

            $upload->file->tmp_name = $tmp;

            // Retrieve the current session of the uploaded files for the field
            // and initialize it if it doesn't exist
            $sessionField = base64_encode($uri);
            $flash = $session->getFlashObject('files-upload');
            if (!$flash) {
                $flash = [];
            }
            if (!isset($flash[$sessionField])) {
                $flash[$sessionField] = [];
            }
            if (!isset($flash[$sessionField][$upload->field])) {
                $flash[$sessionField][$upload->field] = [];
            }

            // Set destination
            $destination = Folder::getRelativePath(rtrim($settings->destination, '/'));
            $destination = $this->getPagePathFromToken($destination, $page);

            // Create destination if needed
            if (!is_dir($destination)) {
                Folder::mkdir($destination);
            }

            // Generate random name if required
            if ($settings->random_name) {
                $extension = pathinfo($upload->file->name)['extension'];
                $upload->file->name = Utils::generateRandomString(15) . '.' . $extension;
            }

            // Handle conflicting name if needed
            if ($settings->avoid_overwriting) {
                if (file_exists($destination . '/' . $upload->file->name)) {
                    $upload->file->name = date('YmdHis') . '-' . $upload->file->name;
                }
            }

            // Prepare object for later save
            $path = $destination . '/' . $upload->file->name;
            $upload->file->path = $path;
            // $upload->file->route = $page ? $path : null;

            // Prepare data to be saved later
            $flash[$sessionField][$upload->field][$path] = (array) $upload->file;

            // Finally store the new uploaded file in the field session
            $session->setFlashObject('files-upload', $flash);


            // json_response
            $json_response = [
                'status' => 'success',
                'session' => \json_encode([
                    'sessionField' => base64_encode($uri),
                    'path' => $upload->file->path,
                    'field' => $settings->name
                ])
            ];

            // Return JSON
            header('Content-Type: application/json');
            echo json_encode($json_response);
            exit;
        }
        return;
    }    
 
    /**
     * Internal method to normalize the $_FILES array
     *
     * @param array  $data $_FILES starting point data
     * @param string $key
     * @return object a new Object with a normalized list of files
     */
    protected function normalizeFiles($data, $key = '')
    {
        $files = new \stdClass();
        $files->field = $key;
        $files->file = new \stdClass();

        foreach ($data as $fieldName => $fieldValue) {
            // Since Files Upload are always happening via Ajax
            // we are not interested in handling `multiple="true"`
            // because they are always handled one at a time.
            // For this reason we normalize the value to string,
            // in case it is arriving as an array.
            $value = (array) Utils::getDotNotation($fieldValue, $key);
            $files->file->{$fieldName} = array_shift($value);
        }

        return $files;
    }
    
    /**
     * Get the configured max file size in bytes
     *
     * @param bool $mbytes return size in MB
     * @return int
     */
    public static function getMaxFilesize($mbytes = false)
    {
        $config = Grav::instance()['config'];

        $filesize_mb = (int)($config->get('plugins.form.files.filesize', 0) * static::BYTES_TO_MB);
        $system_filesize = $config->get('system.media.upload_limit', 0);
        if ($filesize_mb > $system_filesize || $filesize_mb === 0) {
            $filesize_mb = $system_filesize;
        }

        if ($mbytes) {
            return $filesize_mb;
        }

        return $filesize_mb  / static::BYTES_TO_MB;
    }    
    
    public function getPagePathFromToken($path, $page)
    {
        return Utils::getPagePathFromToken($path, $page);
    }
    
}
