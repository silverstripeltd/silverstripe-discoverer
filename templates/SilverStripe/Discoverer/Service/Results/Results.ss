<% if $Records %>
    <% with $Records %>
        <% include SilverStripe\Discoverer\Includes\Summary %>

        $Me

        <% if $MoreThanOnePage %>
            <% include SilverStripe\Discoverer\Includes\Pagination %>
        <% end_if %>
    <% end_with %>
<% else %>
    <p class="error"><%t SilverStripe\Discoverer\Service\Results\Results.NoResults 'No search results.' %></p>
<% end_if %>
