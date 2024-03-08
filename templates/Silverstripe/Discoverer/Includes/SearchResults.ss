<% if $SearchResults %>

    <% include SilverStripe\Discoverer\SearchRecords %>

    <% include SilverStripe\Discoverer\SearchFacets %>

    <% include SilverStripe\Discoverer\SearchPagination %>
<% end_if %>
