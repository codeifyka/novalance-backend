export default class RestFreelancerProjects {
    constructor(axiosInstance){
        this.axiosInstance = axiosInstance;
    }

    async getAll(username){
        if(!username){
            return this.axiosInstance.get(`/api/user/projects`).then(response => response.data);
        }
        return this.axiosInstance.get(`/api/user/${username}/projects`).then(response => response.data);
    }

    async getById(project_id){
        return this.axiosInstance.get(`/api/projects/${project_id}`).then(response => response.data);
    }

    async uploadImages(files){
        let formData = new FormData();

        for(let i = 0; i < files.length; i++){
            formData.append(`file${i + 1}`, files[i]);
        }

        return await this.axiosInstance.post('/api/projects/upload_images', formData, {
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

    async create(project){
        return await this.axiosInstance.post('/api/projects/create', project).then(response => response.data);
    }
};
