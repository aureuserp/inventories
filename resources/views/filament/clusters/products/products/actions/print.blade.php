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
            border-spacing: 10px;
            table-layout: fixed;
        }
        td {
            vertical-align: top;
            padding: 8px;
            border: 1px solid #ddd;
            background: white;
            overflow: hidden;
        }
        .format-4x7_price td,
        .format-4x12 td,
        .format-4x12_price td {
            width: 22%;
        }
        .format-2x7_price td {
            width: 47%;
        }
        .record-name {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
            text-align: center;
        }
        .barcode {
            text-align: center;
            margin: 5px 0;
        }
        .barcode img {
            width: 100% !important;
            height: 30px !important;
            max-width: 100%;
        }
        .barcode-name {
            font-size: 10px;
            margin-bottom: 3px;
            text-align: center;
        }
        .format-2x7_price .barcode img {
            height: 40px !important;
        }
        .price {
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            margin-top: 3px;
        }
        .price::before {
            content: '$ ';
        }
        
        .format-dymo table {
            border-spacing: 0;
        }
        .format-dymo td {
            padding: 5px;
        }
        .format-dymo .barcode img {
            height: 40px !important;
        }
    </style>
</head>
<body class="format-{{ $format }}">
    @php
        $columns = match ($format) {
            'dymo' => 1,
            '2x7_price' => 2,
            '4x7_price' => 4,
            '4x12' => 4,
            '4x12_price' => 4,
            default => 2,
        };
        
        $showPrice = str_contains($format, 'price');
        
        $flatRecords = [];

        foreach ($records as $record) {
            for ($i = 0; $i < $quantity; $i++) {
                $flatRecords[] = $record;
            }
        }
        
        $totalProducts = count($flatRecords);
        
        $barcodeScale = $columns == 4 ? 1 : 2;
    @endphp

    <table>
        @for ($i = 0; $i < $totalProducts; $i += $columns)
            <tr>
                @for ($j = 0; $j < $columns && ($i + $j) < $totalProducts; $j++)
                    @php
                        $currentRecord = $flatRecords[$i + $j];
                    @endphp

                    <td>
                        <div class="record-name">{{ $currentRecord->name }}</div>

                        @if ($currentRecord->barcode)
                            <div class="barcode">
                                {!! DNS1D::getBarcodeHTML($currentRecord->barcode, 'C128', $barcodeScale, 30) !!}
                            </div>

                            <div class="barcode-name">{{ $currentRecord->barcode }}</div>
                        @endif

                        @if ($showPrice)
                            <div class="price">{{ number_format($currentRecord->price, 2) }}</div>
                        @endif
                    </td>
                @endfor
            </tr>
        @endfor
    </table>
</body>
</html>