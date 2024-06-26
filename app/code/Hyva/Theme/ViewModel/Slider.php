<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2020-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
 */

declare(strict_types=1);

namespace Hyva\Theme\ViewModel;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

/**
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class Slider implements ArgumentInterface
{
    /**
     * @var LayoutInterface
     */
    private $layout;

    public function __construct(LayoutInterface $layout)
    {
        $this->layout = $layout;
    }

    public function getSliderForItems(
        string $itemTemplateFile,
        iterable $items,
        string $sliderTemplateFile = 'Magento_Theme::elements/slider-php.phtml'
    ): AbstractBlock {
        // phpcs:disable Magento2.Security.InsecureFunction.FoundWithAlternative
        $id = md5(uniqid($sliderTemplateFile . $itemTemplateFile));
        // phpcs:enable

        $sliderBlock = $this->createTemplateBlock("slider.{$id}", [
            'items'    => $items,
            'template' => $sliderTemplateFile,
        ]);

        $this->addSliderItemChildBlock($sliderBlock, $id, $itemTemplateFile);

        return $sliderBlock;
    }

    private function createTemplateBlock(string $name, array $arguments = []): Template
    {
        return $this->layout->createBlock(Template::class, $name, ['data' => $arguments]);
    }

    private function addSliderItemChildBlock(Template $sliderBlock, string $id, string $itemTemplateFile): void
    {
        $sliderBlock->setChild(
            'slider.item.template',
            $this->createTemplateBlock("slider.items.{$id}", ['template' => $itemTemplateFile])
        );
    }
}
