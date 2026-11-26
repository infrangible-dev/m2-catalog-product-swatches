// noinspection JSUnresolvedReference

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

define([
    'jquery',
    'mage/template'
], function ($, template) {
    'use strict';

    const globalOptions = {
        index: {}
    };

    $.widget('mage.catalogProductSwatches', {
        options: globalOptions,
        attributes: {},

        _init: function () {
            const self = this,
                mutationObserver = this.getMutationObserver(),
                attributes = this.attributes;

            $(this.element).on('swatch.initialized', function () {
                $(this).find('.swatch-attribute[data-attribute-code][data-attribute-id]').each(function () {
                    mutationObserver.observe(this, {
                        attributes: true
                    });

                    attributes[$(this).data('attribute-id')] = $(this).data('option-selected');
                });

                self.processAttributeSelection();
            });
        },

        _create: function () {
        },

        getMutationObserver: function () {
            const self = this,
                selectedAttributeOptionIds = this.attributes;

            return new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.type === 'attributes') {
                        const attributeNode = $(mutation.target);

                        selectedAttributeOptionIds[attributeNode.data('attribute-id')] =
                            attributeNode.attr('data-option-selected') ?
                                parseInt(attributeNode.attr('data-option-selected'), 10) : null;

                        self.processAttributeSelection();
                    }
                });
            });
        },

        processAttributeSelection: function () {
            const index = this.options.index,
                attributesData = this.options.attributesData,
                selectedAttributeOptionIds = this.attributes;
            let selectedProductId = null;

            $.each(index, function (productId, attributeValues) {
                let isSelected = true;

                $.each(attributeValues, function (attributeId, optionId) {
                    isSelected = isSelected && selectedAttributeOptionIds[attributeId] === optionId;
                });

                if (isSelected) {
                    selectedProductId = productId;
                }
            });

            $.each(attributesData, function (attributeId, attributeData) {
                const attributeSelector = attributeData.selector,
                    attributeTemplateId = attributeData.templateId,
                    attributeCode = attributeData.attributeCode,
                    attributeValue = attributeData.values[selectedProductId];

                $(attributeSelector).html(
                    template($('#' + attributeTemplateId).html(), {
                        'attributeId': attributeId,
                        'attributeCode': attributeCode,
                        'attributeCodeClass': attributeCode ? attributeCode.replace(/\W+/g, '_').toLowerCase() : null,
                        'attributeValue': attributeValue,
                        'attributeValueClass': attributeValue ?
                            String(attributeValue).replace(/\W+/g, '_').toLowerCase() : null
                    })
                );
            });

            $('.column.main').trigger('swatch.changed', [selectedProductId, selectedAttributeOptionIds]);
        }
    });

    return $.mage.catalogProductSwatches;
});
