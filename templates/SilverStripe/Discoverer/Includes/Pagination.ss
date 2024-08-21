<nav aria-label="<%t SilverStripe\Discoverer\Includes\Pagination.PaginationLabel 'Search paginaiton' %>">
    <ul class="pagination">
        <li class="pagination-item pagination-item--prev">
            <% if $NotFirstPage %>
                <a
                    class="pagination-prev-link"
                    href="$PrevLink"
                    {$Attributes}
                    aria-label="<%t SilverStripe\Discoverer\Includes\Pagination.PreviousPage 'Previous page' %>"
                >&laquo;</a>
            <% else %>
                <a
                    class="pagination-prev-link pagination-prev-link--disabled"
                    href="#"
                    aria-label="<%t SilverStripe\Discoverer\Includes\Pagination.PreviousPageDisabled 'Previous page (not available)' %>"
                    aria-disabled="true"
                >&laquo;</a>
            <% end_if %>
        </li>

        <% loop $PaginationSummary(4) %>
            <li class="pagination-item">
                <% if $CurrentBool %>
                    <a title="Viewing page $PageNum of results"
                       class="pagination-link pagination-link--disabled"
                    >$PageNum</a>
                <% else %>
                    <% if $Link %>
                        <a title="View page $PageNum of results" class="pagination-link" href="$Link">$PageNum</a>
                    <% else %>
                        <span class="pagination-link pagination-link--disabled">&hellip;</span>
                    <% end_if %>
                <% end_if %>
            </li>
        <% end_loop %>

        <li class="pagination-item pagination-item--next">
            <% if $NotLastPage %>
                <a
                    class="pagination-next-link"
                    href="$NextLink"
                    {$Attributes}
                    aria-label="<%t SilverStripe\Discoverer\Includes\Pagination.NextPage 'Next page' %>"
                >&raquo;</a>
            <% else %>
                <a
                    class="pagination-next-link pagination-next-link--disabled"
                    href="#"
                    aria-label="<%t SilverStripe\Discoverer\Includes\Pagination.NextPageDisabled 'Next page (not available)' %>"
                    aria-disabled="true"
                >&raquo;</a>
            <% end_if %>
        </li>
    </ul>
</nav>
