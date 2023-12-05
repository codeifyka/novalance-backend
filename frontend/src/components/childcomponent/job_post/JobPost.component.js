import { ref ,inject  ,defineComponent} from 'vue';
import RestClientJobs from "@/libs/RestClientJobs";

export default {
    props: {
        job: Object,
        colors: Object,
    },
    setup({ job, colors ,},{ emit }){
        const axios = inject('axios');
        
        const menuValue = ref(false)
        function menu(){
            menuValue.value=!menuValue.value
        }
        const  deleteJob = async() =>{
            let restClientJobs = new RestClientJobs(axios)
            let response = await  restClientJobs.delete(job.id)
            emit('remove-job', job.id);        
        }
        return { job ,colors, menu, menuValue  ,deleteJob , }
    }
}
