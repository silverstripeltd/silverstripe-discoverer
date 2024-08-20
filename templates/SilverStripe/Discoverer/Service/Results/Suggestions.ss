<ul>
    <% loop $Me %>
        <% if $Up.TargetQueryUrl && $Up.TargetQueryStringField %>
            <li><a href="{$Up.TargetQueryUrl}?{$Up.TargetQueryStringField}={$Me}">$Me</a></li>
        <% else %>
            <li>$Me</li>
        <% end_if %>
    <% end_loop %>
</ul>
