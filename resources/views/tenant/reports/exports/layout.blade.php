<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $exportTitle }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; }
        .header { padding: 12px 0 8px; border-bottom: 2px solid #1a1a1a; margin-bottom: 10px; }
        .header h1 { font-size: 15px; font-weight: 700; }
        .header .meta { font-size: 9px; color: #555; margin-top: 3px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1a1a1a; color: #fff; text-align: left; padding: 5px 6px; font-size: 9px; font-weight: 600; white-space: nowrap; }
        td { padding: 4px 6px; border-bottom: 1px solid #e5e5e5; vertical-align: top; }
        tr:nth-child(even) td { background: #f8f8f8; }
        tfoot td { background: #f0f0f0; font-weight: 700; border-top: 2px solid #1a1a1a; }
        .footer { margin-top: 10px; font-size: 8px; color: #888; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $exportTitle }}</h1>
        <div class="meta">
            @yield('meta')
        </div>
    </div>

    @yield('table')

    <div class="footer">
        Generated on {{ now()->format('d M Y, H:i') }}
    </div>
</body>
</html>
