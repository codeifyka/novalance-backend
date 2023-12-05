export default class RestFreelancerServices {
    constructor(axiosInstance){
        this.axiosInstance = axiosInstance;
    }

    async getAll(username){
        if(!username){
            return this.axiosInstance.get(`/api/user/services`).then(response => response.data);
        }
        return this.axiosInstance.get(`/api/user/${username}/services`).then(response => response.data);
    }

    async getById(service_id){
        return this.axiosInstance.get(`/api/services/${service_id}`).then(response => response.data);
    }

    async uploadImages(files){
        let formData = new FormData();

        for(let i = 0; i < files.length; i++){
            formData.append(`file${i + 1}`, files[i]);
        }

        return await this.axiosInstance.post('/api/services/upload_images', formData, {
            headers: {
                'Content-Type': 'multipart/form-data', // Important for file uploads
            },
            onUploadProgress: progressEvent => {
                const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                console.log(percentCompleted);
                // You can update a progress bar or do something with the progress here
            }
        }).then(response => response.data);
    }

    async create(service){
        return await this.axiosInstance.post('/api/services/create', service).then(response => response.data);
    }
};
