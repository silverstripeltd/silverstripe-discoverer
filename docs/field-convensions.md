# Field convensions

Different search services use different naming convensions for fields, but most like to use either kebab-case or
snake_case.

In order to provide a consistent pattern for interacting with `Record` objects in this module, all field names from
search services should be converted into a standard that is more familiar to Silverstripe developers.

* PascalCase
* Abbreviations like "ID", "IDs", "URL", etc, should be presented as "Id", "Ids", "Url", etc

Why "Id" and not "ID"?

Well.. ID is an easy one to predict because we know that Silverstripe uses that capitalised, but beyond that, it's
impossible for this module to programmatically know what other abbreviations you might be using in your project, and
equally impossible for this module to then guess whether you use that abbreviation fully capitalised or not.

The easiest, and most consistent way to solve this issue is to simply say that each portion of a field name that is
separated by (eg) `-` or `_` is one word, and therefor treated as PascalCase, and not PascalCASE.

Another reason for this standard is because PHP provides us with the `ucwords` function, and that's a really easy and
consistent way to turn snake_case and kebab-case (which is a common field pattern for search services) into PascalCase.

EG:

```php
// Convert snake_case to PascalCase
str_replace('_', '', ucwords('snake_case', '_'));
// Convert kebab-case to PascalCase
str_replace('_', '', ucwords('kebab-case', '-'));
```
