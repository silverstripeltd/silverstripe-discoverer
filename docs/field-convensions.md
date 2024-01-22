# Field convensions

Field names from search services should be converted into a standard that is more familiar to Silverstripe developers.

* PascalCase
* Abbreviations like "ID", "IDs", "URL", etc, should be presented as "Id", "Ids", "Url", etc

Why "Id" and not "ID"?

Well.. ID is an easy one, we know that folks use that abbreviation fully capitalised, but beyond that, it's impossible
for us to know what other abbreviations you might be using in your project, and equally impossible for us to guess
whether you use that abbreviation fully capitalised or not.

Another reason for this standard is because PHP provides us with the `ucwords` function, and that's a really easy and
consistent way to turn snake_case and kebab-case (which is a common field pattern for search services) into PascalCase.

EG:

```php
// Convert snake_case to PascalCase
str_replace('_', '', ucwords('snake_case', '_'));
// Convert kebab-case to PascalCase
str_replace('_', '', ucwords('kebab-case', '-'));
```
