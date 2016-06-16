<?php namespace Octommerce\Octommerce;

use Event;
use System\Classes\PluginBase;
use Octommerce\Octommerce\Classes\ProductManager;
use Illuminate\Foundation\AliasLoader;
use Octommerce\Octommerce\Models\Category;
use Octommerce\Octommerce\Models\Brand;
use Rainlab\Location\Models\State;
use Rainlab\User\Models\User;
use Octommerce\Octommerce\Models\OrderStatusLog;
use Responsiv\Pay\Models\InvoiceStatus;

/**
 * Octommerce Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = ['RainLab.User', 'RainLab.Location', 'Responsiv.Currency', 'Responsiv.Pay'];

    public function boot()
    {
        //
        // Extend RainLab.Location
        //
        State::extend(function($model) {
            $model->hasMany['cities'] = [
                'Octommerce\Octommerce\Models\City'
            ];
        });

        //
        // Extend RainLab.User
        //
        User::extend(function($model) {

            $model->addFillable([
                'phone',
                'country_id',
                'state_id',
                'city_id',
                'address',
                'postcode',
            ]);

            $model->hasMany['orders'] = ['Octommerce\Octommerce\Models\Order'];
            $model->belongsTo['city'] = 'Octommerce\Octommerce\Models\City';
            $model->belongsTo['state'] = 'Rainlab\Location\Models\State';
        });

        State::extend(function($model) {
            $model->hasMany['users'] = [
                'Rainlab\User\Models\User'
            ];

            $model->hasMany['cities'] = [
                'Octommerce\Octommerce\Models\City'
            ];
        });

        //
        // Built in Types
        //
        $productManager = ProductManager::instance();

        $productManager->registerTypes([
            'Octommerce\Octommerce\ProductTypes\Simple',
            'Octommerce\Octommerce\ProductTypes\Variable',
        ]);

        \Octommerce\Octommerce\Controllers\Products::extendFormFields(function($form, $model, $context) use($productManager) {
            if (!$model instanceof \Octommerce\Octommerce\Models\Product)
                return;

            $productManager->addCustomFields($form);

        });

        /*
         * Register menu items for the RainLab.Pages plugin
         */
        Event::listen('pages.menuitem.listTypes', function() {
            return [
                'product-category' => 'Product Category',
                'all-product-categories' => 'All Product Categories',
                'all-brands' => 'All Brands',
            ];
        });

        Event::listen('pages.menuitem.getTypeInfo', function($type) {
            if ($type == 'product-category' || $type == 'all-product-categories')
                return Category::getMenuTypeInfo($type);

            if ($type == 'all-brands')
                return Brand::getMenuTypeInfo($type);
        });

        Event::listen('pages.menuitem.resolveItem', function($type, $item, $url, $theme) {
            if ($type == 'product-category' || $type == 'all-product-categories')
                return Category::resolveMenuItem($item, $url, $theme);

            if ($type == 'all-brands')
                return Brand::resolveMenuItem($item, $url, $theme);
        });

        // Update order status
        Event::listen('responsiv.pay.beforeUpdateInvoiceStatus', function($record, $invoice, $statusId, $previousStatus) {
            $newStatus = InvoiceStatus::find($statusId);
            OrderStatusLog::createRecord($newStatus->code, $invoice->related, $record->comment);
        });
    }

    /**
     * Register method, called when the plugin is first registered.
     */
    public function register()
    {
        $alias = AliasLoader::getInstance();
        $alias->alias('Cart', 'Octommerce\Octommerce\Facades\Cart');
    }

    public function registerComponents()
    {
        return [
            'Octommerce\Octommerce\Components\ProductList'   => 'productList',
            'Octommerce\Octommerce\Components\ProductDetail' => 'productDetail',
            'Octommerce\Octommerce\Components\ProductSearch' => 'productSearch',
            'Octommerce\Octommerce\Components\Cart'          => 'cart',
            'Octommerce\Octommerce\Components\Checkout'      => 'checkout',
            'Octommerce\Octommerce\Components\Account'      => 'OctommerceAccount',
        ];
    }

    public function registerSettings()
    {
        return [
            'config' => [
                'label'       => 'Octommerce',
                'icon'        => 'icon-shopping-cart',
                'description' => 'Configure Octommerce plugins.',
                'class'       => 'Octommerce\Octommerce\Models\Settings',
                'permissions' => ['octommerce.octommerce.manage_plugins'],
                'order'       => 60
            ]
        ];
    }

    public function registerMarkupTags()
    {
        return [

        ];
    }
}
