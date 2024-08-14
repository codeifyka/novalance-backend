import { ClientHeaderVue } from '@/components/client/header';
import { ref, onMounted, inject } from 'vue';
import RestChat from '@/libs/RestChat';
import { ClientChatRoomVue } from './chat_room';
import RestUserSession from '@/libs/RestUserSession';

export default {
  components: { ClientHeaderVue, ClientChatRoomVue },
  setup() {
    const axios = inject('axios');
    const restChat = new RestChat(axios)
    const freelancers = ref(null)
    const currentUser = ref(null)

    const getFreelancers = async () => {
      try{
        const response = await restChat.getUsers()
        if(response.data){
          freelancers.value = response.data
          currentUser.value = response.data[0]
        }
      }catch(err){
        console.log(err)
      }
    }
    
    onMounted(() => {
      getFreelancers()
    });

    const ChangeCurrentUser = (chat_id) => {
      currentUser.value = freelancers.value.find(fr => fr.id == chat_id);
    }

    const handleMessage = (message) => {
      console.log('Message received from child:', message);
      freelancers.value.filter(freelancer => {
        if(freelancer.job_post_id == message.JobPostId && freelancer.freelancer_id == message.userId){
          freelancer.last_message.message = message.message
        }
      })
    }

    return {
      freelancers,
      currentUser,
      ChangeCurrentUser,
      handleMessage
    };
  },
};
