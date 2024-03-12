<% if $Facets %>
    $Facets
<% end_if %>

<% if $Records %>
    <% with $Records %>
        <% include SilverStripe\Discoverer\Includes\Summary %>

        $Me

        <% if $MoreThanOnePage %>
            <% include SilverStripe\Discoverer\Includes\Pagination %>
        <% end_if %>
    <% end_with %>
<% else %>
    <p class="error">No search results.</p>
<% end_if %>
