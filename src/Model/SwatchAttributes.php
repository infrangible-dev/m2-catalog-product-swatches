<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductSwatches\Model;

use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Attribute;
use Infrangible\Core\Helper\Database;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * The attributes whose values of the selected variant are shown on the product page; observers of the event
 * catalog_product_swatch_attributes add them.
 */
class SwatchAttributes
{
    /** @var SwatchAttribute[] */
    private array $swatchAttributes = [];

    /**
     * @param Attribute $attributeHelper
     * @param Database $databaseHelper
     * @param Variables $variables
     * @param SwatchAttributeFactory $swatchAttributeFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Attribute $attributeHelper,
        protected Database $databaseHelper,
        protected Variables $variables,
        protected SwatchAttributeFactory $swatchAttributeFactory,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Returns the attributes.
     *
     * @return SwatchAttribute[]
     */
    public function getSwatchAttributes(): array
    {
        return $this->swatchAttributes;
    }

    /**
     * Adds an attribute.
     *
     * @param SwatchAttribute $swatchAttribute
     *
     * @return void
     */
    public function addSwatchAttribute(SwatchAttribute $swatchAttribute): void
    {
        $this->swatchAttributes[] = $swatchAttribute;
    }

    /**
     * Creates and adds an attribute.
     *
     * @param int $id
     * @param string $code
     * @param string $selector
     * @param string $templateId
     * @param array $values
     * @phpstan-param array<int, mixed> $values
     *
     * @return SwatchAttribute
     */
    public function createAndAddSwatchAttribute(
        int $id,
        string $code,
        string $selector,
        string $templateId,
        array $values = []
    ): SwatchAttribute {
        $swatchAttribute = $this->swatchAttributeFactory->create(
            ['id' => $id, 'code' => $code, 'selector' => $selector, 'templateId' => $templateId, 'values' => $values]
        );

        $this->addSwatchAttribute($swatchAttribute);

        return $swatchAttribute;
    }

    /**
     * Adds the product attribute with the values of the enabled variants of the configurable product.
     *
     * The values are read for the variants in the store of the product: Magento keeps the variants of the first
     * request on the product, without attributes that it does not need itself. Values of attributes with options are
     * their labels, the labels of a multiselect attribute joined with the separator. Errors are logged, the attribute
     * is then left out.
     *
     * @param Product $product
     * @param string $attributeCode
     * @param string $selector CSS selector of the element that shows the value
     * @param string $templateId ID of the element with the template that renders the value
     * @param string $multiAttributeValueSeparator
     *
     * @return void
     */
    public function addAttribute(
        Product $product,
        string $attributeCode,
        string $selector,
        string $templateId,
        string $multiAttributeValueSeparator = ', '
    ): void {
        $typeInstance = $product->getTypeInstance();

        if (! $typeInstance instanceof Configurable) {
            return;
        }

        $productIds = [];

        foreach ($typeInstance->getUsedProducts($product) as $childProduct) {
            if ($childProduct instanceof Product &&
                $this->variables->intValue($childProduct->getStatus()) === Status::STATUS_ENABLED) {
                $productIds[] = $this->variables->intValue($childProduct->getId());
            }
        }

        $storeId = $this->variables->intValue($product->getStoreId());

        try {
            $attribute = $this->attributeHelper->getAttribute(Product::ENTITY, $attributeCode);
            $optionLabels = $this->getOptionLabels($attribute, $storeId);
            $values = count($productIds) === 0 ? [] : $this->attributeHelper->getAttributeValues(
                $this->databaseHelper->getDefaultConnection(),
                Product::ENTITY,
                $attributeCode,
                $productIds,
                $storeId,
                false
            );
        } catch (LocalizedException $exception) {
            $this->logger->error($exception);

            return;
        }

        $swatchAttribute = $this->createAndAddSwatchAttribute(
            $this->variables->intValue($attribute->getId()),
            $attributeCode,
            $selector,
            $templateId
        );

        foreach ($productIds as $productId) {
            $attributeValue = $values[ $productId ] ?? null;

            if ($optionLabels !== null && is_scalar($attributeValue) && (string)$attributeValue !== '') {
                $attributeValue = $this->getLabel(
                    (string)$attributeValue,
                    $optionLabels,
                    $attribute->getFrontendInput() === 'multiselect',
                    $multiAttributeValueSeparator
                );
            }

            $swatchAttribute->addValue($productId, $attributeValue);
        }
    }

    /**
     * Returns the labels of the options in the store by option ID, or null if the attribute has no options.
     *
     * @param AbstractAttribute $attribute
     * @param int $storeId
     *
     * @return array<string, string>|null
     * @throws LocalizedException
     */
    protected function getOptionLabels(AbstractAttribute $attribute, int $storeId): ?array
    {
        if (! $attribute->usesSource()) {
            return null;
        }

        $attribute->setData('store_id', $storeId);

        $source = $attribute->getSource();
        $source->setAttribute($attribute);

        $labels = [];

        foreach ($source->getAllOptions() as $option) {
            if (is_array($option) && array_key_exists('value', $option) && array_key_exists('label', $option)) {
                $value = $this->variables->stringValueOrDefault($option[ 'value' ]);

                if ($value !== '') {
                    $labels[ strtolower($value) ] = $this->variables->stringValueOrDefault($option[ 'label' ]);
                }
            }
        }

        return $labels;
    }

    /**
     * Returns the label of the value, the labels of the values of a multiselect attribute joined with the separator.
     *
     * @param string $attributeValue
     * @param array $optionLabels
     * @phpstan-param array<string, string> $optionLabels
     * @param bool $isMultiselect
     * @param string $separator
     *
     * @return string
     */
    protected function getLabel(
        string $attributeValue,
        array $optionLabels,
        bool $isMultiselect,
        string $separator
    ): string {
        $labels = [];

        foreach ($isMultiselect ? explode(',', $attributeValue) : [$attributeValue] as $value) {
            $label = $optionLabels[ strtolower(trim($value)) ] ?? null;

            if ($label !== null) {
                $labels[] = $label;
            }
        }

        return implode($separator, $labels);
    }
}
