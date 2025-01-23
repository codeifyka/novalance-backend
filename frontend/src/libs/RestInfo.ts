import type { AxiosInstance } from "axios";
import Utils from "./Utils";
import type Result from "./Result";

interface Info {
    status: string;
    message: string;
}

export default class RestInfo {
    axiosInstance: AxiosInstance;

    constructor(axiosInstance: AxiosInstance) {
        this.axiosInstance = axiosInstance;
    }

    async getInfo(): Promise<Result<string, Info>> {
        return this.axiosInstance.get('/api/info').then(Utils.handleResponse<string, Info>);
    }
};
