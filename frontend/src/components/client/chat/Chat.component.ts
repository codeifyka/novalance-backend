import { ClientHeaderVue } from '@/components/client/header';
import { ref, onMounted, inject } from 'vue';
import RestChat from '@/libs/RestChat';
import { ClientChatRoomVue } from './chat_room';
import type { AxiosInstance } from 'axios';

export default {
  components: { ClientHeaderVue, ClientChatRoomVue },
  setup() {
    const axios = inject<AxiosInstance>('axios');
    const restChat = new RestChat(axios!);
    const freelancers = ref<Chat[]>([]);
    const currentUser = ref<Chat | null>(null);
    const chatScreenState = ref(true);
    const freelancerLastMessage = ref("");

    const getFreelancers = async () => {
      try {
        const response = await restChat.getUsers()
        if (response.isSuccess()) {
          freelancers.value = response.value!;
          currentUser.value = response.value![0];
        }
      } catch (err) {
        console.log(err)
      }
    }

    onMounted(() => {
      getFreelancers()
    });

    const ChangeCurrentUser = (chat_id: number) => {
      let user = freelancers.value.find(fr => fr.id == chat_id);
      if (user) {
        currentUser.value = user;
        toggleChatScreen();
      }
    }

    const handleMessage = (message: any) => {
      console.log('Message received from child:', message);
      freelancers.value.filter(freelancer => {
        if (freelancer.job_post_id == message.JobPostId && freelancer.freelancer_id == message.userId) {
          freelancerLastMessage.value = message.message
        }
      })
    }

    const toggleChatScreen = () => {
      chatScreenState.value = !chatScreenState.value;
    }


    return {
      freelancers,
      currentUser,
      ChangeCurrentUser,
      handleMessage,
      chatScreenState,
      toggleChatScreen,
      freelancerLastMessage
    };
  },
};
