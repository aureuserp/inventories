<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            margin: 0;
            padding: 0;
        }
        body {
            margin: 0;
            padding: 15px;
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            table-layout: fixed;
        }
        td {
            width: 33%; /* Fixed width regardless of content */
            vertical-align: top;
            padding: 8px;
            border: 1px solid #ddd;
            background: white;
            overflow: hidden;
        }
        /* Empty cell styling */
        td.empty {
            border: none;
            background: transparent;
        }
        .record-name {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
            text-align: center;
        }
        .barcode-name {
            font-size: 10px;
            margin-bottom: 3px;
            text-align: center;
        }
        .barcode {
            text-align: center;
            margin: 5px 0;
            width: 100%;
        }
        .barcode img {
            display: inline-block;
            width: 90% !important;
            height: 30px !important;
            max-width: 100%;
        }
    </style>
</head>

<body>
    <table>
        @foreach ($records->chunk(3) as $chunk)
            <tr>
                @foreach ($chunk as $record)
                    <td>
                        <div class="record-name">{{ $record->name }}</div>

                        @if ($record->packageType)
                            <div class="record-name">Package Type: {{ $record->packageType->name }}</div>
                        @endif

                        @if ($record->pack_date)
                            <div class="record-name">Pack Date: {{ $record->pack_date }}</div>
                        @endif
                        
                        <div class="barcode">
                            {!! DNS1D::getBarcodeHTML($record->name, 'C128', 1, 30) !!}
                        </div>

                        <div class="barcode-name">{{ $record->name }}</div>
                    </td>
                @endforeach
                
                @for ($i = $chunk->count(); $i < 3; $i++)
                    <td class="empty"></td>
                @endfor
            </tr>
        @endforeach
    </table>
</body>
</html>