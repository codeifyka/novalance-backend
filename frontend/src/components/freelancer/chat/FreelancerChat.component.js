import { FreeLancerHeaderVue } from '@/components/freelancer/header';
import { JobPostVue } from '@/components/childcomponent/job_post';
import RestUserSession from '@/libs/RestUserSession';
import RestClientJobs from "@/libs/RestClientJobs";
import { inject, onMounted, ref, nextTick } from 'vue';
import RestChat from '@/libs/RestChat';
import { ChatRoomVue } from './chat_room';

export default {
    components: { FreeLancerHeaderVue, JobPostVue, ChatRoomVue },
    setup() {
        const colors = ref({
            'text': 'text-purple-700',
            'background': "bg-violet-50/60",
        })
        const isLoading = ref(false);
        const axios = inject("axios");
        const restChat = new RestChat(axios)
        const clients = ref(null)
        const currentUser = ref(null)
    
        const getClients = async () => {
            try {
                const response = await restChat.getUsers()
                if (response.data) {
                    clients.value = response.data
                    currentUser.value = response.data[0]
                }
            } catch (err) {
                console.log(err)
            }
        }

        onMounted(() => {
            getClients()
        });

        return {
            colors, isLoading, clients, currentUser
        };

    }
}
