import { FreeLancerHeaderVue } from '@/components/freelancer/header';
import { JobPostVue } from '@/components/childcomponent/job_post';
import{ref} from 'vue'
export default {
    components: { FreeLancerHeaderVue , JobPostVue},
    setup(){
        const colors = ref({
            'text':'text-purple-700',
            'background':"bg-purple-50/60",
        })
        return {colors }
    }
}
