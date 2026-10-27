import type { StageState } from '../components/ui';
import { setupStages, stageApprovers, workflowStages } from '../data';

/**
 * Stage states at `frame`, given the frame on which each stage is approved.
 * The first unapproved stage is active; pass an empty list for a fully
 * approved workflow seen after the fact.
 */
export const stagesAt = (frame: number, approvedAt: number[] | 'all'): StageState[] => {
    if (approvedAt === 'all') {
        return workflowStages.map((name, index) => ({ name, role: setupStages[index].role, status: 'approved', by: stageApprovers[index] }));
    }

    const approvedCount = approvedAt.filter((at) => frame >= at).length;

    return workflowStages.map((name, index) => {
        if (index < approvedCount) {
            return { name, role: setupStages[index].role, status: 'approved', by: stageApprovers[index], changedAt: approvedAt[index] };
        }

        if (index === approvedCount) {
            return { name, role: setupStages[index].role, status: 'active', changedAt: index === 0 ? undefined : approvedAt[index - 1] + 6 };
        }

        return { name, role: setupStages[index].role, status: 'pending' };
    });
};
