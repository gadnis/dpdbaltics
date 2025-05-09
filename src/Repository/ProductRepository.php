<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */


namespace Invertus\dpdBaltics\Repository;

use Db;
use DbQuery;
use DPDProduct;
use mysqli_result;
use PDOStatement;
use PrestaShopCollection;
use PrestaShopDatabaseException;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductRepository extends AbstractEntityRepository
{
    public function getAllProducts()
    {
        return new PrestaShopCollection('DPDProduct');
    }

    public function getAllActiveProducts($onlyId = false)
    {
        $query = new DbQuery();
        if ($onlyId) {
            $query->select('id_dpd_product');
        } else {
            $query->select('*');
        }
        $query->from('dpd_product');
        $query->where('active = 1');

        return $this->db->getValue($query);
    }

    public function getAllActiveDpdProductReferences($onlyId = false)
    {
        $query = new DbQuery();
        if ($onlyId) {
            $query->select('id_reference');
        } else {
            $query->select('*');
        }
        $query->from('dpd_product');
        $query->where('active = 1');

        return $this->db->executeS($query);
    }

    public function deleteOldData()
    {
        $this->db->delete('dpd_product_shop');
        $this->db->delete('dpd_product_zone');
    }

    /**
     * @param $carrierId
     * @return array|bool|object|null
     */
    public function findProductByCarrierReference($carrierReference)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('dpd_product', 'dsc');
        $query->where('dsc.id_reference = '.(int) $carrierReference);

        return $this->db->getRow($query);
    }

    /**
     *
     * @param $idCarrier
     * @param $idShop
     * @return array|false|mysqli_result|PDOStatement|resource|null
     *
     * @throws PrestaShopDatabaseException
     */
    public function checkIfCarrierIsAvailableInShop($carrierReference, $idShop)
    {
        $query = new DbQuery();
        $query->select('csp.id_dpd_product');
        $query->from('dpd_product', 'sc');
        $query->leftJoin(
            'dpd_product_shop',
            'csp',
            'csp.`id_dpd_product` = sc.`id_dpd_product`'
        );
        $query->where('sc.id_reference = '.(int)$carrierReference . ' AND (csp.id_shop = ' . (int)$idShop . ' OR all_shops = 1)');

        return $this->db->executeS($query);
    }

    public function updateProductsForPriceRule($idPriceRule, array $carriers, $checkAll = 0)
    {
        if (!$idPriceRule) {
            return;
        }

        if (empty($carriers)) {
            return true;
        }

        if ($checkAll) {
            return $this->db->insert(
                'dpd_price_rule_carrier',
                [
                    'id_dpd_price_rule' => (int) $idPriceRule,
                    'all_carriers' => (int) $checkAll
                ]
            );
        }

        $counter = 0;
        foreach ($carriers as $carrier) {
            $result = $this->db->insert(
                'dpd_price_rule_carrier',
                [
                    'id_dpd_price_rule' => (int)$idPriceRule,
                    'id_reference' => (int)$carrier
                ],
                false,
                true,
                Db::ON_DUPLICATE_KEY
            );
            if (!$result) {
                $counter++;
            }
        }

        return ($counter) ? false : true;
    }

    public function removePriceRuleProducts($idPriceRule)
    {
        $this->db->delete(
            'dpd_price_rule_carrier',
            '`id_dpd_price_rule`=' . (int)$idPriceRule
        );
    }

    public function isProductPudo($carrierReference)
    {
        $query = new DbQuery();
        $query->select('id_dpd_product');
        $query->from('dpd_product');
        $query->where('id_reference = '.(int)$carrierReference . ' AND (is_pudo = 1)');

        return $this->db->getValue($query);
    }

    public function getPudoProducts()
    {
        $query = new DbQuery();
        $query->select('sc.`id_reference`, sc.`is_pudo`');
        $query->from('dpd_product', 'sc');
        $resource = $this->db->query($query);
        $result = [];
        while ($row = $this->db->nextRow($resource)) {
            $result[$row['id_reference']] = $row['is_pudo'];
        }

        return $result;
    }
    public function getProductIdByCarrierReference($carrierReference)
    {
        $query = new DbQuery();
        $query->select('id_dpd_product');
        $query->from('dpd_product');
        $query->where('id_reference = '.(int)$carrierReference);

        return $this->db->getValue($query);
    }

    public function getProductIdByProductReference($productReference)
    {
        $query = new DbQuery();
        $query->select('id_dpd_product');
        $query->from('dpd_product');
        $query->where('product_reference = "' . pSQL($productReference) . '"');

        return $this->db->getValue($query);
    }

    public function getProductIdByProductId($productId)
    {
        $query = new DbQuery();
        $query->select('id_dpd_product');
        $query->from('dpd_product');
        $query->where('id_dpd_product = "' . pSQL($productId) . '"');

        return $this->db->getValue($query);
    }

    public function getProductsByIdZone($idZone)
    {
        $query = new DbQuery();
        $query->select('sc.`id_dpd_product`, c.`name`');
        $query->from('dpd_product_zone', 'z');
        $query->innerJoin(
            'dpd_product',
            'sc',
            'sc.`id_dpd_product` = z.`id_dpd_product`'
        );
        $query->innerJoin(
            'carrier',
            'c',
            'c.`id_reference`=sc.`id_reference` AND c.`deleted`="0"'
        );
        $query->where('z.`id_dpd_zone`="'.(int) $idZone.'"');
        $result = $this->db->executeS($query);
        if (empty($result)) {
            return [];
        }
        return (array) $result;
    }

    public function deleteByProductReference($productReference)
    {
        return $this->db->delete(
            'dpd_product',
            '`product_reference`= "' . pSQL($productReference) . '"'
        );
    }

    /**
     * @param $carrierId
     * @return array|bool|object|null
     */
    public function findProductByProductReference($carrierReference)
    {
        $query = new DbQuery();
        $query->select('*');
        $query->from('dpd_product', 'dsc');
        $query->where('dsc.product_reference = "'. pSQL($carrierReference).'"');

        return $this->db->getRow($query) ?: null;
    }

    /**
     * @param int $carrierReference
     * @param int $countryId
     *
     * @return array|bool|mysqli_result|PDOStatement|resource|null
     * @throws PrestaShopDatabaseException
     */
    public function checkIfCarrierIsAvailableInCountry($carrierReference, $countryId)
    {
        $productId = $this->getProductIdByCarrierReference($carrierReference);
        $product = new DPDProduct($productId);

        if ($product->all_zones) {
            return ['id_dpd_product' => $productId];
        }

        $query = new DbQuery();
        $query->select('dp.id_dpd_product');
        $query->from('dpd_product', 'dp');

        $query->leftJoin(
            'dpd_product_zone',
            'dpz',
            'dp.`id_dpd_product` = dpz.`id_dpd_product`'
        );

        $query->leftJoin(
            'dpd_zone_range',
            'dzr',
            'dzr.`id_dpd_zone` = dpz.`id_dpd_zone`'
        );

        $query->where('dp.id_reference= '.(int) $product->id_reference);
        $query->where('dzr.id_country = '.(int) $countryId);

        return $this->db->executeS($query);
    }
}
