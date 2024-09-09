<?php

declare(strict_types=1);

namespace Infrangible\CatalogProductSwatches\Model;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class SwatchAttribute
{
    /** @var int */
    private $id;

    /** @var string */
    private $code;

    /** @var string */
    private $selector;

    /** @var string */
    private $templateId;

    /** @var array<int, mixed> */
    private $values;

    public function __construct(int $id, string $code, string $selector, string $templateId, array $values = [])
    {
        $this->id = $id;
        $this->code = $code;
        $this->selector = $selector;
        $this->templateId = $templateId;
        $this->values = $values;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getSelector(): string
    {
        return $this->selector;
    }

    public function setSelector(string $selector): void
    {
        $this->selector = $selector;
    }

    public function getTemplateId(): string
    {
        return $this->templateId;
    }

    public function setTemplateId(string $templateId): void
    {
        $this->templateId = $templateId;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function addValue(int $productId, $attributeValue): void
    {
        $this->values[ $productId ] = $attributeValue;
    }
}
