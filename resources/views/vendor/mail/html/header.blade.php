@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
{{-- PNG, not SVG: most email apps don't show SVG images. --}}
<img src="{{ \App\Support\Brand::emailLogo() }}" width="220" height="42" alt="{{ config('app.name') }}" style="height: 42px; width: 220px; max-width: 100%; border: 0;">
</a>
</td>
</tr>
