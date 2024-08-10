export default class RestChat {
    constructor(axiosInstance){
        this.axiosInstance = axiosInstance;
    }

    async getUsers(){
        return this.axiosInstance.get(`/api/chats/getAll`).then(response => response.data);
    }

    async storeMessage(message,chat_id){
        return this.axiosInstance.post(`/api/chats/storeMessage`,{message,chat_id}).then(response => response.data);
    }

    async getAllMessages(chat_id){
        return this.axiosInstance.post(`/api/chats/getAllMessages`,{chat_id}).then(response => response.data);
    }

    async setExpiryDate(chat_id, end_date){
        return this.axiosInstance.post(`/api/chats/setExpiryDate`,{chat_id,end_date}).then(response => response.data);
    }
};
