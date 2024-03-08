<ul>
    <% loop $Records %>
        <li>
            <a href="{$Link}<% if $AnalyticsData %>?$AnalyticsData<% end_if %>">$Title</a>

            <p><strong>Last Edited:</strong> $LastEdited</p>

            <% if $Body %>
                <p>$Body</p>
            <% end_if %>

            <% if $Content %>
                <p>$Content</p>
            <% end_if %>
        </li>
    <% end_loop %>
</ul>

