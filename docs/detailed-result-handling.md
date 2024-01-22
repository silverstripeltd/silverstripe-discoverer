# Detailed result handling

Ok, so you've written some queries and run some searches, and now you want to understand how that information is going
to be made available for use in (say) a Silverstripe template.

This page will use some examples from Elastic, but please keep in mind that this module is designed to be service
agnostic, and different services definitely do things in different ways.

## `Results` class

Whenever you perform a search through the `SearchService`, you will receive a `Results` object. This object has some
simple methods available that you can access anywhere.

* `isSuccess()`: Simply states whether or not the search was a success, or error.
* `getRecords()`: A `PaginatedList` of `Record` objects that were returned by the search service based on your `Query`.
* `getFacets`: An `ArrayList` of `Facet` objects that were returned by the search service based on your `Query`.

The `Results` class is also a `ViewableData` object, so these methods can be access in your template with `$isSuccess`,
`$Records`, and `$Facets`.

## `Record` class

The `Record` class is a simple `ViewableData` class, and the purpose of it is to provide you with access to the fields
that are returned by your search service, and to make those fields available to you in (EG) Silverstripe templates.

**Note:** A `Record` is not assumed to have a linked `DataObject`. The recommendation would be for you to index all of
the data you need access to as part of your search Documents. That way, you don't have to perform additional look ups
in your database.

You will notice that there is only one pre-defined property in this class - `$analyticsData`. All other fields are
dynamic properties - because we have no way of programmatically knowing what fields you might have for any particular
record.

### How fields are made available to you

As mentioned above, there is no way for this module to know what fields you have defined for your search index, but what
we can do is provide you with a predictable pattern to follow in order to access those fields.

We ask that plugin modules for services match the requirements outlined in [Field conversions](field-convensions.md).

**TL;DR:** All of your fields should be available through PascalCase, with abbreviations **also** using PascalCase (EG: 
ID = Id, IDs = Ids, URL = Url, etc).

Here are some examples for an Elastic index (where Elastic uses snake_case for its field names):

* `title` will be available through `$Title`
* `elemental_area` will be available through `$ElementalArea`
* `record_id` will be available through `$RecordId`

If you have analytics enabled, then you will also have a field available called `$AnalyticsData`. See 
[Adding analytics](#adding-analytics).

### Pagination

The `Results` object provides you with a `PaginatedList` of `Records`, that `PaginatedList` can be used like any other
to create your pagination.

For an example, see the template code in [Simple usage](simple-usage.md).

### Adding analytics

This module comes with an HTTP middleware that you can apply by setting the following env var:

* `SEARCH_ANALYTICS_ENABLED=true`

If you do this, then you will have access to `$AnalyticsData` for your `Record` objects. This can then be added to any
link as a query parameter.

The following example assumes that you have indexed a `title` and `link` for each search Document.

```silverstripe
<a href="{$Link}?{$AnalyticsData}">$Title</a>
<%-- Or perhaps with an if condition --%>
<a href="{$Link}<% if $AnalyticsData %>?$AnalyticsData<% end_if %>">$Title</a>
```

**Note:** You need to specify the `?` yourself. We don't include this so that you can append it to other query 
parameter requirements that you might have. EG:

```silverstripe
<a href="{$Link}?{$OtherQueryParams}&{$AnalyticsData}">$Title</a>
```
