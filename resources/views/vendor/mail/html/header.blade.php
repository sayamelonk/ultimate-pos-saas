@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<span style="font-size: 24px; font-weight: 700; color: #1e3a5f; text-decoration: none;">
{{ $slot }}
</span>
</a>
</td>
</tr>
