<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1a1a1a; }
        h1 { font-size: 20px; margin-bottom: 0; }
        h2 { font-size: 15px; margin-top: 24px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .meta { color: #666; margin-top: 4px; }
        .score { font-size: 32px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { text-align: left; padding: 6px 4px; border-bottom: 1px solid #eee; font-size: 11px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .critical { background: #fde2e1; color: #b3261e; }
        .warn { background: #fef3c7; color: #92400e; }
        .info { background: #e5e7eb; color: #374151; }
        .footer { margin-top: 30px; font-size: 9px; color: #999; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">{{ $website->name }} ({{ $website->domain }}) &mdash; generated {{ now()->toDayDateTimeString() }}</p>

    @foreach ($sections as $section)
        <h2>{{ $section['title'] }}</h2>

        @if (isset($section['score']))
            <p class="score">{{ $section['score'] ?? '—' }}<span style="font-size:14px;">/100</span></p>
        @endif

        @if (!empty($section['findings']))
            <table>
                <thead>
                    <tr><th>Severity</th><th>Finding</th><th>Explanation</th></tr>
                </thead>
                <tbody>
                    @foreach ($section['findings'] as $finding)
                        <tr>
                            <td><span class="badge {{ $finding['severity'] === 'critical' ? 'critical' : ($finding['severity'] === 'warn' ? 'warn' : 'info') }}">{{ $finding['severity'] }}</span></td>
                            <td>{{ $finding['title'] }}</td>
                            <td>{{ $finding['explanation'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @elseif (isset($section['findings']))
            <p>No issues found.</p>
        @endif

        @if (!empty($section['summary']))
            <p>{{ $section['summary'] }}</p>
        @endif
    @endforeach

    <p class="footer">SiteGuardian AI &mdash; automatically generated report. Not a substitute for a full manual security or accessibility audit.</p>
</body>
</html>
