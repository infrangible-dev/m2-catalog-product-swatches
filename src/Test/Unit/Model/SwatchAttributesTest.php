<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductSwatches\Test\Unit\Model;

use FeWeDev\Base\Variables;
use Infrangible\CatalogProductSwatches\Model\SwatchAttribute;
use Infrangible\CatalogProductSwatches\Model\SwatchAttributeFactory;
use Infrangible\CatalogProductSwatches\Model\SwatchAttributes;
use Infrangible\Core\Helper\Attribute;
use Infrangible\Core\Helper\Database;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Entity\Attribute as EavAttribute;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests the values of the variants of an attribute: labels of options in the store, labels of multiselect attributes
 * joined with the separator, values of attributes without options, and that disabled variants are left out. The
 * values come from the database, not from the variants of Magento, which do not have attributes they do not need.
 */
class SwatchAttributesTest extends TestCase
{
    /** @var array<int, mixed> values of the attribute by product ID */
    private array $values = [];

    /**
     * Returns the attributes with an attribute of the frontend input and, if it has options, its options.
     *
     * @param string $frontendInput
     * @param array<int, array{value: mixed, label: mixed}>|null $options
     *
     * @return SwatchAttributes
     */
    private function createSwatchAttributes(string $frontendInput, ?array $options): SwatchAttributes
    {
        $source = $this->createStub(AbstractSource::class);
        $source->method('getAllOptions')->willReturn($options ?? []);

        $attribute = $this->createStub(EavAttribute::class);
        $attribute->method('getId')->willReturn('42');
        $attribute->method('usesSource')->willReturn($options !== null);
        $attribute->method('getSource')->willReturn($source);
        $attribute->method('getFrontendInput')->willReturn($frontendInput);

        $attributeHelper = $this->createStub(Attribute::class);
        $attributeHelper->method('getAttribute')->willReturn($attribute);
        // the values of the variants, read from the database
        $attributeHelper->method('getAttributeValues')->willReturnCallback(
            fn(mixed $dbAdapter, string $entityType, string $attributeCode, array $productIds): array =>
                array_intersect_key($this->values, array_flip($productIds))
        );

        $swatchAttributeFactory = $this->createStub(SwatchAttributeFactory::class);
        $swatchAttributeFactory->method('create')->willReturnCallback(
            fn(array $data): SwatchAttribute => new SwatchAttribute(...$data)
        );

        return new SwatchAttributes(
            $attributeHelper,
            $this->createStub(Database::class),
            new Variables(),
            $swatchAttributeFactory,
            new NullLogger()
        );
    }

    /**
     * Returns a configurable product with the variants: product ID => [value, status].
     *
     * @param array<int, array{0: mixed, 1: int}> $variants
     *
     * @return Product
     */
    private function createProduct(array $variants): Product
    {
        $childProducts = [];

        foreach ($variants as $productId => [$value, $status]) {
            $this->values[ $productId ] = $value;

            // the variants of Magento do not have the value of the attribute
            $childProduct = $this->createStub(Product::class);
            $childProduct->method('getId')->willReturn((string)$productId);
            $childProduct->method('getStatus')->willReturn((string)$status);

            $childProducts[] = $childProduct;
        }

        $typeInstance = $this->createStub(Configurable::class);
        $typeInstance->method('getUsedProducts')->willReturn($childProducts);

        $product = $this->createStub(Product::class);
        $product->method('getTypeInstance')->willReturn($typeInstance);
        $product->method('getStoreId')->willReturn('1');

        return $product;
    }

    /**
     * Tests the labels of the options, of multiselect attributes joined with the separator.
     *
     * @return void
     */
    public function testOptionLabels(): void
    {
        $swatchAttributes = $this->createSwatchAttributes(
            'multiselect',
            [
                ['value' => '', 'label' => ' '],
                ['value' => '5', 'label' => 'Red'],
                ['value' => '6', 'label' => __('Blue')]
            ]
        );

        $swatchAttributes->addAttribute(
            $this->createProduct(
                [
                    11 => ['5', Status::STATUS_ENABLED],
                    12 => ['5,6', Status::STATUS_ENABLED],
                    13 => ['6', Status::STATUS_DISABLED],
                    14 => [null, Status::STATUS_ENABLED]
                ]
            ),
            'color',
            '.color',
            'color-template',
            ' / '
        );

        $swatchAttribute = $swatchAttributes->getSwatchAttributes()[ 0 ];

        $this->assertSame(42, $swatchAttribute->getId());
        $this->assertSame('color', $swatchAttribute->getCode());
        $this->assertSame([11 => 'Red', 12 => 'Red / Blue', 14 => null], $swatchAttribute->getValues());
    }

    /**
     * Tests that values of attributes without options stay as they are.
     *
     * @return void
     */
    public function testPlainValues(): void
    {
        $swatchAttributes = $this->createSwatchAttributes('text', null);

        $swatchAttributes->addAttribute(
            $this->createProduct([11 => ['A-1', Status::STATUS_ENABLED]]),
            'sku_suffix',
            '.sku',
            'sku-template'
        );

        $this->assertSame([11 => 'A-1'], $swatchAttributes->getSwatchAttributes()[ 0 ]->getValues());
    }

    /**
     * Tests that products that are not configurable add no attribute.
     *
     * @return void
     */
    public function testSimpleProduct(): void
    {
        $swatchAttributes = $this->createSwatchAttributes('text', null);

        $product = $this->createStub(Product::class);
        $product->method('getTypeInstance')->willReturn($this->createStub(Product\Type\Simple::class));

        $swatchAttributes->addAttribute($product, 'sku_suffix', '.sku', 'sku-template');

        $this->assertSame([], $swatchAttributes->getSwatchAttributes());
    }
}
