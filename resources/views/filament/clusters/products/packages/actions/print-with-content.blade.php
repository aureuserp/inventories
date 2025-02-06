<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
    <head>
        <!-- meta tags -->
        <meta http-equiv="Cache-control" content="no-cache">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

        <!-- lang supports inclusion -->
        <style type="text/css">
            body {
                font-size: 16px;
                color: #091341;
            }

            .page-content {
                padding: 12px;
            }

            .small-text {
                font-size: 12px;
            }

            table {
                width: 100%;
                border-spacing: 1px 0;
                border-collapse: separate;
                margin-bottom: 16px;
            }
            
            table thead th {
                background-color: #E9EFFC;
                padding: 6px 18px;
                text-align: left;
            }

            table.rtl thead tr th {
                text-align: right;
            }

            table tbody td {
                padding: 9px 18px;
                border-bottom: 1px solid #E9EFFC;
                text-align: left;
                vertical-align: top;
            }

            table.rtl tbody tr td {
                text-align: right;
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
        <div class="page">
            <div class="page-content">
                @foreach ($records as $record)
                    <!-- Information -->
                    <table>
                        <tbody>
                            <tr>
                                <td style="width: 50%; padding: 2px 18px;border:none;">
                                    <b>
                                        {{ $record->name }}
                                    </b>
                                </td>

                                <td style="width: 50%; padding: 2px 18px;border:none;">
                                    <div class="barcode">
                                        {!! DNS1D::getBarcodeHTML($record->name, 'C128', 1, 30) !!}
                                    </div>

                                    <div class="barcode-name">{{ $record->name }}</div>
                                </td>
                            </tr>

                            @if ($record->package_type_id || $record->pack_date)
                                <tr>
                                    @if ($record->package_type_id)
                                        <td style="width: 50%; padding: 2px 18px;border:none;">
                                            <b>
                                                Package Type:
                                            </b>

                                            <span>
                                                {{ $record->packageType->name }}
                                            </span>
                                        </td>
                                    @endif

                                    @if ($record->pack_date)
                                        <td style="width: 50%; padding: 2px 18px;border:none;">
                                            <b>
                                                Pack Date:
                                            </b>

                                            <span>
                                                {{ $record->pack_date }}
                                            </span>
                                        </td>
                                    @endif
                                </tr>
                            @endif
                        </tbody>
                    </table>

                    <!-- Items -->
                    @if (! $record->quantities->isEmpty())
                        <div class="items">
                            <table>
                                <thead>
                                    <tr>
                                        <th>
                                            Barcode
                                        </th>

                                        <th>
                                            Product
                                        </th>

                                        <th>
                                            Quantity
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($record->quantities as $item)
                                        <tr>
                                            <td>
                                                @if ($item->product->barcode)
                                                    <div class="barcode">
                                                        {!! DNS1D::getBarcodeHTML($item->product->barcode, 'C128', 1, 30) !!}
                                                    </div>

                                                    <div class="barcode-name">{{ $item->product->barcode }}</div>
                                                @endif
                                            </td>

                                            <td>
                                                {{ $item->product->name }}
                                            </td>

                                            <td>
                                                {{ $item->quantity.' '.$item->product->uom->name }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </body>
</html>
