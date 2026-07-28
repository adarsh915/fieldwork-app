<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Customer Profile - {{ $party->name }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }

        h1,
        h2,
        h3 {
            color: #1e293b;
            margin-top: 0;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin-bottom: 5px;
        }

        .header p {
            color: #64748b;
            margin: 0;
        }

        .section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 5px;
            margin-bottom: 15px;
            color: #475569;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .info-table th {
            text-align: left;
            padding: 6px 0;
            color: #64748b;
            font-weight: normal;
            width: 120px;
        }

        .info-table td {
            padding: 6px 0;
            font-weight: bold;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            text-align: left;
        }

        .data-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: bold;
        }

        .data-table td {
            color: #334155;
        }

        .badge {
            padding: 2px 6px;
            font-size: 10px;
            border-radius: 4px;
            display: inline-block;
            font-weight: bold;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background: #fef9c3;
            color: #854d0e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-primary {
            background: #e0e7ff;
            color: #3730a3;
        }

        .text-right {
            text-align: right !important;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>{{ $party->name }}</h1>
        <p>Customer Profile & Activity Report</p>
        <p style="font-size: 10px; margin-top: 5px;">Generated on {{ now()->format('d M Y, h:i A') }}</p>
    </div>

    <div class="section">
        <div class="section-title">Customer Details</div>
        <table class="info-table">
            <tr>
                <th>Customer ID</th>
                <td>#{{ str_pad($party->id, 5, '0', STR_PAD_LEFT) }}</td>
                <th>Assigned To</th>
                <td>{{ $party->user->name ?? 'Unassigned' }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $party->email ?? 'N/A' }}</td>
                <th>Phone</th>
                <td>{{ $party->phone ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Address</th>
                <td colspan="3">{{ $party->address ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">KPI Summary</div>
        <table class="data-table">
            <tr>
                <th>Total Visits</th>
                <th>Total Orders</th>
                <th>Total Transactions</th>
                <th>Lifetime Revenue</th>
            </tr>
            <tr>
                <td style="font-size: 14px; font-weight: bold;">{{ $party->visits->count() }}</td>
                <td style="font-size: 14px; font-weight: bold;">{{ $party->orders->count() }}</td>
                <td style="font-size: 14px; font-weight: bold;">{{ $party->transactions->count() }}</td>
                <td style="font-size: 14px; font-weight: bold;">
                    ${{ number_format($party->orders->where('status', '!=', 'cancelled')->sum('total_amount'), 2) }}
                </td>
            </tr>
        </table>
    </div>

    @if($purchasedProducts->count() > 0)
        <div class="section">
            <div class="section-title">Top Products Purchased</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th class="text-right">Times Bought</th>
                        <th class="text-right">Total Qty</th>
                        <th class="text-right">Total Spent</th>
                        <th class="text-right">Last Purchased</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchasedProducts->take(15) as $item)
                        <tr>
                            <td>{{ $item->product->name ?? 'Unknown' }}</td>
                            <td>{{ $item->product->code ?? 'N/A' }}</td>
                            <td class="text-right">{{ $item->times_bought }}</td>
                            <td class="text-right">{{ $item->total_quantity }}</td>
                            <td class="text-right">${{ number_format($item->total_spent, 2) }}</td>
                            <td class="text-right">{{ $item->last_purchased ? $item->last_purchased->format('d M Y') : 'N/A' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($purchasedProducts->count() > 15)
                <p style="font-size: 10px; color: #64748b;">* Showing top 15 products</p>
            @endif
        </div>
    @endif

    <div class="section">
        <div class="section-title">Recent Orders</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($party->orders->sortByDesc('created_at')->take(10) as $order)
                    <tr>
                        <td>{{ $order->created_at->format('d M Y') }}</td>
                        <td>{{ $order->order_type ?? 'Standard' }}</td>
                        <td>{{ ucfirst($order->status) }}</td>
                        <td class="text-right">${{ number_format($order->total_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center;">No orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Recent Visits</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Fieldworker</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($party->visits->sortByDesc('check_in_at')->take(10) as $visit)
                    <tr>
                        <td>{{ $visit->check_in_at ? $visit->check_in_at->format('d M Y') : 'N/A' }}</td>
                        <td>{{ $visit->user->name ?? 'N/A' }}</td>
                        <td>{{ ucfirst($visit->status) }}</td>
                        <td>{{ $visit->remarks ?? 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center;">No visits found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>

</html>