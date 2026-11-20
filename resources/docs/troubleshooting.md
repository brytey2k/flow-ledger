---
title: Troubleshooting
description: "Common issues with requests, disbursements, and approvals — and how to reach support when you're stuck."
order: 11
---

## A request won't submit {#cant-submit}

If **Submit for Approval** fails on a Payment Request or Retirement Request, check:

- For an Expense or Retirement, whether your organization requires a receipt — see [Advance and expense defaults](/docs/settings/#request-defaults) — and attach at least one file if so.
- Whether an administrator has set up a [workflow template](/docs/approval-workflows/#configuring-workflow-templates) for this request type and your branch. If none exists, you'll be told to contact an administrator — that's exactly what's needed here.

## Disbursement or settlement is blocked {#disbursement-blocked}

An *"Insufficient cashbook balance"* message means the branch's [Cashbook](/docs/cash-management/#cashbook) doesn't have enough recorded balance to cover the payout. Record a [manual receipt](/docs/cash-management/#manual-receipts) to top it up, then try again.

## I can't see a branch, request, or report {#cant-see-something}

Visibility in Flow Ledger is branch-scoped — see [Branch-scoped visibility](/docs/roles-permissions/#branch-visibility). If something you expect to see is missing, it's usually because your account is scoped to a single branch rather than a branch and its descendants, or because your role is missing the relevant permission. Ask an administrator to check your role under [Roles & Permissions](/docs/roles-permissions/#creating-roles).

## A request seems stuck in approval {#stuck-in-workflow}

Check the **Approval Progress** stepper on the request. A stage marked **Blocked** has no independent eligible approver—it may be that the only role holder submitted the request, already acted at another stage, or doesn't match the stage's department or branch restriction.

Ask an administrator to assign a different user to one of the stage's existing primary or fallback roles under [**Settings → Users**](/docs/roles-permissions/#creating-roles) and confirm that user's department and branch under [**Organisation → Staff**](/docs/branches/#staff). A user who can edit workflow templates can then open the Payment Request or Retirement Request and click **Retry approver resolution**. The stage becomes **Active** when someone qualifies; otherwise it stays blocked and no later stage proceeds.

If the workflow version attached to the request has no usable fallback role, choose **Add one-request fallback**, select an eligible role, explain the exception, and apply it. This recovers only that request. Follow the persistent **Main workflow update required** notice to add the same fallback to a draft, then publish the draft to prevent future requests from becoming blocked. See [Keeping approvals independent](/docs/approval-workflows/#independent-approvals).

## Contact support {#support}

If you're still stuck, reach out with a description of what you were trying to do, any error message shown, and — for a request issue — the request number.

- Email **<support@flowledger.app>**.
