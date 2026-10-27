import type { ReactNode, Ref } from 'react';
import { advance, type RequestStatus } from '../data';
import {
    ApprovalProgressCard,
    Card,
    Flash,
    LineItemsCard,
    PageHeading,
    RequestDetailsCard,
    StatusBadge,
    TypeBadge,
    type StageState,
} from './ui';

/**
 * Re-creation of payment-requests/show.blade.php for request #1042.
 */
export const RequestPage = ({
    status,
    statusChangedAt,
    flash,
    actions,
    actionsRef,
    stages,
    stagesStart,
    progressRef,
    submitted = false,
    approved = false,
}: {
    status: RequestStatus;
    statusChangedAt?: number;
    flash?: { message: string; start: number };
    actions: ReactNode;
    actionsRef?: Ref<HTMLDivElement>;
    stages?: StageState[];
    stagesStart?: number;
    progressRef?: Ref<HTMLDivElement>;
    submitted?: boolean;
    approved?: boolean;
}) => (
    <>
        {flash && <Flash key={flash.message} message={flash.message} start={flash.start} />}
        <PageHeading
            title={`Request #${advance.id}`}
            subtitle={`Created ${advance.createdAt}`}
            back="Back to Requests"
            badges={
                <>
                    <TypeBadge />
                    <StatusBadge key={status} status={status} changedAt={statusChangedAt} />
                    {status !== 'draft' && <span className="sgh-badge sgh-badge-sm sgh-badge-outline">Version 1</span>}
                </>
            }
        />
        <div className="sgh-container-fixed">
            <div className="grid grid-cols-3 gap-7.5">
                <div className="col-span-2 flex flex-col gap-7.5">
                    <RequestDetailsCard submitted={submitted} approved={approved} />
                    <LineItemsCard />
                </div>
                <div className="flex flex-col gap-7.5">
                    <Card title="Actions" cardRef={actionsRef} bodyClassName="sgh-card-content flex flex-col gap-3 p-5">
                        {actions}
                    </Card>
                    {stages && <ApprovalProgressCard stages={stages} cardRef={progressRef} start={stagesStart} />}
                </div>
            </div>
        </div>
    </>
);
