# Silverstripe Search

## Purpose

To provide you (a Silverstripe developer) with search interfaces that **do not change**, even when you switch between
different search service providers.

## Delivery

The deliver on our purpose, the way that you perform filtering, faceting, and certainly the way that you display
results, is very likely going to change. We hope that the learning curve is reasonable, and that the majority of
developer interactions with this code is intuative (once you understand the mentality behind it).

## Feature support

Whether or not certain features are supported **by this module**. Noting that if a service provider does not have any
way for us to integrate with such a feature, then that feature (in this module) will just silently [do nothing] if you
switch to that new provider.

| Feature                                | Module support | Future module support | Rationale                                                                                                                                                                                                                                   |
|----------------------------------------|----------------|-----------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Filters                                | Yes            | Yes                   | You should (hopefully) be able to achieve any sort of nested filtering that you require.                                                                                                                                                    |
| Facets                                 | Partial        | If desired            | Facet support differs hugely between search services. Current thinking is to try and limit what sort of Facet integrations we use (EG: no Geolocation) in the hopes that if we switch services in the future, we won't lose functionality   |
| Multi-search                           | No             | If desired            | Not supported by many other search services, and I couldn't find any existing project using the feature. It wouldn't be unreasonable effort to add this functionality though.                                                               |
| Spelling suggestions                   | No             | Unlikely              | Part of a different API in Elastic (so it is technically possible), but the current thinking is that the typo tollerence provided by (most) search services should be sufficient for this lower tiered service.                             |
| Search suggestions (aka auto-complete) | No             | Unlikely              | Features like auto-complete generate a lot of requests, and for a lower tiered shared service, that is something we want to cut down on.                                                                                                    |
| Click through logging                  | Yes            | Yes                   | Supported in Elastic App Search, but not supported by many other Search Services (including Elasticsearch). This feature in particular is an example of the "silent treatment" we apply when a feature disappears between service providers |

## Getting started

* Querying, filtering, faceting, etc, will now all be performed through interfaces that are provided by this module.
  There is no ability to perform (eg) "raw filtering", or "raw faceting", where you pass data that is in a specific
  format for a specific search service provider. This would break the purpose of this module, since if your search
  service provided changed, then your "raw filtering" would also likely break.
* This module does not assume that your search Documents relate to a `DataObject`, as such, it is recommended that your
  search Documents contain all of the data required for you to build out search results without needing to query the
  application for any additional information.
* When you perform a search, you will receive a `Result` object that includes a `PaginatedList` of `Record` objects.
  The `Record` objects will contain any/all information that you requested during your query, and this is how you will
  output info into your template.
* Fields within your `Record` objects will match the fields in your search index, but be converted to PascalCase, with
  abbreviations presented as (eg) `Id`, `Url`, etc. You can read more about this under [Field convensions](docs/field-convensions.md).

Additional documentation can also be found below:

* [Simple usage](docs/simple-usage.md)
  * Provides some basic code samples.
  * Includes *a bit* more detail on how `Results` and `Records` work.
* [Detailed result handling](docs/detailed-result-handling.md)
  * Lots more information about `Results`, `Records`, pagination, analytics.
* [Detailed querying](docs/detailed-querying.md)
  * Lots more information about filters, facets, sorts, and (hopefully) everything else you need to know to perform
    whatever sort of search you require.
