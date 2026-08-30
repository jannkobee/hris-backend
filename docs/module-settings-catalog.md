# Module settings catalog

Settings below are company policies. Keep record-like data in its own module:
meeting rooms in Workplace Hub, leave-credit rules in Leave Credit Settings,
and holidays in Workforce Calendar. This keeps history, audit trails, and
approval permissions intact.

## Current settings

- Organization: company name and timezone.
- Attendance: photo/location/IP capture, notes, manual entries, photo limit.
- Leave: attachment availability. Leave-credit schedule, eligibility, minimum
  service, and new-hire grants are managed in **Leave Credit Settings**.
- Messaging: real-time delivery, attachments, and file limit.
- Employee 201 files: availability and file-size limit.
- Payroll: enabled state, country/ruleset, frequency, schedule, attendance
  treatment, statutory configuration, and Philippines-only rates.
- Automation: active scheduled tasks are configured in **Scheduled Tasks**.
- Workplace Hub: rooms are managed through **Configurations > Meeting Rooms**.

## Recommended next settings

| Module | Recommended company settings |
| --- | --- |
| Workplace Hub | booking lead time, maximum meeting duration, buffer between bookings, cancellation window, default room capacity, approval for external attendees, attachment limit, retention period |
| Workforce Calendar | country/region, holiday provider, sync year, manual override policy, automatic import review, working-day defaults |
| Leave | workday/holiday calculation, half-day policy, overlap policy, carry-over cap/expiry, conversion eligibility, approval chain, proof requirements |
| Overtime | minimum duration, advance notice, attendance requirement, approval chain, daily/monthly cap, meal/rest policy |
| Employee records | required profile fields, onboarding checklist, probation defaults, document expiry reminders, data retention |
| Attendance | shift/schedule, grace period, geofence/IP allow-list, correction window, device/import policy |
| Announcements | audience defaults, publish/expiry dates, acknowledgement requirement, attachment limit |
| Messaging | retention, searchable history, allowed file types, maximum group size, external communication policy |
| Security | password/MFA/SSO policy, session timeout, IP restrictions, audit-log retention, export approval |

## Holiday provider decision

Calendarific is suitable for a future provider integration because it exposes
country/year holiday data over HTTPS and includes national, local, religious,
and observance categories. Do not automatically treat provider categories as
Philippine payroll classifications: every imported day must be reviewed as a
regular holiday, special non-working day, special working day, or company day
before it affects pay. Use a preview-and-review import workflow, not a blind
background upsert.
