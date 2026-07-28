<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Party Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .footer {
            text-align: right;
            font-size: 10px;
            color: #777;
            margin-top: 30px;
        }
    </style>
</head>
<body>

    <h2>Party Master Export</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Party Name</th>
                <th>Mobile</th>
                <th>Email</th>
                <th>Address</th>
                <th>Assigned Employees</th>
            </tr>
        </thead>
        <tbody>
            @foreach($parties as $party)
                <tr>
                    <td>{{ $party->id }}</td>
                    <td>{{ $party->name }}</td>
                    <td>{{ $party->mobile }}</td>
                    <td>{{ $party->email }}</td>
                    <td>{{ $party->address }}</td>
                    <td>{{ $party->user->name ?? 'Unassigned' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Exported on {{ now()->format('Y-m-d H:i') }}
    </div>

</body>
</html>
