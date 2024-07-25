# Simple usage

This module does not provide a search results page out of the box. Why? Because we do not want to prescribe the way that
you have to interact with search.

That said, below we have some examples on how you could set up search using a basic Page (managed through the CMS like
any other Page), and Controller.

**Note:** If you read through these examples, and you think they fit your use case, then you might want to consider
using [Silverstripe Discoverer > Search UI](https://github.com/silverstripeltd/silverstripe-discoverer-search-ui),
as this module provides most of what is described below out of the box.

* [`SearchResults.php`](#searchresultsphp)
* [`SearchResultsController.php`](#searchresultscontrollerphp)
* [`SearchResults.ss`](#searchresultsss)
* [`Record.ss`](#recordss)

Please also read the following:

* [Detailed usage](detailed-querying.md)
* [Detailed result handling](detailed-result-handling.md)

## `SearchResults.php`

A basic Page type that can be managed through the CMS.

```php
<?php

namespace App\Pages;

use Page;

class SearchResults extends Page
{

    private static string $table_name = 'SearchResults';

    private static string $icon_class = 'font-icon-p-search';

    private static string $singular_name = 'Search results page';

    private static string $plural_name = 'Search results pages';

    private static string $description = 'Display search results';

}

```

## `SearchResultsController.php`

This Controller provides 2 methods:

* `SearchForm()`: Output a basic `<form></form>` that can be used to submit a search request.
  * The important bit here is that it uses a query param `q` to pass the search query to the search method.
  * Not covered in this demo is filtering. You will likely want to add additional form fields for any filters, and pass
    those values along within your query params.
* `SearchResults()`: If the `q` query param is present, then this method will attempt to search, and return results for
  the template to process.
  * A `Results` object is what is returned, and this has a few key method, like `getRecords()` (which is a
    `PaginatedList`), `isSuccess()` (boolean), and `getFacets()` (an `ArrayList`).

```php
<?php

namespace App\Pages;

use PageController;
use SilverStripe\Core\Convert;
use SilverStripe\Discoverer\Query\Query;
use SilverStripe\Discoverer\Service\Results\Results;
use SilverStripe\Discoverer\Service\SearchService;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;

class SearchResultsController extends PageController
{

    private static array $allowed_actions = [
        'SearchForm',
    ];

    public function SearchForm(): Form
    {
        // The keyword that we want to search
        $keywords = Convert::raw2xml($this->getRequest()->getVar('q'));

        return Form::create(
            $this,
            __FUNCTION__,
            FieldList::create(
                TextField::create('q', 'Terms', $keywords)
            ),
            FieldList::create(
                FormAction::create('search', 'Search')
            )
        )
            ->setFormAction($this->dataRecord->Link())
            ->setFormMethod('GET')
            ->disableSecurityToken();
    }

    /**
     * Given URL parameters, create a Query object and pass it to our Search Service, then return the results (if any)
     * to the template for parsing
     */
    public function SearchResults(): ?Results
    {
        // The index suffix that you wish to search. If you are using (eg) subsites, or Fluent, then this might need to
        // be dynamic, and based on which site or Locale a user is browsing
        $index = 'main';
        // The keywords that we want to search. raw2xml() will ensure that search strings are escaped with special
        // characters converted
        $keywords = Convert::raw2xml($this->getRequest()->getVar('q'));
        // How many records we want to display per page
        $perPage = 10;
        // Pagination (if supplied)
        $start = $this->getRequest()->getVar('start') ?? 0;

        // No results unless we search for something
        if (!$keywords) {
            return null;
        }

        $service = SearchService::create();
        // Instantiate a new Query, and provide the search terms that we wish to search for
        $query = Query::create($keywords);
        // Set pagination requirements
        $query->setPagination($perPage, $start);

        // You must also specify which fields you would like included in your results
        // Request an unformatted title field
        $query->addResultField('title');
        // Request a formatted value (AKA a "snippet") for your Content field
        $query->addResultField('content', 400, true);
        // You'll need to have a "link" to each record in your Documents - this module does not automatically
        // connect Documents to DataObjects. Your Documents should really include all the information that you
        // require for creating a search result output
        $query->addResultField('link');

        return $service->search($query, $index);
    }

}

```

## `SearchResults.ss`

**Note:** There is also a [basic theme](https://github.com/silverstripeltd/silverstripe-discoverer-theme) available for
Discoverer, which you might be interested in.

Assuming you have a `SearchResults` page with the namespace `App\Pages` (if not, then update the location accordingly):
`themes/your-theme/templates/App/Pages/Layout/SearchResults.ss`

When you return `$service->search()` from your `SearchResultsController`, a `Results` object is returned, and this comes
out of the box with a basic default `Results.ss` template.

For your `SearchResults.ss` template, the only two variables you need to output are `$SearchForm` (which will have the
search interface for your users), and `$SearchResults`, which will output the OotB results template.

```silverstripe
<div class="form">
    $SearchForm
</div>

$SearchResults

```

## `Record.ss`

Ok, the "hard" bit.

This module has no idea what fields you have in your search index, so there is really no reasonable way for us to
predict what fields you would want to display in your results. As such, you need to implement the template that
indicates how a single "result" should be displayed.

Save your template in:
`themes/your-theme/templates/SilverStripe/Discoverer/Service/Results/Record.ss`

The following example assumes that you have the following fields in your search index, but you should update the
template as you need to:

* `Title`: Your record title (EG: Page title, File title, etc)
* `Link`: A link to the record (EG: Page link, File link, etc)
* `Content`: The primary content of the record (EG: the `Content` field on your Page, or the text content of a File)

`$AnalyticsData` is a special property that is available on all `Records`, and it is populated whenever the env var
`SEARCH_ANALYTICS_ENABLED` is set to `1`. If you are not using any sort of analytics tracking from your search service,
then you can likely leave this out.

The module will automatically display formatted values (aka "snippets") where available - falling back to unformatted
values where formatted isn't available.

```silverstripe
<li>
    <h2>
        <a href="{$Link}<% if $AnalyticsData %>?$AnalyticsData<% end_if %>">$Title</a>
    </h2>

    <% if $Content %>
        $Content
    <% end_if %>
</li>

```

Each `Record` is (by default) housed in a `ul`. You can override this behaviour by implementing your own `Records.ss`
(plural) template.

The following default templates are provided by this module, and you can please feel free to override any and all of
them:

* `Includes\`
  * `Pagination.ss`
  * `Summary.ss`
* `Service\`
  * `Results\`
    * `Facet.ss`
    * `Facets.ss`
    * `Record.ss` - Which you will have implemented above
    * `Records.ss` - Houses the default `ul` wrapper for your results
    * `Results.ss` - Contains the template holding the search summary, results, and pagination
