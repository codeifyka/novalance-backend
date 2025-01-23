import type { AxiosInstance } from "axios";
import Result from "./Result";
import Utils from "./Utils";

export default class RestChat {
    axiosInstance: AxiosInstance;
    constructor(axiosInstance: AxiosInstance) {
        this.axiosInstance = axiosInstance;
    }

    async getUsers(): Promise<Result<string, Chat[]>> {
        return this.axiosInstance.get(`/api/chats/getAll`)
            .then(Utils.handleResponse<string, Chat[]>);
    }

    async storeMessage(message: string, chat_id: number): Promise<Result<string, Message>> {
        return this.axiosInstance.post(`/api/chats/storeMessage`, { message, chat_id })
            .then(Utils.handleResponse<string, Message>);
    }

    async getAllMessages(chat_id: number): Promise<Result<string, Message[]>> {
        return this.axiosInstance.post(`/api/chats/getAllMessages`, { chat_id }).then(Utils.handleResponse<string, Message[]>);
    }

    async setExpiryDate(chat_id: number, end_date: Date): Promise<Result<string, Chat>> {
        return this.axiosInstance.post(`/api/chats/setExpiryDate`, { chat_id, end_date }).then(Utils.handleResponse<string, Chat>);
    }
};
