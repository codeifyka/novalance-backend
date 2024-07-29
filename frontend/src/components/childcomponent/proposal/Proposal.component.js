import { ref ,inject ,onMounted} from 'vue';
import RestClientJobs from '@/libs/RestClientJobs'
import RestProposals from '@/libs/RestProposals';
import { ModalVue } from './modal';
export default {
    props: {
        proposal: Object,
        status: 'pending' | 'active' | 'done' 
    },
    components: {
        ModalVue,
    },
    setup({proposal},  { emit }){
        const axios = inject('axios');
        let toastManager = inject("toastManager");
        let account_type = ref(null);
        const freelancer = ref(null);
        const restJob = new  RestClientJobs(axios)
        const restClientProposals = new  RestProposals(axios)
        const isModalOpen = ref(false);
        const isTruncated = ref(true)
        const isLoading = ref(false);
        const menuValue = ref(false)

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

        function menu(){
            menuValue.value=!menuValue.value
        }

        if (proposal.created_at) {
            let [date, time] = proposal.created_at.split("T");
            proposal.created_day = date;
            proposal.created_time = time.split("Z")[0];
            delete proposal.created_at;
        }if (proposal.started_at) {
            let [date, time] = proposal.started_at.split(" ");
            proposal.started_day = date;
            proposal.started_time = time
            delete proposal.started_at;
        } else {
            console.error('created_at is undefined for a proposal:', proposal);
        }

        if(proposal.ends_at) {
            let [date, time] = proposal.ends_at.split(" ");
            proposal.ends_day = date;
            proposal.ends_time = time
        }

        const toggleTruncate = () => {
            isTruncated.value = !isTruncated.value;
        }

        const acceptProposal = () => {
            try{
                isLoading.value = true
                const response = restClientProposals.acceptProposal(proposal.id)
                if (response.data) {
                    toastManager.value.alertSuccess("Accept proposal successfuly. Now you can send message to freelancer");
                    emit('proposalAccepted', props.proposal.id);
                    isLoading.value = false
                }
            }catch(err){
                isLoading.value = false;
                console.log(err);
                toastManager.value.alertError(err);
            }
        }

        const deleteProposal = () => {
            try{
                isLoading.value = true
                const response = restClientProposals.deleteProposal(proposal.id)
                if (response.data) {
                    toastManager.value.alertSuccess("Delete proposal successfuly. Now you can send message to freelancer");
                    emit('proposalDeleted', props.proposal.id);
                    isLoading.value = false
                }
            }catch(err){
                isLoading.value = false;
                console.log(err);
                toastManager.value.alertError(err);
            }
        }
        
        return {  menu, menuValue, toggleTruncate, isTruncated, account_type, proposal, getFirstLetter, isModalOpen, openModal, freelancer, acceptProposal, isLoading, deleteProposal}
    }
}
