/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

define([
    'jquery',
    'Magento_Catalog/js/product/list/toolbar'
], function ($, productListToolbarForm) {
    $.widget('tweakwise.productListToolbarForm', productListToolbarForm, {

        options: {
            ajaxFilters: false,
            pagerItemSelector: '.pages li.item',
            filterFormSelector: '#facet-filter-form'
        },

        /** @inheritdoc */
        _create: function () {
            var options = this.options;
            var element = this.element;
            this._bind(element.find(options.modeControl), options.mode, options.modeDefault);
            this._bind(element.find(options.directionControl), options.direction, options.directionDefault);
            this._bind(element.find(options.orderControl), options.order, options.orderDefault);
            this._bind(element.find(options.limitControl), options.limit, options.limitDefault);
            if (options.ajaxFilters) {
                $(element).on('click', options.pagerItemSelector, this.handlePagerClick.bind(this));
            }
        },

        handlePagerClick: function (event) {
            event.preventDefault();
            var anchor = $(event.target).closest('a');
            var page = anchor.attr('href') || '';
            var pageValueRegex = '[?&]p=(\\\d?)';
            var pageValue = new RegExp(pageValueRegex).exec(page);
            if (pageValue) {
                pageValue = pageValue[1];
                this.changeUrl('p', pageValue, pageValue)
            }

            return false;
        },

        /**
         * @param {String} paramName
         * @param {*} paramValue
         * @param {*} defaultValue
         */
        changeUrl: function (paramName, paramValue, defaultValue) {
            if (!this.options.ajaxFilters) {
                return this._super(paramName, paramValue, defaultValue);
            }

            var form = $(this.options.filterFormSelector);
            var input = form.find('input[name=' + paramName + ']');
            if (!input.length) {
                input = document.createElement('input');
                input.name = paramName;
                input = $(input);
                form.append(input);
                input.hide();
            }

            input.attr('value', paramValue);
            form.trigger('change');
        }

    });

    return $.tweakwise.productListToolbarForm;
});
