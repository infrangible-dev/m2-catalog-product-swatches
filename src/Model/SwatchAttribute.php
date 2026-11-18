<?php

declare(strict_types=1);

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

namespace Infrangible\CatalogProductSwatches\Model;

/**
 * An attribute whose value of the selected variant is shown on the product page: the values by variant (product ID),
 * the CSS selector of the element that shows it and the ID of the template that renders it.
 */
class SwatchAttribute
{
    /**
     * @param int $id
     * @param string $code
     * @param string $selector
     * @param string $templateId
     * @param array $values
     * @phpstan-param array<int, mixed> $values
     */
    public function __construct(
        private int $id,
        private string $code,
        private string $selector,
        private string $templateId,
        private array $values = []
    ) {
    }

    /**
     * Returns the ID of the attribute.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Sets the ID of the attribute.
     *
     * @param int $id
     *
     * @return void
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Returns the code of the attribute.
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Sets the code of the attribute.
     *
     * @param string $code
     *
     * @return void
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * Returns the CSS selector of the element that shows the value.
     *
     * @return string
     */
    public function getSelector(): string
    {
        return $this->selector;
    }

    /**
     * Sets the CSS selector of the element that shows the value.
     *
     * @param string $selector
     *
     * @return void
     */
    public function setSelector(string $selector): void
    {
        $this->selector = $selector;
    }

    /**
     * Returns the ID of the element with the template (mage/template) that renders the value.
     *
     * @return string
     */
    public function getTemplateId(): string
    {
        return $this->templateId;
    }

    /**
     * Sets the ID of the element with the template (mage/template) that renders the value.
     *
     * @param string $templateId
     *
     * @return void
     */
    public function setTemplateId(string $templateId): void
    {
        $this->templateId = $templateId;
    }

    /**
     * Returns the values by product ID of the variant.
     *
     * @return array
     * @phpstan-return array<int, mixed>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * Sets the value of a variant.
     *
     * @param int $productId
     * @param mixed $attributeValue
     *
     * @return void
     */
    public function addValue(int $productId, mixed $attributeValue): void
    {
        $this->values[ $productId ] = $attributeValue;
    }
}
