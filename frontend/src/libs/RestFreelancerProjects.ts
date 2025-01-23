import type { AxiosInstance } from "axios";
import Utils from "./Utils";
import type Result from "./Result";

export default class RestFreelancerProjects {
    axiosInstance: AxiosInstance;

    constructor(axiosInstance: AxiosInstance) {
        this.axiosInstance = axiosInstance;
    }

    async getAll(username: string): Promise<Result<string, Project[]>> {
        if (!username) {
            return this.axiosInstance.get(`/api/user/projects`).then(Utils.handleResponse<string, Project[]>);
        }
        return this.axiosInstance.get(`/api/user/${username}/projects`).then(Utils.handleResponse<string, Project[]>);
    }

    async getById(project_id: number): Promise<Result<string, Project>> {
        return this.axiosInstance.get(`/api/projects/${project_id}`).then(Utils.handleResponse<string, Project>);
    }

    async uploadImages(files: File[]): Promise<Result<string, string[]>> {
        let formData = new FormData();

        for (let i = 0; i < files.length; i++) {
            formData.append(`file${i + 1}`, files[i]);
        }

        return await this.axiosInstance.post('/api/projects/upload_images', formData, {
            headers: {
                'Content-Type': 'multipart/form-data', // Important for file uploads
            },
            onUploadProgress: progressEvent => {
                const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total!);
                console.log(percentCompleted);
                // You can update a progress bar or do something with the progress here
            }
        }).then(Utils.handleResponse<string, string[]>);
    }

    async create(project: CreateProject): Promise<Result<string, Project>> {
        return await this.axiosInstance.post('/api/projects/create', project).then(Utils.handleResponse<string, Project>);
    }
};
