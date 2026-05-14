# Workflow Parallel Groups

## What Are Parallel Groups?

By default, workflow stages run sequentially — each stage must complete before the next activates. Parallel groups allow multiple stages to run at the same time, with configurable rules for when to advance.

## How Activation Works

The engine activates stages based on `display_order`. When `activateNextStages` runs, it finds all pending stages with the lowest `display_order` and activates them all simultaneously. This means:

- Stages with the **same `display_order`** are always activated together.
- Stages with a **higher `display_order`** remain pending until the current batch resolves.

## Parallel Group Modes (`require_all`)

A `WorkflowParallelGroup` has a `require_all` boolean that controls completion behaviour.

### `require_all = true` — AND (all must approve)

All stages in the group must reach a resolved status (`approved`, `skipped`, or `cancelled`) before the workflow advances. Each approver acts independently; the workflow waits for the last one.

**Use case:** Finance Manager **and** Legal Officer must both sign off.

### `require_all = false` — OR (first wins)

The first approver to act causes the engine to immediately cancel all sibling stages in the group and advance the workflow. The remaining approvers no longer need to act.

**Use case:** Any one of three regional managers can approve.

## Setting Up Parallel Stages

1. Create a `WorkflowParallelGroup` with a name and the desired `require_all` value.
2. Create the stages that should run in parallel, assigning each:
   - The **same `display_order`** (e.g. `2`)
   - The **same `parallel_group_id`**
3. Give the stage that follows a higher `display_order` (e.g. `3`).

**Example:**

```
display_order=1  Finance Officer          (sequential, no group)
display_order=2  Finance Manager  ──┐    parallel_group_id=1, require_all=true
display_order=2  Legal Officer    ──┘
display_order=3  Director                (only reached after both approve)
```

## The "No Group" Gotcha

Stages with the same `display_order` but **no `parallel_group_id`** behave like an implicit AND:

- Each approval calls `advanceWorkflow`, which only advances when `activeCount === 0`.
- So the workflow naturally waits for every ungrouped same-order stage to complete.
- However, there is **no OR mode available** — you cannot express "first wins" without a group.
- There is also **no sibling cancellation** — if you later want OR behaviour, adding a group is the only option.

| Configuration | Completion rule | Cancels siblings? |
|---|---|---|
| Same order, no group | All must complete (implicit AND) | No |
| Group, `require_all = true` | All must complete (explicit AND) | No |
| Group, `require_all = false` | First wins (OR) | Yes |

**Rule of thumb:** Always attach a parallel group to stages that share a `display_order`. The group makes the intent explicit and is the only way to enable OR behaviour.
