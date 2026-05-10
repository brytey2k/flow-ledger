# Flow Ledger — End-to-End Testing Guide

This guide walks through setting up the application from scratch and testing the complete request lifecycle: **Setup → Workflow Configuration → Payment Request → Approval → Disbursement → Retirement → Cashbook**.

---

## Table of Contents

1. [Initial Setup](#1-initial-setup)
2. [Configure Supporting Data](#2-configure-supporting-data)
3. [Configure Roles & Permissions](#3-configure-roles--permissions)
4. [Set Up Staff & Users](#4-set-up-staff--users)
5. [Configure Workflow Templates](#5-configure-workflow-templates)
6. [Create a Payment Request](#6-create-a-payment-request)
7. [Submit for Approval](#7-submit-for-approval)
8. [Approve the Request](#8-approve-the-request)
9. [Disburse the Payment](#9-disburse-the-payment)
10. [Create a Retirement Request](#10-create-a-retirement-request)
11. [Approve & Settle the Retirement](#11-approve--settle-the-retirement)
12. [The Cashbook](#12-the-cashbook)
13. [Status Reference](#13-status-reference)

---

## 1. Initial Setup

Log in as a super-admin or a user with full permissions.

**URL:** `/login`

After logging in you land on the **Dashboard** which shows pending approvals, recent requests, and quick stats.

---

## 2. Configure Supporting Data

These must be set up before you can create staff or requests. Order matters — some depend on others.

### 2.1 Currencies

**URL:** `/currencies`

Create at least one currency (e.g. GHS, USD).

| Field | Example |
|-------|---------|
| Name | Ghana Cedi |
| Code | GHS |
| Symbol | ₵ |

### 2.2 Levels

**URL:** `/levels`

Levels represent the **type of office location** in your organisation's structure. Branches are then assigned to a level.

| Field | Example |
|-------|---------|
| Name | Head Office |
| Description | Top-level headquarters |

Create levels for each tier of your structure, for example:

| Name | Meaning |
|------|---------|
| Head Office | The organisation's headquarters (e.g. HQ) |
| Branch | Country or regional offices (e.g. Ghana Office, Burkina Faso Office) |
| Office | Sub-offices under a branch |

You can name these anything that fits your structure. Branches (Section 2.5) are then assigned one of these levels.

### 2.3 Departments

**URL:** `/departments`

| Field | Example |
|-------|---------|
| Name | Finance |
| Description | Finance & Accounts |

### 2.4 Positions

**URL:** `/positions`

| Field | Example |
|-------|---------|
| Name | Accounts Officer |
| Description | Handles payment processing |

### 2.5 Branches

**URL:** `/branches`

Branches are the actual office locations. Each branch is assigned a **Level** (configured in Section 2.2) that describes what type of location it is.

| Field | Example |
|-------|---------|
| Name | HQ |
| Level | Head Office |
| Location | Accra |
| Default Currency | GHS |

Example branch structure:

| Name | Level |
|------|-------|
| HQ | Head Office |
| Ghana Office | Branch |
| Burkina Faso Office | Branch |
| Accra Office | Office |

### 2.6 Account Codes

**URL:** `/account-codes`

Account codes classify expenditure items on requests.

| Field | Example |
|-------|---------|
| Code | 5001 |
| Name | Travel & Transport |
| Description | Field travel expenses |

Create several codes that cover your test scenarios (travel, accommodation, supplies, etc.).

---

## 3. Configure Roles & Permissions

**URL:** `/roles`

Roles control what users can see and do. Create at minimum:

| Role | Purpose |
|------|---------|
| Finance Officer | Creates and submits requests |
| Finance Manager | Approves requests |
| Finance Director | Final approver |
| Disbursement Officer | Disburses approved requests |
| Admin | Full access |

### Assign Permissions to Roles

After creating a role, click **Edit Permissions** (or go to `/roles/{id}/permissions`).

Key permissions to assign:

| Permission | Relevant Role(s) |
|-----------|-----------------|
| `AccessPaymentRequests` | All roles |
| `CreatePaymentRequest` | Finance Officer |
| `AccessRetirementRequests` | All roles |
| `CreateRetirementRequest` | Finance Officer |
| `ApproveRequests` | Finance Manager, Finance Director |
| `DisburseRequests` | Disbursement Officer, Admin |
| `SettleRetirements` | Finance Manager, Admin |
| `AccessCashbook` | Finance Manager, Disbursement Officer, Admin |
| `CreateCashbookEntry` | Finance Manager, Admin |
| `DeleteCashbookEntry` | Admin |
| `AccessWorkflowTemplates` | Admin |
| `CreateWorkflowTemplate` | Admin |
| `AccessStaff` | Admin |
| `AccessUsers` | Admin |
| `AccessRoles` | Admin |
| `AccessActivityLog` | Admin |

---

## 4. Set Up Staff & Users

### 4.1 Create Staff

**URL:** `/staff/create`

Staff represent employees who will raise requests.

| Field | Notes |
|-------|-------|
| First Name / Last Name | Employee's full name |
| Staff ID | Unique identifier (e.g. EMP001) |
| Email | Employee email |
| Department | Select from configured departments |
| Position | Select from configured positions |
| Level | Select from configured levels |
| Branch | Select from configured branches |

Create at least **2 staff members** — one to be the requester, one as an approver.

### 4.2 Create Users

**URL:** `/users/create`

Users are the system login accounts. Link each user to a staff profile if they need to raise requests themselves.

| Field | Notes |
|-------|-------|
| Name | Display name |
| Email | Login email |
| Password | Initial password |
| Role | Assign one or more roles |
| Linked Staff | (Optional) Link to a staff profile |

Create users for each role you need to test:
- `requester@example.com` → Role: Finance Officer, linked to a staff profile
- `manager@example.com` → Role: Finance Manager
- `director@example.com` → Role: Finance Director
- `disburse@example.com` → Role: Disbursement Officer

> **Note:** A user must have a linked staff profile to appear as the requester on a payment request.

---

## 5. Configure Workflow Templates

Workflow templates define the approval chain for each request type. **This is required before any request can be submitted.**

**URL:** `/workflow-templates`

### 5.1 Create a Template

Click **Create Workflow Template**.

| Field | Notes |
|-------|-------|
| Name | e.g. "Advance Approval Workflow" |
| Type | `advance` or `expense` or `retirement` |
| Description | Brief description |

> You need separate templates for **advance**, **expense**, and **retirement** request types.

### 5.2 Add Approval Stages

After creating the template, click into it and add stages. Each stage represents one level of approval.

**URL:** `/workflow-templates/{id}/stages/create`

| Field | Notes |
|-------|-------|
| Name | e.g. "Line Manager Review" |
| Display Order | Controls sequence (1, 2, 3...) |
| Required Roles | Which roles can action this stage |
| Skip Below Amount | (Optional) Auto-skip if request total is below this amount |

**Example approval chain for an Advance request:**

| Order | Stage Name | Roles Required | Skip Below |
|-------|-----------|----------------|------------|
| 1 | Line Manager Review | Finance Manager | — |
| 2 | Director Approval | Finance Director | 500.00 |

> Stages with the **same display order** run in parallel. Stages with different orders run sequentially.

### 5.3 Parallel Groups (Optional)

If you have multiple stages at the same order level and want to control whether **all** or **any one** approver is sufficient:

1. Create a Parallel Group on the template (give it a name and set `Require All` true/false)
2. Assign the relevant stages to this group when creating/editing those stages

| `Require All = true` | All stages in the group must approve before advancing |
| `Require All = false` | First approval in the group advances the workflow; others are cancelled |

### 5.4 Minimum Required Templates

For full end-to-end testing, create templates for all three types:

| Template Name | Type |
|--------------|------|
| Advance Approval Workflow | `advance` |
| Expense Approval Workflow | `expense` |
| Retirement Approval Workflow | `retirement` |

---

## 6. Create a Payment Request

Log in as the **requester** user (e.g. Finance Officer with a linked staff profile).

**URL:** `/requests/create`

| Field | Notes |
|-------|-------|
| Staff | The staff member the request is for |
| Branch | Which branch |
| Currency | Select currency |
| Type | `advance` (will need retirement later) or `expense` |
| Purpose / Description | What the funds are for |
| Items | Add line items (description, amount, account code) |

> **Advance** — funds given upfront; must be retired after use.  
> **Expense** — reimbursement for already-incurred costs; no retirement required.

After filling the form and submitting, the request is saved as a **Draft**.

The request detail page (`/requests/{id}`) shows:
- Request details and items
- Current status
- Workflow progress (once submitted)
- Comments section
- Activity log

---

## 7. Submit for Approval

From the request detail page, click **Submit for Approval**.

- The system looks up the workflow template matching the request type (`advance` or `expense`).
- A workflow instance is created and the first stage(s) become **active**.
- Approvers with the required role receive a notification.
- The request status changes to **In Workflow**.

> If no workflow template exists for the request type, submission will fail. Return to [Section 5](#5-configure-workflow-templates).

---

## 8. Approve the Request

Log in as an **approver** (e.g. Finance Manager with `ApproveRequests` permission).

**URL:** `/approvals`

The approvals list shows all active stages assigned to the current user's roles.

Click a pending approval to open the detail view (`/approvals/{instanceStageId}`).

### Available Actions

| Action | Effect |
|--------|--------|
| **Approve** | Marks this stage approved; advances to the next stage (or fully approves if last stage) |
| **Reject** | Cancels the entire workflow; request status → `cancelled` |
| **Send Back** | Returns the request to the requester for revision; request status → `sent_back` |

You can also add a **comment** before actioning.

### After All Stages Approved

Once every required stage is approved (or skipped/auto-skipped by amount threshold):
- Workflow instance status → `completed`
- Payment request status → **`approved`**
- Notification sent to the disbursement team

### Handling a Send Back

If an approver sends the request back:
1. Log in as the requester
2. Go to the request detail page (`/requests/{id}`)
3. Review any comments from the approver
4. Make necessary edits
5. Click **Resubmit** — the workflow resumes from the stage that sent it back

---

## 9. Disburse the Payment

Log in as a user with the **`DisburseRequests`** permission.

**URL:** `/disbursements`

The disbursements list shows all **approved** requests ready to be paid out.

Click **Disburse** on a request and fill in:

| Field | Notes |
|-------|-------|
| Disbursement Method | e.g. Bank Transfer, Cash, Cheque |
| Reference Number | Transaction/cheque reference |
| Notes | (Optional) Additional notes |

On submission:
- Request status → **`disbursed`**
- `disbursed_at` and `disbursed_by` are recorded
- Notification sent to the requester (funds are ready)
- A **credit entry** is automatically added to the branch's cashbook (money has left the cashbook and gone to the staff member)

> Only **advance-type** requests require a retirement after disbursement. Expense requests end here.

> The cashbook entry is created automatically — no manual action is needed. You can verify it in the branch's cashbook (see [Section 12](#12-the-cashbook)).

---

## 10. Create a Retirement Request

After an advance has been disbursed and the staff member has used the funds, they must account for the expenditure through a retirement.

Log in as the **requester**.

**URL:** `/requests/{paymentRequestId}/retirement/create`

> You can also navigate from the request detail page — a **Retire** button appears once the request is in `disbursed` status.

| Field | Notes |
|-------|-------|
| Items | Actual items purchased/spent (description, amount, account code, receipt number) |
| Attachments | Upload receipts or supporting documents |

The system automatically calculates:

| Scenario | Difference Type | Meaning |
|----------|----------------|---------|
| Spent = Advance | `nil` | No balance to settle |
| Spent > Advance | `pay_to_staff` | Company owes the employee the shortfall |
| Spent < Advance | `refund_to_company` | Employee must return the surplus |

The retirement is saved as a **Draft**.

### Submit the Retirement

From the retirement detail page (`/retirements/{id}`), click **Submit for Approval**.

The system uses the **retirement** workflow template. The same approval flow applies (stages, approvals, send-back, etc.).

---

## 11. Approve & Settle the Retirement

### Approve

Approvers follow the same flow as payment requests:
- Go to `/approvals`
- Action the retirement approval stages
- Once all stages are approved, retirement status → **`approved`**

### Settle

Log in as a user with **`SettleRetirements`** permission.

Go to the retirement detail page (`/retirements/{id}`) and click **Settle**.

| Field | Notes |
|-------|-------|
| Settlement Notes | Record how the difference was handled |

On settlement:
- Retirement status → **`settled`**
- `settled_at` and `settled_by` are recorded
- A cashbook entry is automatically created based on the difference type:

| Difference Type | Cashbook Effect | Direction |
|----------------|----------------|-----------|
| `refund_to_company` | Staff returns surplus — money comes back in | **Debit** (balance increases) |
| `pay_to_staff` | Company owes staff extra — money goes out | **Credit** (balance decreases) |
| `nil` | Spent exactly the advance — no balance to settle | No entry created |

If `difference_type = refund_to_company` — document that the employee has returned the surplus funds.  
If `difference_type = pay_to_staff` — document that the extra expense has been reimbursed.

> The cashbook entry is created automatically on settlement. Check the branch cashbook to confirm the entry (see [Section 12](#12-the-cashbook)).

---

## 12. The Cashbook

The cashbook gives you a real-time view of cash on hand for each branch. It records every movement of cash — automatically when payments are disbursed or retirements are settled, and manually when cash is received from the bank or another external source.

### No Setup Required

The cashbook is created automatically the first time any transaction touches a branch (disbursement, retirement settlement, or first manual receipt). There is no separate creation step.

### Navigation

Two ways to reach a branch's cashbook:

1. **Sidebar** — Click **Cashbook** under the Finance section (requires `AccessCashbook` permission). This takes you to the branches list; select a branch from there.
2. **Branches list** — Go to `/branches` and click the calculator icon on any branch row (requires `AccessCashbook` permission).

**URL:** `/branches/{branch}/cashbook`

### What You See

The cashbook index shows:

- **Current balance card** — live cash on hand for the branch (in the branch's currency)
- **Entries table** — all transactions in reverse chronological order

| Column | Notes |
|--------|-------|
| Date | The date the transaction occurred or was recorded |
| Description | What the entry is for (e.g. "Payment disbursed", "Retirement settlement") |
| Reference | Optional reference number |
| Type | **Debit** (money in — shown in green) or **Credit** (money out — shown in red) |
| Amount | Transaction amount |
| Actions | Trash icon for deletable manual entries only |

Entries generated automatically from disbursements and retirements show an **Auto** label and cannot be deleted.

### How the Balance Works

| Event | Direction | Balance Effect |
|-------|-----------|----------------|
| Payment request disbursed | Credit (out) | Balance decreases |
| Retirement settled — `pay_to_staff` | Credit (out) | Balance decreases |
| Retirement settled — `refund_to_company` | Debit (in) | Balance increases |
| Manual receipt (bank top-up) | Debit (in) | Balance increases |

### Recording a Manual Receipt (Bank Top-Up)

When cash is received from a bank or external source and added to the physical cashbook, record it here.

Requires `CreateCashbookEntry` permission.

**URL:** `/branches/{branch}/cashbook/receipts/create`

Or click **Add Receipt** on the cashbook index page.

| Field | Notes |
|-------|-------|
| Amount | The amount received — must be greater than zero |
| Entry Date | The date the cash was received — cannot be a future date |
| Reference | (Optional) Bank reference, cheque number, etc. |
| Notes | (Optional) Additional context |

On submission:
- A **debit** entry is created (money has come in)
- The cashbook balance increases by the amount entered

### Deleting a Manual Receipt

Requires `DeleteCashbookEntry` permission.

Only manually created receipts can be deleted. Auto-generated entries (from disbursements and retirements) are permanent.

Click the trash icon on the entry row. The entry is removed and the balance is adjusted downward by the deleted amount.

> Auto-generated entries cannot be deleted. If a disbursement or retirement entry is incorrect, it must be corrected by reversing or correcting the source transaction.

---

## 13. Status Reference

### Payment Request Statuses

| Status | Meaning |
|--------|---------|
| `draft` | Created but not yet submitted |
| `in_workflow` | Under approval review |
| `sent_back` | Returned for revision by an approver |
| `approved` | All approvals passed; ready for disbursement |
| `cancelled` | Rejected and cancelled |
| `disbursed` | Funds have been paid out |

### Retirement Request Statuses

| Status | Meaning |
|--------|---------|
| `draft` | Created but not submitted |
| `in_workflow` | Under approval review |
| `sent_back` | Returned for revision |
| `approved` | All approvals passed; ready for settlement |
| `cancelled` | Cancelled |
| `settled` | Difference has been settled |

### Workflow Stage Statuses

| Status | Meaning |
|--------|---------|
| `pending` | Waiting for prior stages to complete |
| `active` | Currently awaiting action from approvers |
| `approved` | Approved |
| `rejected` | Rejected — triggers cancellation of entire request |
| `sent_back` | Returned for revision |
| `skipped` | Auto-skipped because request amount was below the stage threshold |
| `cancelled` | Cancelled because another stage in a parallel group resolved first |

---

## Quick Test Checklist

Use this as your testing punch list:

- [ ] Created at least one currency, level, department, position, branch, and account code
- [ ] Created roles with correct permissions assigned (including `AccessCashbook`, `CreateCashbookEntry`, `DeleteCashbookEntry`)
- [ ] Created staff members and linked users to staff profiles
- [ ] Created workflow templates for `advance`, `expense`, and `retirement` types
- [ ] Each template has at least one stage with roles assigned
- [ ] Created a draft **advance** payment request
- [ ] Submitted the request — status changed to `in_workflow`
- [ ] Approved all stages as the approver user — status changed to `approved`
- [ ] Disbursed the request as the disbursement user — status changed to `disbursed`
- [ ] Verified a **credit** entry appeared in the branch cashbook with the disbursed amount
- [ ] Created a retirement request from the disbursed advance
- [ ] Submitted the retirement — status changed to `in_workflow`
- [ ] Approved the retirement — status changed to `approved`
- [ ] Settled the retirement — status changed to `settled`
- [ ] Verified the correct cashbook entry appeared (debit for `refund_to_company`, credit for `pay_to_staff`, no entry for `nil`)
- [ ] Recorded a manual bank top-up receipt in the cashbook — balance increased
- [ ] Deleted the manual receipt — balance adjusted back down
- [ ] Confirmed auto-generated entries cannot be deleted (trash icon absent)
- [ ] Tested **send back** flow: approver sends back → requester resubmits → approval completes
- [ ] Tested **rejection** flow: approver rejects → request cancelled
- [ ] Tested an **expense** request (no retirement required — ends at disbursement)
