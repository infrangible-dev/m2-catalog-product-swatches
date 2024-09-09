<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductSwatches\Block;

use Exception;
use FeWeDev\Base\Json;
use FeWeDev\Base\Variables;
use Infrangible\CatalogProductSwatches\Model\SwatchAttributesFactory;
use Infrangible\Core\Helper\Registry;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\View\Element\Template;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Swatches extends Template
{
    /** @var Registry */
    protected $registryHelper;

    /** @var Variables */
    protected $variables;

    /** @var Json */
    protected $json;

    /** @var SwatchAttributesFactory */
    protected $swatchAttributesFactory;

    /** @var Product|null */
    private $product = null;

    public function __construct(
        Template\Context $context,
        Registry $registryHelper,
        Variables $variables,
        Json $json,
        SwatchAttributesFactory $swatchAttributesFactory,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );

        $this->registryHelper = $registryHelper;
        $this->variables = $variables;
        $this->json = $json;
        $this->swatchAttributesFactory = $swatchAttributesFactory;
    }

    public function getProduct(): ?Product
    {
        if ($this->product === null) {
            $this->product = $this->registryHelper->registry('product');
        }

        return $this->product;
    }

    public function getIndex(): string
    {
        $index = [];

        $product = $this->getProduct();

        $typeInstance = $product->getTypeInstance();

        if ($typeInstance instanceof Configurable) {
            $allowAttributes = $typeInstance->getConfigurableAttributes($product);
            $allProducts = $typeInstance->getUsedProducts($product);

            /** @var Product $childProduct */
            foreach ($allProducts as $childProduct) {
                if ((int)$childProduct->getStatus() === Status::STATUS_ENABLED) {
                    $productId = $childProduct->getId();

                    foreach ($allowAttributes as $attribute) {
                        $productAttribute = $attribute->getProductAttribute();
                        $productAttributeId = $productAttribute->getId();
                        $attributeValue = $childProduct->getData($productAttribute->getAttributeCode());

                        try {
                            $index[ $productId ][ $productAttributeId ] = $this->variables->intValue($attributeValue);
                        } catch (Exception $exception) {
                        }
                    }
                }
            }
        }

        return $this->json->encode($index);
    }

    public function getAttributesData(): string
    {
        $swatchAttributes = $this->swatchAttributesFactory->create();

        $this->_eventManager->dispatch(
            'catalog_product_swatch_attributes',
            ['product' => $this->getProduct(), 'swatch_attributes' => $swatchAttributes]
        );

        $attributesData = [];

        foreach ($swatchAttributes->getSwatchAttributes() as $swatchAttribute) {
            $attributeData = [
                'code'       => $swatchAttribute->getCode(),
                'selector'   => $swatchAttribute->getSelector(),
                'templateId' => $swatchAttribute->getTemplateId(),
                'values'     => []
            ];

            foreach ($swatchAttribute->getValues() as $productId => $attributeValue) {
                $attributeData[ 'values' ][ $productId ] = $attributeValue;
            }

            $attributesData[ $swatchAttribute->getId() ] = $attributeData;
        }

        return $this->json->encode($attributesData);
    }
}
