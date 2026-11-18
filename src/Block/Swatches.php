<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductSwatches\Block;

use FeWeDev\Base\Json;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductSwatches\Model\SwatchAttributesFactory;
use Infrangible\Core\Helper\Registry;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\View\Element\Template;

/**
 * The data of the swatches of a configurable product for the JavaScript of the product page: the options of the
 * configurable attributes of every variant (index) and the values of the attributes that are shown for the selected
 * variant (attributes data, added by observers of the event catalog_product_swatch_attributes).
 */
class Swatches extends Template
{
    /** @var Product|null */
    private ?Product $product = null;

    /**
     * @param Template\Context $context
     * @param Registry $registryHelper
     * @param Variables $variables
     * @param Json $json
     * @param SwatchAttributesFactory $swatchAttributesFactory
     * @param array $data
     * @phpstan-param array<string, mixed> $data
     */
    public function __construct(
        Template\Context $context,
        protected Registry $registryHelper,
        protected Variables $variables,
        protected Json $json,
        protected SwatchAttributesFactory $swatchAttributesFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Returns the product of the page.
     *
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        if ($this->product === null) {
            $product = $this->registryHelper->registry('product');

            $this->product = $product instanceof Product ? $product : null;
        }

        return $this->product;
    }

    /**
     * Returns the option IDs of the configurable attributes of the enabled variants as JSON.
     *
     * Product ID => attribute ID => option ID.
     *
     * @return string
     */
    public function getIndex(): string
    {
        $index = [];

        $product = $this->getProduct();
        $typeInstance = $product?->getTypeInstance();

        if ($product !== null && $typeInstance instanceof Configurable) {
            $attributes = $typeInstance->getConfigurableAttributes($product);

            foreach ($typeInstance->getUsedProducts($product) as $childProduct) {
                if (! $childProduct instanceof Product ||
                    $this->variables->intValue($childProduct->getStatus()) !== Status::STATUS_ENABLED) {
                    continue;
                }

                $productId = $this->variables->intValue($childProduct->getId());

                foreach ($attributes as $attribute) {
                    $productAttribute = $attribute->getData('product_attribute');

                    if (! $productAttribute instanceof AbstractAttribute) {
                        continue;
                    }

                    $optionId = $childProduct->getData($productAttribute->getAttributeCode());

                    // a variant always has an option of every configurable attribute
                    if (is_numeric($optionId)) {
                        $index[ $productId ][ $this->variables->intValue($productAttribute->getId()) ] =
                            $this->variables->intValue($optionId);
                    }
                }
            }
        }

        return $this->encode($index);
    }

    /**
     * Returns the attributes that are shown for the selected variant as JSON.
     *
     * Attribute ID => code, selector, ID of the template and the values by product ID.
     *
     * @return string
     */
    public function getAttributesData(): string
    {
        $product = $this->getProduct();

        if ($product === null) {
            return $this->encode([]);
        }

        $swatchAttributes = $this->swatchAttributesFactory->create();

        $this->_eventManager->dispatch(
            'catalog_product_swatch_attributes',
            ['product' => $product, 'swatch_attributes' => $swatchAttributes]
        );

        $attributesData = [];

        foreach ($swatchAttributes->getSwatchAttributes() as $swatchAttribute) {
            $attributesData[ $swatchAttribute->getId() ] = [
                'code'          => $swatchAttribute->getCode(),
                // the scripts of the product page read the code from attributeCode
                'attributeCode' => $swatchAttribute->getCode(),
                'selector'      => $swatchAttribute->getSelector(),
                'templateId'    => $swatchAttribute->getTemplateId(),
                'values'        => $swatchAttribute->getValues()
            ];
        }

        return $this->encode($attributesData);
    }

    /**
     * Returns the data as JSON object, also if it is empty; slashes are escaped, so the JSON can be part of a script.
     *
     * @param array $data
     * @phpstan-param array<int|string, mixed> $data
     *
     * @return string
     */
    private function encode(array $data): string
    {
        return count($data) === 0 ? '{}' : ($this->json->encode($data) ?? '{}');
    }
}
