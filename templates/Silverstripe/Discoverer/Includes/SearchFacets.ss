<% if $SearchResults.Facets %>
    <h3>Facet results:</h3>

    <ul class="facets">
        <% loop $SearchResults.Facets %>
            <% if $Data %>
                <li>Facet field: $FieldName
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
