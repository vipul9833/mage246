<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes 2020-present. All rights reserved.
 * This product is licensed per Magento install
 * See https://hyva.io/license
 */

declare(strict_types=1);

namespace Hyva\Theme\Plugin\PageCache;

use Hyva\Theme\Model\HtmlBlockCacheTagsStorage;
use Magento\Framework\View\LayoutInterface;

use function array_map as map;

class SaveTagsForCachedBlocks
{
    /**
     * @var HtmlBlockCacheTagsStorage
     */
    private $blockCacheTagsStorage;

    public function __construct(HtmlBlockCacheTagsStorage $blockCacheTagsStorage)
    {
        $this->blockCacheTagsStorage = $blockCacheTagsStorage;
    }

    public function afterGetOutput(LayoutInterface $subject, $result)
    {
        map([$this->blockCacheTagsStorage, 'save'], $subject->getAllBlocks());

        return $result;
    }
}
