<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2019 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\Tweakwise\Model\Catalog\Layer\Url\Strategy;

use Emico\Tweakwise\Model\Catalog\Layer\Filter\Item;
use Emico\Tweakwise\Model\Catalog\Layer\Url\CategoryUrlInterface;
use Emico\Tweakwise\Model\Catalog\Layer\Url\FilterApplierInterface;
use Emico\Tweakwise\Model\Catalog\Layer\Url\UrlInterface;
use Emico\Tweakwise\Model\Client\Request\ProductNavigationRequest;
use Emico\Tweakwise\Model\Catalog\Layer\Url\UrlModel;
use Emico\Tweakwise\Model\Client\Request\ProductSearchRequest;
use Magento\Framework\App\Request\Http as MagentoHttpRequest;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Emico\TweakwiseExport\Model\Helper as ExportHelper;
use Magento\Catalog\Model\Layer\Resolver;


class QueryParameterStrategy implements UrlInterface, FilterApplierInterface, CategoryUrlInterface
{
    use ItemCategoryResolverTrait;

    /**
     * Separator used in category tree urls
     */
    const CATEGORY_TREE_SEPARATOR = '-';

    /**
     * Extra ignored page parameters
     */
    const PARAM_MODE = 'product_list_mode';
    const PARAM_CATEGORY = 'categorie';

    /**
     * Commonly used query parameters from headers
     */
    const PARAM_LIMIT = 'product_list_limit';
    const PARAM_ORDER = 'product_list_order';
    const PARAM_PAGE = 'p';
    const PARAM_SEARCH = 'q';

    /**
     * Parameters to be ignored as attribute filters
     *
     * @var string[]
     */
    protected $ignoredQueryParameters = [
        self::PARAM_CATEGORY,
        self::PARAM_ORDER,
        self::PARAM_LIMIT,
        self::PARAM_MODE,
        self::PARAM_SEARCH,
    ];

    /**
     * @var UrlModel
     */
    private $url;

    /**
     * @var Resolver
     */
    private $layerResolver;

    /**
     * Magento constructor.
     *
     * @param UrlModel $url
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ExportHelper $exportHelper
     * @param Resolver $layerResolver
     */
    public function __construct(
        UrlModel $url,
        CategoryRepositoryInterface $categoryRepository,
        ExportHelper $exportHelper,
        Resolver $layerResolver)
    {
        $this->url = $url;
        $this->categoryRepository = $categoryRepository;
        $this->exportHelper = $exportHelper;
        $this->layerResolver = $layerResolver;
    }

    /**
     * Fetch clear all items from url
     *
     * @param MagentoHttpRequest $request
     * @param Item[] $activeFilterItems
     * @return string
     */
    public function getClearUrl(
        MagentoHttpRequest $request,
        array $activeFilterItems
    ): string {
        $query = [];
        foreach ($activeFilterItems as $item) {
            $filter = $item->getFilter();

            $urlKey = $filter->getUrlKey();
            $query[$urlKey] = $filter->getCleanValue();
        }

        return $this->getCurrentQueryUrl($query);
    }

    /**
     * @param array $query
     * @return string
     */
    protected function getCurrentQueryUrl(array $query)
    {
        $params['_current'] = true;
        $params['_use_rewrite'] = true;
        $params['_query'] = $query;
        $params['_escape'] = false;
        return $this->url->getUrl('*/*/*', $params);
    }

