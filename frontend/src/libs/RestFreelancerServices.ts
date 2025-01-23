import type { AxiosInstance } from "axios";
import Result from "./Result";
import Utils from "./Utils";

export default class RestFreelancerServices {
    axiosInstance: AxiosInstance;

    constructor(axiosInstance: AxiosInstance) {
        this.axiosInstance = axiosInstance;
    }

    async getAll(username: string): Promise<Result<string, Service[]>> {
        if (!username) {
            return this.axiosInstance.get(`/api/user/services`).then(Utils.handleResponse<string, Service[]>);
        }
        return this.axiosInstance.get(`/api/user/${username}/services`).then(Utils.handleResponse<string, Service[]>)
    }

    async getAll2(): Promise<Result<string, Service[]>> {
        return this.axiosInstance.get(`/api/getAll2`).then(Utils.handleResponse<string, Service[]>);
    }

    async getById(service_id: number): Promise<Result<string, Service>> {
        return this.axiosInstance.get(`/api/services/${service_id}`).then(Utils.handleResponse<string, Service>);
    }

    async uploadImages(files: File[]): Promise<Result<string, string[]>> {
        let formData = new FormData();

        for (let i = 0; i < files.length; i++) {
            formData.append(`file${i + 1}`, files[i]);
        }

        return await this.axiosInstance.post('/api/services/upload_images', formData, {
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

    async create(service: CreateService): Promise<Result<string, Service>> {
        return await this.axiosInstance.post('/api/services/create', service).then(Utils.handleResponse<string, Service>);
    }
    async searchByTitle(query: string): Promise<Result<string, Service[]>> {
        return this.axiosInstance.get(`/api/services/search/${query}`).then(Utils.handleResponse<string, Service[]>);
    }
};
