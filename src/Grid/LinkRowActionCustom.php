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

namespace Invertus\dpdBaltics\Grid;

use PrestaShop\PrestaShop\Core\Grid\Action\Row\AbstractRowAction;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\AccessibilityChecker\AccessibilityCheckerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

if (!defined('_PS_VERSION_')) {
    exit;
}

final class LinkRowActionCustom extends AbstractRowAction
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'link_custom';
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver
            ->setRequired([
                'route',
                'route_param_name',
                'route_param_field',
            ])
            ->setDefaults([
                'confirm_message' => '',
                'accessibility_checker' => null,
                //pass extra_route_params in case one param is not enough.
                //route_param_name and route_param_field becomes redundant, but it cannot be removed due to BC break
                'extra_route_params' => [],
                'clickable_row' => false,
                'is_label_download' => false,

            ])
            ->setAllowedTypes('route', 'string')
            ->setAllowedTypes('route_param_name', 'string')
            ->setAllowedTypes('is_label_download', 'boolean')
            ->setAllowedTypes('route_param_field', 'string')
            ->setAllowedTypes('extra_route_params', 'array')
            ->setAllowedTypes('confirm_message', 'string')
            ->setAllowedTypes('accessibility_checker', [AccessibilityCheckerInterface::class, 'callable', 'null'])
            ->setAllowedTypes('clickable_row', 'boolean')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(array $record)
    {
        $accessibilityChecker = $this->getOptions()['accessibility_checker'];

        if ($accessibilityChecker instanceof AccessibilityCheckerInterface) {
            return $accessibilityChecker->isGranted($record);
        }

        if (is_callable($accessibilityChecker)) {
            return call_user_func($accessibilityChecker, $record);
        }

        return parent::isApplicable($record);
    }
}
