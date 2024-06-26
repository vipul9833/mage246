<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2020-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
 */

declare(strict_types=1);

namespace Hyva\Theme\ViewModel;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Pricing\Render;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\LayoutInterface;

class ProductListItem implements ArgumentInterface
{
    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var ProductPage
     */
    private $productViewModel;

    /**
     * @var CurrentCategory
     */
    private $currentCategory;

    /**
     * @var BlockCache
     */
    private $blockCache;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    public function __construct(
        LayoutInterface $layout,
        ProductPage $productViewModel,
        CurrentCategory $currentCategory,
        BlockCache $blockCache,
        CustomerSession $customerSession
    ) {
        $this->layout           = $layout;
        $this->productViewModel = $productViewModel;
        $this->currentCategory  = $currentCategory;
        $this->blockCache       = $blockCache;
        $this->customerSession  = $customerSession;
    }

    public function getProductPriceHtml(
        Product $product
    ) {
        $priceType = FinalPrice::PRICE_CODE;

        $arguments = [
            'include_container'     => true,
            'display_minimal_price' => true,
            'list_category_page'    => true,
            'zone'                  => Render::ZONE_ITEM_LIST,
        ];

        return $this->getPriceRendererBlock()->render($priceType, $product, $arguments);
    }

    /**
     * @return Render
     */
    private function getPriceRendererBlock()
    {
        /** @var Render $priceRender */
        $priceRender = $this->layout->getBlock('product.price.render.default');
        return $priceRender ?: $this->layout->createBlock(
            Render::class,
            'product.price.render.default',
            ['data' => ['price_render_handle' => 'catalog_product_prices']]
        );
    }

    private function getCurrencyCode(): string
    {
        return $this->productViewModel->getCurrencyData()['code'];
    }

    private function isCategoryInProductUrl(): bool
    {
        return $this->productViewModel->isProductUrlIncludesCategory() && $this->currentCategory->exists();
    }

    public function getItemCacheKeyInfo(
        Product $product,
        AbstractBlock $block,
        string $viewMode,
        string $templateType
    ): array {
        return [
            $product->getId(),
            $viewMode,
            $templateType,
            $block->getTemplate(),
            $product->getStoreId(),
            $this->getCurrencyCode(),
            (int) $block->getData('hideDetails'),
            (int) $block->getData('hide_rating_summary'),
            $this->isCategoryInProductUrl()
                ? $this->currentCategory->get()->getId()
                : '0',
            (int) $this->customerSession->getCustomerGroupId(),
            (string) $block->getData('image_display_area'),
            json_encode($product->getData('image_custom_attributes') ?? []),
        ];
    }

    public function getItemHtmlWithRenderer(
        AbstractBlock $itemRendererBlock,
        Product $product,
        AbstractBlock $parentBlock,
        string $viewMode,
        string $templateType,
        string $imageDisplayArea,
        bool $showDescription
    ): string {
        return $this->withParentChildLayoutRelationshipExecute($parentBlock, $itemRendererBlock, [$this, 'renderItemHtml'], func_get_args());
    }

    private function renderItemHtml(
        AbstractBlock $itemRendererBlock,
        Product $product,
        AbstractBlock $parentBlock,
        string $viewMode,
        string $templateType,
        string $imageDisplayArea,
        bool $showDescription
    ): string {
        // Careful! Temporal coupling!
        // First the values on the block need to be set, then the cache key info array can be created.

        $itemRendererBlock->setData('product', $product)
                          ->setData('view_mode', $viewMode)
                          ->setData('item_relation_type', $parentBlock->getData('item_relation_type'))
                          ->setData('image_display_area', $imageDisplayArea)
                          ->setData('show_description', $showDescription)
                          ->setData('position', $parentBlock->getPositioned())
                          ->setData('pos', $parentBlock->getPositioned())
                          ->setData('template_type', $templateType)
                          ->setData('cache_lifetime', 3600)
                          ->setData('cache_tags', $product->getIdentities())
                          ->setData('hideDetails', $parentBlock->getData('hideDetails'))
                          ->setData('hide_rating_summary', $parentBlock->getData('hide_rating_summary'));

        $itemCacheKeyInfo = $this->getItemCacheKeyInfo($product, $itemRendererBlock, $viewMode, $templateType);
        $itemRendererBlock->setData('cache_key', $this->blockCache->hashCacheKeyInfo($itemCacheKeyInfo));

        foreach (($itemRendererBlock->getData('additional_item_renderer_processors') ?? []) as $processor) {
            if (method_exists($processor, 'beforeListItemToHtml')) {
                //phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                call_user_func([$processor, 'beforeListItemToHtml'], $itemRendererBlock, $product);
            }
        }

        return $itemRendererBlock->toHtml();
    }

    /**
     * Temporarily set the parent - child relationship in the layout structure and execute the given callback
     *
     * @param AbstractBlock $newParent
     * @param AbstractBlock $child
     * @param callable $fn
     * @param mixed[] $args
     * @return mixed
     */
    private function withParentChildLayoutRelationshipExecute(AbstractBlock $newParent, AbstractBlock $child, callable $fn, array $args = [])
    {
        $childName = $child->getNameInLayout();
        $origParentBlock = $child->getParentBlock();
        $origAlias = $this->layout->getElementAlias($childName);
        $this->layout->setChild($newParent->getNameInLayout(), $childName, $childName);

        //phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $result = call_user_func_array($fn, $args);

        if ($origParentBlock) {
            $this->layout->setChild($origParentBlock->getNameInLayout(), $childName, $origAlias ?: $childName);
        }

        return $result;
    }

    public function getItemHtml(
        Product $product,
        AbstractBlock $parentBlock,
        string $viewMode,
        string $templateType,
        string $imageDisplayArea,
        bool $showDescription
    ): string {
        /** @var AbstractBlock $itemRendererBlock */
        $itemRendererBlock = $this->layout->getBlock('product_list_item');

        if (! $itemRendererBlock) {
            return '';
        }

        return $this->getItemHtmlWithRenderer(
            $itemRendererBlock,
            $product,
            $parentBlock,
            $viewMode,
            $templateType,
            $imageDisplayArea,
            $showDescription
        );
    }
}
