import type { AxiosInstance } from "axios";
import Result from "./Result";
import Utils from "./Utils";

export default class RestProposals {
    axiosInstance: AxiosInstance;

    constructor(axiosInstance: AxiosInstance) {
        this.axiosInstance = axiosInstance;
    }

    async create(proposal: CreateProposal): Promise<Result<string, Proposal>> {
        return this.axiosInstance.post('/api/proposals/create', proposal).then(Utils.handleResponse<string, Proposal>);
    }

    async getByFreelancerJobId(job_id: number): Promise<Result<string, Proposal[]>> {
        return this.axiosInstance.get(`/api/proposals/${job_id}`).then(Utils.handleResponse<string, Proposal[]>);
    }

    async getMyProposals(): Promise<Result<string, Proposal[]>> {
        return this.axiosInstance.get(`/api/proposals/getMyProposals`).then(Utils.handleResponse<string, Proposal[]>);
    }

    async getUserInfo(user_id: number): Promise<Result<string, User>> {
        return this.axiosInstance.get(`/api/user/${user_id}`).then(Utils.handleResponse<string, User>);
    }

    async acceptProposal(id: number): Promise<Result<string, Proposal>> {
        return this.axiosInstance.put(`/api/proposals/${id}/accept`).then(Utils.handleResponse<string, Proposal>);
    }

    async getFreelancerProposals(): Promise<Result<string, Proposal[]>> {
        return this.axiosInstance.get(`/api/proposals/getFreelancerProposals`).then(response => response.data);
    }
};
