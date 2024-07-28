import { ref ,inject ,onMounted} from 'vue';
import RestClientJobs from '@/libs/RestClientJobs'
import RestProposals from '@/libs/RestProposals';
import { ModalVue } from './modal';
export default {
    props: {
        proposal: Object,
    },
    components: {
        ModalVue
    },
    setup({proposal}){
        const axios = inject('axios');
        let account_type = ref(null);
        const freelancer = ref(null);
        const restJob = new  RestClientJobs(axios)
        const restClientProposals = new  RestProposals(axios)
        var isModalOpen = ref(false);

        const getJobPost = async ()  =>  {
            let response = await restJob.getById(proposal.job_post_id)
            if(response.data){
                proposal.job_title = response.data.title
            }
        }

        const getFreelancer = async ()  =>  {
            let response = await restClientProposals.getFreelancerInfo(proposal.freelancer_id)
            if(response.data){
                proposal.freelancer_image = response.data.username
                freelancer.value = response.data
            }
        }

        const getFirstLetter = (name) => {
            if (name && typeof name === 'string') {
                return name.charAt(0);
            }
            return '';
        }

        const openModal = () => {
            isModalOpen.value = true;
        }

        onMounted( () => {
            getJobPost()
            getFreelancer()
        });
        const menuValue = ref(false)
        function menu(){
            menuValue.value=!menuValue.value
        }

        if (proposal.created_at) {
            let [date, time] = proposal.created_at.split("T");
            proposal.created_day = date;
            proposal.created_time = time.split("Z")[0];
            delete proposal.created_at;
        } else {
            console.error('created_at is undefined for a proposal:', proposal);
        }
        return {  menu, menuValue, account_type, proposal, getFirstLetter, isModalOpen, openModal, freelancer}
    }
}
