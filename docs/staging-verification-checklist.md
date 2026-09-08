# Staging Browser Verification Checklist

Use this runbook to perform complete end-to-end user journey verification across the browser and platform before official release.

---

## 1. Public Signup & Workspace Creation (Free Basic)

-   [ ] **Navigate to Registration**: Open `/start-trial` (or `/signup`) in a browser.
-   [ ] **Verify Value Proposition**: Heading must state: `Free Basic · Up to 10 active employees · No expiry` with no mention of a 14-day trial countdown.
-   [ ] **Submit Registration**:
    -   Organization Name: `Acme Staging Ltd` (Observe automatic slug preview: `acme-staging-ltd`).
    -   Country Code: `PH`, Timezone: `Asia/Manila`.
    -   Admin: `Jane Doe`, Email: `admin@acmestaging.test`, Strong Password.
    -   Terms: Checked.
    -   Click **Create my free workspace**.
-   [ ] **Confirmation**:
    -   Success banner appears displaying workspace ID `acme-staging-ltd`.
    -   Verify message confirms: `No expiry. Free Basic.`
    -   Direct button **Sign in to your workspace** is present.

---

## 2. Administrator Authentication & First Login

-   [ ] **Sign In**: Navigate to `/login` with `admin@acmestaging.test` and your password.
-   [ ] **Workspace Land**: Confirm seamless redirection to the Dashboard (`/`).
-   [ ] **Verify Setup Checklist**:
    -   Top of dashboard displays the **Workspace Setup Guide** with 3 steps:
        1. _Configure Organization Settings_ (`/settings`)
        2. _Add Team Members_ (`/employee-management`)
        3. _Test Attendance & Approval_ (`/attendance-management`)
    -   Progress shows `0 of 3 done` (or `1 of 3` if admin profile is pre-counted).

---

## 3. Password Recovery Flow

-   [ ] **Request Reset**: Log out, go to `/forgot-password`, input `admin@acmestaging.test`, and submit.
-   [ ] **Reset Link**: Check mailhog/mail log for password reset link (`/reset-password?token=...&email=...`).
-   [ ] **Submit New Password**: Choose a new valid password and submit.
-   [ ] **Sign In**: Sign in with the new password and verify success.

---

## 4. Free Allowance & Capacity Enforcement (10 Active Seats)

-   [ ] **Add Employees 1 through 9**:
    -   In `/employee-management`, create 9 additional employee profiles (totaling 10 with the admin).
    -   Check dashboard: Setup Guide Step 2 automatically marks as completed (`Add Team Members: 10/10`).
-   [ ] **Attempt 11th Employee**:
    -   Try creating the 11th active employee.
    -   Form validation must block submission with message:
        `Basic includes 10 active employees. Upgrade before adding or reactivating another employee.`

---

## 5. Attendance & Approval Workflow

-   [ ] **Punch Attendance**: In `/attendance-management`, click **Clock In** and then **Clock Out**.
-   [ ] **Submit Correction Request**: In `/attendance-corrections`, submit an attendance correction for manager review.
-   [ ] **Approve Request**: In `/approval-inbox`, review the pending correction request and approve it.
-   [ ] **Audit Trail**: Check that the correction audit status updates to approved.

---

## 6. Growth Billing Upgrade & Bill Preview

-   [ ] **Navigate to Billing**: In `/billing`, review the active subscription:
    -   Plan: **Free Basic (Active)**
    -   Quota Gauge: `10 / 10 included seats used (100%)`
    -   Bill Preview: `First 10 employees: ₱0 (Free)`
-   [ ] **Project Team Costs**:
    -   Move slider to 25 employees.
    -   Bill preview calculates: `15 billable seats × ₱19 = ₱285/month`.
-   [ ] **Initiate Stripe Checkout**:
    -   Click **Upgrade to Growth Plan**.
    -   Browser redirects to Stripe Checkout with unit amount `₱19.00` (`1900 centavos`) and billable seat quantity.
-   [ ] **Simulate Success**:
    -   Complete Stripe checkout or simulate `checkout.session.completed` webhook.
    -   Return to `/billing`: Plan now shows **Growth (Active)**, next monthly renewal date, and **Customer Portal** management button.

---

## 7. Billing Failures, Grace Window, and Cancellation

-   [ ] **Simulate Payment Failure (`invoice.payment_failed`)**:
    -   Post webhook event for the organization's subscription ID.
    -   Reload `/billing`: Status changes to **Past Due**.
    -   Warning banner displays 7-day grace window with button **Update Payment Method**.
-   [ ] **Simulate Cancellation (`customer.subscription.deleted`)**:
    -   Post cancellation webhook event.
    -   Reload `/billing`: Status displays **Canceled**.
