<?php
/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2018.
 */

namespace Emico\Tweakwise\Block\LayeredNavigation\Navigation;

use Emico\Tweakwise\Model\Catalog\Layer\Filter;
use Emico\Tweakwise\Model\Config;
use Magento\LayeredNavigation\Block\Navigation;

class Plugin
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param Navigation $block
     * @param Filter[] $result
     *
     * @return mixed
     */
    public function afterGetFilters(Navigation $block, $result)
    {
        $block->setData('form_filters', $this->config->getUseFormFilters());
        return array_filter($result, [$this, 'shouldShowFacet']);
    }

    /**
     * @param Filter $filter
     * @return bool
     */
    protected function shouldShowFacet(Filter $filter)
    {
        if (!$this->config->getHideSingleOptions()) {
            return true;
        }

        return count($filter->getItems()) !== 1;
    }


}