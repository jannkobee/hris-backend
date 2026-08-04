<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Leave Request</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f6f8;
            color: #333333;
            margin: 0;
            padding: 40px 20px;
        }
        .card {
            max-width: 500px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        h2 {
            color: #1867c0; /* App Primary Color */
            margin-top: 0;
            font-size: 20px;
            border-bottom: 1px solid #eeeeee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .info-item {
            margin: 10px 0;
            font-size: 15px;
        }
        .label {
            font-weight: 600;
            color: #555555;
            display: inline-block;
            width: 100px;
        }
        .button {
            display: block;
            width: max-content;
            margin: 30px auto 0;
            padding: 10px 24px;
            background-color: #1867c0; /* App Primary Color */
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            text-align: center;
        }
        .footer {
            margin-top: 25px;
            font-size: 12px;
            color: #999999;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Leave Request Submitted</h2>
        
        <p>Hello Admin,</p>
        <p>A new leave request requires your approval.</p>
        
        <div class="info-item">
            <span class="label">Employee:</span> 
            {{ $leaveRequest->employee->user->full_name ?? 'N/A' }} ({{ $leaveRequest->employee->employee_no ?? 'N/A' }})
        </div>
        <div class="info-item">
            <span class="label">Role:</span> 
            {{ $leaveRequest->employee->position->name ?? 'N/A' }} &mdash; {{ $leaveRequest->employee->department->name ?? 'N/A' }}
        </div>
        <div class="info-item">
            <span class="label">Leave Type:</span> 
            {{ $leaveRequest->leaveType->name ?? 'N/A' }}
        </div>
        <div class="info-item">
            <span class="label">Dates:</span> 
            {{ $leaveRequest->start_date->format('M j, Y') }} to {{ $leaveRequest->end_date->format('M j, Y') }}
        </div>
        <div class="info-item">
            <span class="label">Reason:</span> 
            {{ $leaveRequest->reason ?: 'None provided.' }}
        </div>

        <a href="{{ url(env('FRONTEND_URL', 'http://localhost:3000') . '/leave-requests') }}" class="button">
            Review Request
        </a>

        <div class="footer">
            This is an automated message from your HR System.
        </div>
    </div>
</body>
</html>