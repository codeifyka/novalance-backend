type ProposalStatus = 'pending' | 'active' | 'done';
interface Proposal {
    id: number;
    cover_letter: string;
    freelancer_id: number;
    job_post_id: number;
    status: ProposalStatus;
    started_at: string;
    ends_at: string;
    created_at: string;
    updated_at: string;
}

interface CreateProposal {
    job_post_id: number;
    cover_letter: string;
}