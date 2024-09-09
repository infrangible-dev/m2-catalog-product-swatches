// noinspection JSUnresolvedReference

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

define([
    'jquery',
    'mage/template'
], function ($, template) {
    'use strict';

    var globalOptions = {
        index: {}
    };

    $.widget('mage.catalogProductSwatches', {
        options: globalOptions,
        attributes: {},

        _init: function initCatalogProductSwatches() {
            var self = this;
            var mutationObserver = this.getMutationObserver();
            var attributes = this.attributes;

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

        _create: function createCatalogProductSwatches() {
        },

        getMutationObserver: function getMutationObserver() {
            var self = this;
            var selectedAttributeOptionIds = this.attributes;

            return new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === "attributes") {
                        var attributeNode = $(mutation.target);

                        selectedAttributeOptionIds[attributeNode.data('attribute-id')] =
                            attributeNode.attr('data-option-selected') ?
                                parseInt(attributeNode.attr('data-option-selected'), 10) : null;

                        self.processAttributeSelection();
                    }
                });
            });
        },

        processAttributeSelection: function processAttributeSelection() {
            var index = this.options.index;
            var attributesData = this.options.attributesData;
            var selectedAttributeOptionIds = this.attributes;
            var selectedProductId = null;

            $.each(index, function(productId, attributeValues) {
                var isSelected = true;

                $.each(attributeValues, function(attributeId, optionId) {
                    isSelected = isSelected && (selectedAttributeOptionIds[attributeId] === optionId);
                });

                if (isSelected) {
                    selectedProductId = productId;
                }
            });

            $.each(attributesData, function(attributeId, attributeData) {
                var attributeSelector = attributeData.selector;
                var attributeTemplateId = attributeData.templateId;
                var attributeCode = attributeData.attributeCode;
                var attributeValue = attributeData.values[selectedProductId];

                $(attributeSelector).html(
                    template($('#' + attributeTemplateId).html(), {
                        'attributeId': attributeId,
                        'attributeCode': attributeCode,
                        'attributeCodeClass': attributeCode ? attributeCode.replace(/\W+/g, '_').toLowerCase() : null,
                        'attributeValue': attributeValue,
                        'attributeValueClass': attributeValue ? attributeValue.replace(/\W+/g, '_').toLowerCase() : null
                    })
                );
            });

            $('.column.main').trigger('swatch.changed', [selectedProductId, selectedAttributeOptionIds]);
        }
    });

    return $.mage.catalogProductSwatches;
});
