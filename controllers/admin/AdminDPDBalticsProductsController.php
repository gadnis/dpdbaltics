<?php
/**
 * NOTICE OF LICENSE
 *
 * @author    INVERTUS, UAB www.invertus.eu <support@invertus.eu>
 * @copyright Copyright (c) permanent, INVERTUS, UAB
 * @license   Addons PrestaShop license limitation
 * @see       /LICENSE
 *
 *  International Registered Trademark & Property of INVERTUS, UAB
 */
use Invertus\dpdBaltics\Builder\Template\Admin\ProductBlockBuilder;
use Invertus\dpdBaltics\Controller\AbstractAdminController;
use Invertus\dpdBaltics\Exception\ProductUpdateException;
use Invertus\dpdBaltics\Service\Carrier\UpdateCarrierService;
use Invertus\dpdBaltics\Service\Product\ProductService;
use Invertus\dpdBaltics\Service\Product\UpdateProductShopService;
use Invertus\dpdBaltics\Service\Product\UpdateProductZoneService;

require_once dirname(__DIR__).'/../vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminDPDBalticsProductsController extends AbstractAdminController
{
    public function __construct()
    {
        $this->className = 'DPDProduct';
        $this->table = DPDProduct::$definition['table'];
        $this->identifier = DPDProduct::$definition['primary'];
        $this->allow_export = true;

        parent::__construct();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme); // TODO: Change the autogenerated stub
        $shops = Shop::getShops(true);
        $isMultiShop = (bool) Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && (count($shops) > 1);
        Media::addJsDef(
            [
                'isMultiShop' => $isMultiShop,
                'chosenPlaceholder' => $this->module->l('Click to select'),
                'errorMessages' => [
                    'noZones' => $this->l('Missing zones in highlighted area.'),
                    'noShops' => $this->l('Missing shops in highlighted area.'),
                    'noProductName' => $this->l('Missing product name in highlighted area.'),
                    'productSaveFailed' => $this->l('Failed to save product'),
                ],
                'messages' => [
                    'productSaveSuccess' => $this->l('Product successfully saved'),
                ]
            ]
        );

        $pluginPath = Media::getJqueryPluginPath('chosen');
        $this->addJS($pluginPath['js']);
        $this->addCSS(key($pluginPath['css']));
        $this->addCSS($this->module->getPathUri() . 'views/css/admin/product.css');
        $this->addCSS($this->module->getPathUri() . 'views/css/admin/validate_error.css');

        $this->addJS($this->module->getPathUri() . 'views/js/admin/search_block.js');
        $this->addJS($this->module->getPathUri() . 'views/js/admin/product.js');
    }

    public function initContent()
    {
        parent::initContent();

        /** @var ProductBlockBuilder $productBlockBuilder */
        $productBlockBuilder = $this->module->getModuleContainer()->get('invertus.dpdbaltics.builder.template.admin.product_block_builder');
        $this->content .= $productBlockBuilder->renderProducts();

        $this->context->smarty->assign('content', $this->content);
    }

    public function postProcess()
    {
        $this->postProcessProduct();

        return parent::postProcess();
    }

    private function postProcessProduct()
    {
        if (!Tools::isSubmit('ajax') && Tools::getValue('action') !== 'updateProduct') {
            return;
        }

        $response['status'] = true;

        $params = [];
        parse_str(Tools::getValue('data'), $params);

        /** @var ProductService $updateProductService */
        /** @var UpdateProductZoneService $updateProductZoneService */
        /** @var UpdateProductShopService $updateProductShopService */
        /** @var UpdateCarrierService $updateCarrierService */
        $updateProductService = $this->module->getModuleContainer()->get('invertus.dpdbaltics.service.product.product_service');
        $updateProductZoneService = $this->module->getModuleContainer()->get('invertus.dpdbaltics.service.product.update_product_zone_service');
        $updateProductShopService = $this->module->getModuleContainer()->get('invertus.dpdbaltics.service.product.update_product_shop_service');
        $updateCarrierService = $this->module->getModuleContainer()->get('invertus.dpdbaltics.service.carrier.update_carrier_service');

        $productId = $params['id-product'];

        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && isset($params['shops_select'])) {
            $shops = $params['shops_select'];
        } else {
            // if multi-store is off, all shops are set
            $shops = ['0'];
        }

        try {
            $isActive = $params["product-active-{$params['id-product']}"];
            $updateProductService->updateProduct($productId, $isActive);
            $updateProductZoneService->updateProductZones($productId, $params['zones_select']);
            $updateProductShopService->updateProductShop($productId, $shops);
            $updateCarrierService->updateCarrier($productId, $params);
        } catch (ProductUpdateException $e) {
            $response['status'] = false;
            $response['errors'][] = $e->getMessage();
        }

        $this->ajaxDie(json_encode($response));
    }
}