    /**
     * Fetch current selected values
     *
     * @param MagentoHttpRequest $request
     * @param Item $item
     * @return string[]|string|null
     */
    protected function getRequestValues(MagentoHttpRequest $request, Item $item)
    {
        $filter = $item->getFilter();
        $settings = $filter
            ->getFacet()
            ->getFacetSettings();

        $urlKey = $filter->getUrlKey();

        $data = $request->getQuery($urlKey);
        if (!$data) {
            if ($settings->getIsMultipleSelect()) {
                return [];
            } else {
                return null;
            }
        }

        if ($settings->getIsMultipleSelect()) {
            if (!is_array($data)) {
                $data = [$data];
            }
            return array_map('strval', $data);
        }

        return (string) $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getCategoryFilterSelectUrl(
        MagentoHttpRequest $request,
        Item $item
    ): string {
        return $this->getCategoryFromItem($item)->getUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function getCategoryFilterRemoveUrl(
        MagentoHttpRequest $request,
        Item $item
    ): string {
        /** @var \Magento\Catalog\Model\Category $category */
        $category = $this->getCategoryFromItem($item);
        /** @var \Magento\Catalog\Model\Category $parentCategory */
        $parentCategory = $category->getParentCategory();
        if (!$parentCategory || !$parentCategory->getId() || \in_array($parentCategory->getId(), [1,2], false)) {
            return $category->getUrl();
        }
        return $parentCategory->getUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeSelectUrl(MagentoHttpRequest $request, Item $item): string
    {
        $settings = $item
            ->getFilter()
            ->getFacet()
            ->getFacetSettings();
        $attribute = $item->getAttribute();

        $urlKey = $settings->getUrlKey();
        $value = $attribute->getTitle();

        $values = $this->getRequestValues($request, $item);

        if ($settings->getIsMultipleSelect()) {
            $values[] = $value;
            $values = array_unique($values);

            $query = [$urlKey => $values];
        } else {
            $query = [$urlKey => $value];
        }

        return $this->getCurrentQueryUrl($query);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeRemoveUrl(MagentoHttpRequest $request, Item $item): string
    {
        $filter = $item->getFilter();
        $settings = $filter->getFacet()->getFacetSettings();

        $urlKey = $settings->getUrlKey();

        if ($settings->getIsMultipleSelect()) {
            $attribute = $item->getAttribute();
            $value = $attribute->getTitle();
            $values = $this->getRequestValues($request, $item);

            $index = array_search($value, $values, false);
            if ($index !== false) {
                unset($values[$index]);
            }

            $query = [$urlKey => $values];
        } else {
            $query = [$urlKey => $filter->getCleanValue()];
        }

        return $this->getCurrentQueryUrl($query);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCategoryFilters()
    {
        $currentCategory = $this->layerResolver->get()->getCurrentCategory();
        $currentCategoryId = (int)$currentCategory->getId();
        $parentCategoryId = (int)$currentCategory->getParentCategory()->getId();
        if (!$currentCategoryId || $currentCategoryId === 1 || !$parentCategoryId) {
            return [];
        }

        $rootCategoryId = (int)$currentCategory->getStore()->getRootCategoryId();
        if (\in_array($parentCategoryId,  [1, $rootCategoryId], true)) {
            return [];
        }

        return [
            $parentCategoryId,
            $currentCategoryId
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getAttributeFilters(MagentoHttpRequest $request)
    {
        $result = [];
        foreach ($request->getQuery() as $attribute => $value) {
            if (in_array(mb_strtolower($attribute), $this->ignoredQueryParameters, false)) {
                continue;
            }

            $result[$attribute] = $value;
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getSliderUrl(MagentoHttpRequest $request, Item $item): string
    {
        $query = [$item->getFilter()->getUrlKey() => '{{from}}-{{to}}'];

        return $this->getCurrentQueryUrl($query);
    }

    /**
     * {@inheritdoc}
     */
    public function apply(MagentoHttpRequest $request, ProductNavigationRequest $navigationRequest): FilterApplierInterface
    {
        $attributeFilters = $this->getAttributeFilters($request);
        foreach ($attributeFilters as $attribute => $values) {
            if (!is_array($values)) {
                $values = [$values];
            }

            foreach ($values as $value) {
                $navigationRequest->addAttributeFilter($attribute, $value);
            }
        }

        $sortOrder = $this->getSortOrder($request);
        if ($sortOrder) {
            $navigationRequest->setOrder($sortOrder);
        }

        $page = $this->getPage($request);
        if ($page) {
            $navigationRequest->setPage($page);
        }

        $limit = $this->getLimit($request);
        if ($limit) {
            $navigationRequest->setLimit($limit);
        }

        $isSearchRequest = $navigationRequest instanceof ProductSearchRequest;
        // Do not check for category paths in case of search request.
        // This will throw an exception on layer resolver.
        if (!$isSearchRequest) {
            $categories = $this->getCategoryFilters();

            if ($categories) {
                $navigationRequest->addCategoryPathFilter($categories);
            }
        }

        $search = $this->getSearch($request);
        if ($search && $isSearchRequest) {
            /** @var ProductSearchRequest $navigationRequest */
            $navigationRequest->setSearch($search);
        }
        return $this;
    }

    /**
     * @param MagentoHttpRequest $request
     * @return string|null
     */
    protected function getSortOrder(MagentoHttpRequest $request)
    {
        return $request->getQuery(self::PARAM_ORDER);
    }

    /**
     * @param MagentoHttpRequest $request
     * @return int|null
     */
    protected function getPage(MagentoHttpRequest $request)
    {
        return $request->getQuery(self::PARAM_PAGE);
    }

    /**
     * @param MagentoHttpRequest $request
     * @return int|null
     */
    protected function getLimit(MagentoHttpRequest $request)
    {
        return $request->getQuery(self::PARAM_LIMIT);
    }

    /**
     * @param MagentoHttpRequest $request
     * @return string|null
     */
    protected function getSearch(MagentoHttpRequest $request)
    {
        return $request->getQuery(self::PARAM_SEARCH);
    }
}