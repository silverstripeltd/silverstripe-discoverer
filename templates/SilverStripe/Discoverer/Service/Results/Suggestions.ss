<ul>
    <% loop $Me %>
        <% if $Up.DesiredUrl && $Up.DesiredQueryField %>
            <li><a href="{$Up.DesiredUrl}?{$Up.DesiredQueryField}={$Me}">$Me</a></li>
        <% else %>
            <li>$Me</li>
        <% end_if %>
    <% end_loop %>
</ul>
