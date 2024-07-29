import { FreeLancerHeaderVue } from '@/components/freelancer/header';
import RestClientJobs from "@/libs/RestClientJobs";
import RestProposals from "@/libs/RestProposals";
import { SliderVue } from "@/components/slider";
import { inject, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { JobPostVue } from '@/components/childcomponent/job_post';
import { ModalVue } from '@/components/modal';

export default {
    components: { 
        FreeLancerHeaderVue,
        SliderVue,
        JobPostVue,
        ModalVue
    },
    setup(){
        const isLoading = ref(false);
        let toastManager = inject("toastManager");
        const colors = ref({
            'text':'text-purple-700',
            'background':"bg-violet-50/60",
        })
        const isSent = ref(false)
        var isModalOpen = ref(false);
        let route = useRoute();
        const axios = inject('axios');
        const restClientJobs = new RestClientJobs(axios);
        const restProposals = new RestProposals(axios);
        let job = ref([])

        onMounted(async () => {
            let response = await restClientJobs.getById(route.params.id);
            if(response.data){
                job.value = response.data;
            }
        });

        onMounted(async () => {
            let response = await restProposals.getByFreelancerJobId(route.params.id);
            console.log(response)
            if(response){
                isSent.value = true;
            }
        })

        const openModal = () => {
            isModalOpen.value = true;
        }

        const handleSend = async (coverLetter) =>{
            try{
                isLoading.value = true;
                console.log('Cover Letter Sent:', coverLetter);
                let response = await restProposals.create({
                    job_post_id:route.params.id,
                    cover_letter:coverLetter,
                });
                if(response){
                    toastManager.value.alertSuccess("Sent proposal successfuly.");
                    isModalOpen.value = false;
                    isSent.value = true;
                }
            }catch(error){
                isLoading.value = false;
                console.log(error);
                toastManager.value.alertError(error);
            }
        }

        return { 
            job,colors,isModalOpen,
            openModal,
            handleSend,
            isSent,
            isLoading
        };
    }
}
