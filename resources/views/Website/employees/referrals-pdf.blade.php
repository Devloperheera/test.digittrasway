<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employee Referrals - {{ $employee->emp_id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        .header {
            background: #4472C4;
            color: white;
            padding: 15px;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #4472C4;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 9px;
        }
        td {
            border: 1px solid #ddd;
            padding: 6px;
            font-size: 9px;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-success {
            background: #28a745;
            color: white;
        }
        .badge-warning {
            background: #ffc107;
            color: black;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Employee Referrals Report</h2>
        <p><strong>Employee:</strong> {{ $employee->name }} ({{ $employee->emp_id }})</p>
        <p><strong>Department:</strong> {{ $employee->department }} | <strong>Designation:</strong> {{ $employee->designation }}</p>
        <p><strong>Total Referrals:</strong> {{ $referrals->count() }} | <strong>Generated:</strong> {{ now()->format('d M, Y h:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Contact</th>
                <th>Email</th>
                <th>Install Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($referrals as $index => $user)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $user->name ?? 'N/A' }}</td>
                <td>{{ $user->contact_number }}</td>
                <td>{{ $user->email ?? 'N/A' }}</td>
                <td>{{ $user->app_installed_at ? $user->app_installed_at->format('d M, Y') : 'N/A' }}</td>
                <td>
                    <span class="badge badge-{{ $user->is_verified ? 'success' : 'warning' }}">
                        {{ $user->is_verified ? 'Verified' : 'Pending' }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
