@if (isset($messages[$step]))
    <div style="margin-top:10px;">
        @if ($messages[$step]['ok'])
            <div style="padding:10px; background:#e8fff0; border:1px solid #67c587;">
                ✔ {{ $messages[$step]['text'] }}
            </div>
        @else
            <div style="padding:10px; background:#fff1f1; border:1px solid #e18b8b;">
                ✘ {{ $messages[$step]['text'] }}
            </div>
        @endif
    </div>
@endif