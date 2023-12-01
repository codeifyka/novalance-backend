export default class RestClientJobs{
    constructor(axiosInstance){
        this.axiosInstance = axiosInstance;
    }

    async getAll(){
        return this.axiosInstance.get('/api/job/').then(Response => Response.data)
    }

    async getById(id){
        return this.axiosInstance.get(`/api/job/${id}`).then(Response => Response.data)
    }

    async create(job){
        return this.axiosInstance.post('/api/job/store',job).then(Response => Response.data)
    }

    async update(id){
        return this.axiosInstance.post(`/api/job/update/${id}`).then(Response => Response.data)
    }

    async delete(id){
        return this.axiosInstance.post(`/api/job/delete/${id}`).then(Response => Response.data)
    }
}