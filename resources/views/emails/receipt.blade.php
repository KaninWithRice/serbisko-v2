<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #00923F; padding-bottom: 10px; }
        .receipt-number { font-size: 24px; font-weight: bold; color: #005288; text-align: center; margin: 20px 0; padding: 15px; background: #f9f9f9; border: 2px dashed #ccc; }
        .label { font-size: 12px; color: #777; text-transform: uppercase; font-weight: bold; }
        .value { font-size: 16px; font-weight: bold; margin-bottom: 15px; }
        .footer { text-align: center; font-size: 12px; color: #999; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="color: #00923F; margin: 0;">TNCHS-SHS</h1>
            <p style="margin: 5px 0;">Digital Enrollment Receipt</p>
        </div>

        <div>
            <div class="label">Student Name</div>
            <div class="value">{{ $user->last_name }}, {{ $user->first_name }} {{ $user->middle_name }}</div>

            <div class="label">Learner Reference Number (LRN)</div>
            <div class="value">{{ $student->lrn }}</div>

            <div class="label">Grade Level</div>
            <div class="value">Grade {{ $enrollment->grade_level }}</div>

            <div class="label">Track</div>
            <div class="value">{{ $enrollment->track }}</div>

            <div class="label">Selected Cluster</div>
            <div class="value">{{ $enrollment->cluster }}</div>
        </div>

        <div class="receipt-number">
            <div class="label" style="margin-bottom: 5px;">Receipt Number</div>
            {{ $enrollment->receipt_number }}
        </div>

        <p style="text-align: center; color: #b91c1c; font-weight: bold;">
            Please keep this receipt number for your records.
        </p>

        <div class="footer">
            &copy; {{ date('Y') }} Trece Martires City National High School - Senior High School. All rights reserved.
        </div>
    </div>
</body>
</html>