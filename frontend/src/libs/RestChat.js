export default class RestChat {
    constructor(axiosInstance){
        this.axiosInstance = axiosInstance;
    }

    async getFreelancers(){
        return this.axiosInstance.get(`/api/chats/getAll`).then(response => response.data);
    }       
};
