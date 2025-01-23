import { FreeLancerHeaderVue } from '@/components/freelancer/header';
import { JobPostVue } from '@/components/childcomponent/job_post';
import { inject, onMounted, ref } from 'vue';
import RestChat from '@/libs/RestChat';
import { ChatRoomVue } from './chat_room';
import type { AxiosInstance } from 'axios';

export default {
    components: { FreeLancerHeaderVue, JobPostVue, ChatRoomVue },
    setup() {
        const colors = ref({
            'text': 'text-purple-700',
            'background': "bg-violet-50/60",
        })
        const isLoading = ref(false);
        const axios = inject<AxiosInstance>("axios");
        const restChat = new RestChat(axios!);
        const clients = ref<Chat[]>([]);
        const currentUser = ref<Chat | null>(null);
        const chatScreenState = ref(true);
        const clientLastMessage = ref("");

        const getClients = async () => {
            try {
                const response = await restChat.getUsers()
                if (response.isSuccess()) {
                    clients.value = response.value!;
                    currentUser.value = response.value![0]
                }
            } catch (err) {
                console.log(err)
            }
        }

        const ChangeCurrentUser = (chat_id: number): void => {
            currentUser.value = clients.value.find(fr => fr.id == chat_id) || null;
            toggleChatScreen();
        }

        const handleMessage = (message: any) => {
            clients.value.filter(client => {
                if (client.job_post_id == message.JobPostId && client.client_id == message.userId) {
                    clientLastMessage.value = message.message;
                }
            })
        }

        const toggleChatScreen = () => {
            chatScreenState.value = !chatScreenState.value
        }

        onMounted(async () => {
            getClients()
        });

        return {
            colors,
            isLoading,
            clients,
            currentUser,
            ChangeCurrentUser,
            handleMessage,
            chatScreenState,
            toggleChatScreen,
            clientLastMessage
        };

    }
}
