# Simple usage

* [`SearchResults.php`](#searchresultsphp)
* [`SearchResultsController.php`](#searchresultscontrollerphp)
* [`SearchResults.ss`](#searchresultsss)

**Note:** This is not meant to be a feature-packed demo or a prescription for how you should implement search. This is
just here to give a basic idea of how you might interact with the service components that this module provides.

Please also read the following:

* [Detailed usage](detailed-querying.md)
* [Detailed result handling](detailed-result-handling.md)

This module does not provide a search results page out of the box. You can hook it up to your existing results page, or
create a new one. A minimal example of a `SearchResultsController` is below, along with the Silverstripe template that
might go alongside it.

## `SearchResults.php`

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

    private static string $description = 'Display search results from Elastic search';

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

use PageController;use Psr\Log\LoggerInterface;use SilverStripe\Core\Convert;use SilverStripe\Discoverer\Query\Query;use SilverStripe\Discoverer\Service\Results\Results;use SilverStripe\Discoverer\Service\SearchService;use SilverStripe\Forms\FieldList;use SilverStripe\Forms\Form;use SilverStripe\Forms\FormAction;use SilverStripe\Forms\TextField;

class SearchResultsController extends PageController
{

    private ?LoggerInterface $logger = null;

    private static array $dependencies = [
        'logger' => '%$' . LoggerInterface::class . '.errorhandler',
    ];

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

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

        try {
            $service = SearchService::create();
            // Instantiate a new Query, and provide the search terms that we wish to search for
            $query = Query::create($keywords);
            // Set pagination requirements
            $query->setPagination($perPage, $start);

            // You must also specify which fields you would like included in your results
            // Request an unformatted title field
            $query->addResultField('title');
            // Request a formatted value (AKA a "snippet") for your Content field
            $query->addResultField('content', 200, true);
            // You'll need to have a "link" to each record in your Documents - this module does not automatically
            // connect Documents to DataObjects. Your Documents should really include all of the information that you
            // require for creating a search result output
            $query->addResultField('link');

            return $service->search($query, $index);
        } catch (Throwable $e) {
            // Log the error without breaking the page
            $this->logger->error(sprintf('Elastic error: %s', $e->getMessage()), ['elastic' => $e]);
        }

        return null;
    }

}

```

## `SearchResults.ss`

`SearchResults::getRecords()` contains a `PaginatedList` of `Record` objects. Each `Record` object represents one of the
search Documents that was returned from your search service.

The `Record` object contains one `Field` for each result field from your search service. The exact format of the field
names is determined by the plugin module that you are using, but the **general rule** is that we try to turn your field
names into PascalCase. So, for example, if your search service had a field called `summary_field`, then this would
(ideally) have been converted into `$SummaryField` for you to use in your template.

Each `Field` can have a `Raw` and `Formatted` value (depending on what you requested during your search). By default,
within a Silverstripe template, we will attempt to display the `Formatted` value first, and then we'll fallback to the
`Raw` value. However, you can also explictly call `.Raw` or `.Formatted` for just those values (without any programmatic
fallback logic).

`$AnalyticsData` is the only predictable field in a `Record` object, and it is enabled with the
`SEARCH_ANALYTICS_ENABLED` environment variable.

```silverstripe
<div class="search-form">$SearchForm</div>

<% if $SearchResults %>
    <% with $SearchResults %>
        <p class="_summary">Displaying $Records.FirstItem - $Records.LastItem results of $Records.TotalItems for "$Query"</p>
    <% end_with %>

    <ul>
        <% loop $SearchResults.Records %>
            <li>
                <%-- The following assumes that you have fields in your index called `link`, `title`, and `description` --%>
                <%-- $AnalyticsData is the only predefined field available to all Records. All other fields are based on what fields are in your index--%>
                <h2><a href="{$Link}<% if $AnalyticsData %>?$AnalyticsData<% end_if %>">$Title.Raw: $Description</a></h2>

                <%-- An example of looping through a field that contains an array of primitives --%>
                <%-- The following assumes that you have a field in your index called `taxonomy_terms` --%>
                <% loop $TaxonomyTerms %>
                    $Me
                <% end_loop %>
            </li>
        <% end_loop %>
    </ul>

    <%-- Facet example --%>
    <% if $SearchResults.Facets %>
        <ul class="facets">
            <% loop $SearchResults.Facets %>
                <% if $Data %>
                    <li>Field: $FieldName
                        <ul>
                            <% loop $Data %>
                                <li>$Value: $Count</li>
                            <% end_loop %>
                        </ul>
                    </li>
                <% end_if %>
            <% end_loop %>
        </ul>
    <% end_if %>

    <%-- Pagination example --%>
    <% with $SearchResults.Records %>
        <% if $MoreThanOnePage %>
            <ul class="pagination">
                <li class="pagination-item pagination-item--prev">
                    <% if $NotFirstPage %>
                        <a title="View previous page of results" class="pagination-prev-link" href="$PrevLink">&laquo;</a>
                    <% else %>
                        <span title="View previous page of results" class="pagination-prev-link pagination-prev-link--disabled">&laquo;</span>
                    <% end_if %>
                </li>

                <% loop $PaginationSummary(4) %>
                    <% if $CurrentBool %>
                        <li class="pagination-item pagination-item--active">
                            <a title="Viewing page $PageNum of results" class="pagination-link pagination-link--disabled">$PageNum</a>
                        </li>
                    <% else %>
                        <% if $Link %>
                            <li class="pagination-item">
                                <a title="View page $PageNum of results" class="pagination-link" href="$Link">$PageNum</a>
                            </li>
                        <% else %>
                            <li class="pagination-item">
                                <a class="pagination-link pagination-link--disabled">&hellip;</a>
                            </li>
                        <% end_if %>
                    <% end_if %>
                <% end_loop %>

                <li class="pagination-item pagination-item--next">
                    <% if $NotLastPage %>
                        <a title="View next page of results" class="pagination-next-link" href="$NextLink">&raquo;</a>
                    <% else %>
                        <span title="View next page of results" class="pagination-next-link pagination-next-link--disabled">&raquo;</span>
                    <% end_if %>
                </li>
            </ul>
        <% end_if %>
    <% end_with %>
<% else %>
    <p class="error">Sorry, there was an error or you didn't enter a keyword.</p>
<% end_if %>

```
