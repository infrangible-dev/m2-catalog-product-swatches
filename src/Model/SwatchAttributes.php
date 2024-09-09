<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductSwatches\Model;

use Exception;
use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Attribute;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class SwatchAttributes
{
    /** @var Attribute */
    protected $attributeHelper;

    /** @var Variables */
    protected $variables;

    /** @var SwatchAttributeFactory */
    protected $swatchAttributeFactory;

    /** @var LoggerInterface */
    protected $logger;

    /** @var SwatchAttribute[] */
    private $swatchAttributes = [];

    public function __construct(
        Attribute $attributeHelper,
        Variables $variables,
        SwatchAttributeFactory $swatchAttributeFactory,
        LoggerInterface $logger
    ) {
        $this->attributeHelper = $attributeHelper;
        $this->variables = $variables;
        $this->swatchAttributeFactory = $swatchAttributeFactory;
        $this->logger = $logger;
    }

    /**
     * @return SwatchAttribute[]
     */
    public function getSwatchAttributes(): array
    {
        return $this->swatchAttributes;
    }

    public function addSwatchAttribute(SwatchAttribute $swatchAttribute): void
    {
        $this->swatchAttributes[] = $swatchAttribute;
    }

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

        $this->swatchAttributes[] = $swatchAttribute;

        return $swatchAttribute;
    }

    public function addAttribute(
        Product $product,
        string $attributeCode,
        string $selector,
        string $templateId
    ): void {
        try {
            $attribute = $this->attributeHelper->getAttribute(
                Product::ENTITY,
                $attributeCode
            );

            $attributeId = $this->variables->intValue($attribute->getId());

            $swatchAttribute = $this->createAndAddSwatchAttribute(
                $attributeId,
                $attributeCode,
                $selector,
                $templateId
            );

            $storeId = $this->variables->intValue($product->getStoreId());

            $typeInstance = $product->getTypeInstance();

            if ($typeInstance instanceof Configurable) {
                $allProducts = $typeInstance->getUsedProducts($product, [$attributeCode]);

                /** @var Product $childProduct */
                foreach ($allProducts as $childProduct) {
                    if ((int)$childProduct->getStatus() === Status::STATUS_ENABLED) {
                        $productId = $this->variables->intValue($childProduct->getId());

                        $attributeValue = $childProduct->getData($attributeCode);

                        if ($attribute->usesSource()) {
                            $source = $attribute->getSource();

                            $source->setAttribute($attribute);

                            $attribute->setData(
                                'store_id',
                                $storeId
                            );

                            $optionValue = null;

                            foreach ($source->getAllOptions() as $option) {
                                if (array_key_exists(
                                        'label',
                                        $option
                                    ) && array_key_exists(
                                        'value',
                                        $option
                                    )) {

                                    if (strcasecmp(
                                            strval($option[ 'value' ]),
                                            strval($attributeValue)
                                        ) === 0) {

                                        $optionValue = $option[ 'label' ];
                                        break;
                                    }
                                }
                            }

                            $attributeValue = $optionValue;
                        }

                        $swatchAttribute->addValue(
                            $productId,
                            $attributeValue
                        );
                    }
                }
            }
        } catch (Exception $exception) {
            $this->logger->error($exception);
        }
    }
}
