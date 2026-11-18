# Changelog

## 2.0.0

### Breaking changes

- Requires Magento 2.4.6 or newer and PHP 8.2 or newer. Older versions of Magento and PHP 7.1 to 8.1 are no longer
  supported; use version 1.x for these.
- Requires `infrangible/m2-core` 5.0 and `infrangible/m2-foundation` 3.0, and `fewedev/base` 2.4; before, the module
  used m2-core without requiring it.
- All classes have native types, and the constructors use constructor property promotion.

### Fixed

- Values of attributes that are not configurable attributes are shown; before, they were always empty: the attribute
  code was passed to Magento where it expects attribute IDs, and Magento keeps the variants of its first request
  without further attributes anyway. The values are now read from the database.
- The templates of the shown values get the attribute code (`attributeCode`, `attributeCodeClass`); before, the
  scripts read it from a key the data did not have, also in the Hyvä module.
- Values that are numbers no longer stop the script of the product page.
- A product page without product no longer fails.

### Changed

- The labels of the options are read once per attribute instead of once per variant and option.
- The module is loaded after the modules it uses, including Magento_Swatches.
- README.
- Checks with PHPStan level 9, the Magento coding standard and unit tests on every push.
