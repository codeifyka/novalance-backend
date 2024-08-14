import { FreeLancerHeaderVue } from '@/components/freelancer/header';
import { JobPostVue } from '@/components/childcomponent/job_post';
import RestUserSession from '@/libs/RestUserSession';
import { inject, onMounted, ref } from 'vue';
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

        const ChangeCurrentUser = (chat_id) => {
            currentUser.value = clients.value.find(fr => fr.id == chat_id);
        }

        const handleMessage = (message) => {
            clients.value.filter(client => {
                if(client.job_post_id == message.JobPostId && client.client_id == message.userId){
                    client.last_message.message = message.message
                    console.log(client)
                }
            })
        }      

        onMounted(async() => {
            getClients()
        });

        return {
            colors,
            isLoading,
            clients,
            currentUser,
            ChangeCurrentUser,
            handleMessage
        };

    }
}
