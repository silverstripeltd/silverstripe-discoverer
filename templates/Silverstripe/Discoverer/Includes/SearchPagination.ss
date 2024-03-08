
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
