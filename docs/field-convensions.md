# Field convensions

Different search services use different naming convensions for fields, but most like to use either kebab-case or
snake_case.

The `FieldService` class provides a method can be used to convert your index fields into a format that is more familiar
to Silverstripe developers.

```php
$converted = FieldService::singleton()->getConvertedFieldName($fieldName);
```

I would recommend having a look at the example conversions in `FieldServiceTest` to understand how we transform
different naming patterns.

We should all try to following these standards when developing our service integrations:

* PascalCase
* Abbreviations like "ID", "IDs", "URL", etc, should be presented as "Id", "Ids", "Url", etc

Why "Id" and not "ID"?

Well.. ID is an easy one to predict because we know that Silverstripe uses that capitalised, but beyond that, it's
impossible for this module to programmatically know what other abbreviations you might be using in your project, and
equally impossible for this module to then guess whether you use that abbreviation fully capitalised or not.
