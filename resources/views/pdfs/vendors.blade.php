<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Vendors List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #4CAF50;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .header-info {
            margin-bottom: 20px;
            font-size: 11px;
        }
        .badge-yes {
            color: green;
            font-weight: bold;
        }
        .badge-no {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Vendors List Report</h1>

    <div class="header-info">
        <strong>Generated On:</strong> {{ date('d M Y, h:i A') }}<br>
        <strong>Total Vendors:</strong> {{ $vendors->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Contact</th>
                <th>Email</th>
                <th>City</th>
                <th>RC Number</th>
                <th>RC Verified</th>
                <th>DL Number</th>
                <th>DL Verified</th>
                <th>Status</th>
                <th>Registration Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vendors as $vendor)
            <tr>
                <td>{{ $vendor->id }}</td>
                <td>{{ $vendor->name }}</td>
                <td>{{ $vendor->contact_number }}</td>
                <td>{{ $vendor->email ?? 'N/A' }}</td>
                <td>{{ $vendor->city ?? 'N/A' }}</td>
                <td>{{ $vendor->rc_number ?? 'N/A' }}</td>
                <td class="{{ $vendor->rc_verified ? 'badge-yes' : 'badge-no' }}">
                    {{ $vendor->rc_verified ? 'Yes' : 'No' }}
                </td>
                <td>{{ $vendor->dl_number ?? 'N/A' }}</td>
                <td class="{{ $vendor->dl_verified ? 'badge-yes' : 'badge-no' }}">
                    {{ $vendor->dl_verified ? 'Yes' : 'No' }}
                </td>
                <td>{{ $vendor->is_verified ? 'Verified' : 'Pending' }}</td>
                <td>{{ $vendor->created_at->format('d-m-Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
