# Infrangible Catalog Product Swatches

Base of modules that show values of the selected variant on the page of a configurable product: the page knows which
variant the selected swatches (or options) match and updates the values whenever the selection changes. The module
changes nothing in the store on its own.

## Usage

An observer of the event `catalog_product_swatch_attributes` (data: `product`, `swatch_attributes`) adds the attributes
whose values are shown:

```php
/** @var \Infrangible\CatalogProductSwatches\Model\SwatchAttributes $swatchAttributes */
$swatchAttributes = $observer->getEvent()->getData('swatch_attributes');

$swatchAttributes->addAttribute(
    $observer->getEvent()->getData('product'),
    'pos',                      // attribute code
    '.catalog-product-pos',     // CSS selector of the element that shows the value
    'catalog-product-pos-template', // ID of the element with the template (mage/template)
    ', '                        // separator of the labels of multiselect attributes
);
```

- The values are the labels of the options in the store of the product, or the plain values of attributes without
  options; only enabled variants count.
- `createAndAddSwatchAttribute()` adds an attribute with values of another source (product ID => value).
- The template gets `attributeId`, `attributeCode`, `attributeCodeClass`, `attributeValue` and `attributeValueClass`
  (code and value in lower case with `_` for other characters than letters and digits).
- When the selection changes, `.column.main` triggers the event `swatch.changed` with the ID of the selected variant
  (or null) and the selected option IDs by attribute ID.

The Hyvä theme needs
[Infrangible Catalog Product Swatches Hyvä](https://github.com/infrangible-dev/m2-catalog-product-swatches-hyva).

## Requirements

- Magento Open Source or Adobe Commerce 2.4.6 or newer
- PHP 8.2 or newer

## Installation

```bash
composer require infrangible/m2-catalog-product-swatches
bin/magento module:enable Infrangible_CatalogProductSwatches
bin/magento setup:upgrade
```

In production mode, also run `bin/magento setup:di:compile` and `bin/magento setup:static-content:deploy`.

## License

Infrangible Catalog Product Swatches is licensed under the MIT License - see the LICENSE file for details.
