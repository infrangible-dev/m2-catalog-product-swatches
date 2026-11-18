<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductSwatches\Test\Unit\Block;

use FeWeDev\Base\Json;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductSwatches\Block\Swatches;
use Infrangible\CatalogProductSwatches\Model\SwatchAttribute;
use Infrangible\CatalogProductSwatches\Model\SwatchAttributeFactory;
use Infrangible\CatalogProductSwatches\Model\SwatchAttributes;
use Infrangible\CatalogProductSwatches\Model\SwatchAttributesFactory;
use Infrangible\Core\Helper\Attribute as AttributeHelper;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Registry;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests the data of the swatches for the JavaScript of the product page.
 */
class SwatchesTest extends TestCase
{
    /**
     * Returns the block with the product of the page, and an observer that adds an attribute.
     *
     * @param Product|null $product
     *
     * @return Swatches
     */
    private function createBlock(?Product $product): Swatches
    {
        $eventManager = $this->createStub(ManagerInterface::class);
        $eventManager->method('dispatch')->willReturnCallback(
            function (string $eventName, array $data): void {
                if ($eventName === 'catalog_product_swatch_attributes') {
                    $swatchAttributes = $data[ 'swatch_attributes' ];
                    $this->assertInstanceOf(SwatchAttributes::class, $swatchAttributes);
                    $swatchAttributes->addSwatchAttribute(
                        new SwatchAttribute(42, 'pos', '.pos', 'pos-template', [11 => 'Shop </script>'])
                    );
                }
            }
        );

        $context = $this->createStub(Context::class);
        $context->method('getEventManager')->willReturn($eventManager);

        $registry = $this->createStub(Registry::class);
        $registry->method('registry')->willReturn($product);

        $swatchAttributesFactory = $this->createStub(SwatchAttributesFactory::class);
        $swatchAttributesFactory->method('create')->willReturn(
            new SwatchAttributes(
                $this->createStub(AttributeHelper::class),
                $this->createStub(Database::class),
                new Variables(),
                $this->createStub(SwatchAttributeFactory::class),
                new NullLogger()
            )
        );

        return new Swatches($context, $registry, new Variables(), new Json(), $swatchAttributesFactory);
    }

    /**
     * Returns a configurable product with the attribute color (ID 93) and an enabled and a disabled variant.
     *
     * @return Product
     */
    private function createProduct(): Product
    {
        $attribute = $this->createStub(Attribute::class);
        $attribute->method('getId')->willReturn('93');
        $attribute->method('getAttributeCode')->willReturn('color');

        $configurableAttribute = $this->createStub(Configurable\Attribute::class);
        $configurableAttribute->method('getData')->willReturnMap([['product_attribute', null, $attribute]]);

        $variants = [];

        foreach ([11 => Status::STATUS_ENABLED, 12 => Status::STATUS_DISABLED] as $productId => $status) {
            $variant = $this->createStub(Product::class);
            $variant->method('getId')->willReturn((string)$productId);
            $variant->method('getStatus')->willReturn((string)$status);
            $variant->method('getData')->willReturn('50');
            $variants[] = $variant;
        }

        $typeInstance = $this->createStub(Configurable::class);
        $typeInstance->method('getConfigurableAttributes')->willReturn([$configurableAttribute]);
        $typeInstance->method('getUsedProducts')->willReturn($variants);

        $product = $this->createStub(Product::class);
        $product->method('getTypeInstance')->willReturn($typeInstance);

        return $product;
    }

    /**
     * Tests the options of the enabled variants.
     *
     * @return void
     */
    public function testIndex(): void
    {
        $this->assertSame('{"11":{"93":50}}', $this->createBlock($this->createProduct())->getIndex());
    }

    /**
     * Tests the attributes of the observers, with the code also as attributeCode, which the scripts read; before,
     * the scripts got no code. Slashes are escaped, so values cannot end the script element.
     *
     * @return void
     */
    public function testAttributesData(): void
    {
        $this->assertSame(
            '{"42":{"code":"pos","attributeCode":"pos","selector":".pos","templateId":"pos-template",' .
            '"values":{"11":"Shop <\/script>"}}}',
            $this->createBlock($this->createProduct())->getAttributesData()
        );
    }

    /**
     * Tests a page without product; before, the block failed.
     *
     * @return void
     */
    public function testWithoutProduct(): void
    {
        $block = $this->createBlock(null);

        $this->assertSame('{}', $block->getIndex());
        $this->assertSame('{}', $block->getAttributesData());
    }
}
