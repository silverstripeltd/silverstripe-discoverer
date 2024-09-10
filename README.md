# 🧭 Silverstripe Discoverer: Search and querying module for Silverstripe CMS

* [Purpose](#purpose)
* [Delivery](#delivery)
* [Installation](#installation)
* [Feature support](#feature-support)
* [Getting started](#getting-started)
  * [Simple usage](docs/simple-usage.md)
  * [Detailed result handling](docs/detailed-result-handling.md)
  * [Detailed querying](docs/detailed-querying.md)
* [Available service integration modules](#available-service-integration-modules)

## Purpose

To provide you (a Silverstripe developer) with interfaces for search querying that **do not change**, even when you
switch between different search service providers (EG: Elastic, Algolia, Silverstripe Search).

## Delivery

To deliver on our purpose, the way that you perform filtering, faceting, and certainly the way that you display
results, is very likely going to change. We hope that the learning curve is reasonable, and that the majority of
developer interactions with this code is intuative (once you understand the mentality behind it).

You will **not** be able to perform any sort of "raw filtering" or "raw querying" with service specific formats, as that
would not meet the purpose of this module - which is to use a common interface that will allow you to easily switch
between services.

## Installation

**Note:** this module does not function without an
[integration module](#available-service-integration-modules)

If you want to install this module for developing your own integration module, then you can install it with:

```
composer require "silverstripe/silverstripe-discoverer"
```

If you are planning to search through a page and controller, then you might also want to consider using
[Silverstripe Discoverer > Search UI](https://github.com/silverstripeltd/silverstripe-discoverer-search-ui)

## Feature support

Whether or not certain features are supported **by this module**. Noting that different search providers often do things
in different ways, and often have different levels of support for features. This module attempts to provide a level
of functionality that is commonly supported by many different services.

| Feature                                                     | Module support | Future module support | Rationale                                                                                                                                                                                                                                                 |
|-------------------------------------------------------------|----------------|-----------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Filters                                                     | Yes            | Yes                   | You should (hopefully) be able to achieve any sort of nested filtering that you require.                                                                                                                                                                  |
| Facets                                                      | Partial        | Partial               | Facet support differs hugely between search services. Current thinking is to try and limit what sort of Facet integrations we support (EG: no Geolocation) in the hopes that if you switch services in the future, you won't lose functionality.          |
| Multi-search                                                | No             | If desired            | Not supported by many search services. It wouldn't be unreasonable effort to add this functionality though. Plugin modules for services without mult-search could manually perform 2 searches and combine the results.                                    |
| Search suggestions (aka auto-complete, "did you mean", etc) | No             | Yes                   | This feature comes in many different shapes and sizes between services, we do plan to add support for this feature in the very near future.                                                                                                               |
| Click through logging                                       | Yes            | Yes                   | Supported in Elastic App Search, but not supported by many other Search Services (including Elasticsearch). This feature in particular is an example of the "silent treatment" that might be applied when a feature disappears between service providers. |

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

## Available service integration modules

* [Silverstripe Discoverer > Elastic Enterprise Search](https://github.com/silverstripeltd/silverstripe-discoverer-elastic-enterprise)
* [Silverstripe Discoverer > Silverstripe Search](https://github.com/silverstripeltd/silverstripe-discoverer-bifrost)
