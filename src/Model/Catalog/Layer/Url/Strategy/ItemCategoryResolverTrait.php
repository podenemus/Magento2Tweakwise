<?php
/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2019.
 */

namespace Emico\Tweakwise\Model\Catalog\Layer\Url\Strategy;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Emico\TweakwiseExport\Model\Helper as ExportHelper;
use Emico\Tweakwise\Model\Catalog\Layer\Filter\Item;
use Magento\Catalog\Api\Data\CategoryInterface;

/**
 * Used to extract a category from filter item
 *
 * Trait ItemCategoryResolverTrait
 *
 * @package Emico\Tweakwise\Model\Catalog\Layer\Url\Strategy
 */
trait ItemCategoryResolverTrait
{

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var ExportHelper
     */
    private $exportHelper;

    /**
     * @param Item $item
     * @return CategoryInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCategoryFromItem(Item $item): CategoryInterface
    {
        $tweakwiseCategoryId = $item->getAttribute()->getAttributeId();
        $categoryId = $this->exportHelper->getStoreId($tweakwiseCategoryId);

        return $this->categoryRepository->get($categoryId);
    }
}