import { ClientHeaderVue } from '@/components/client/header';
import { ref, onMounted, inject, nextTick } from 'vue';
import RestChat from '@/libs/RestChat';
import { ClientChatRoomVue } from './chat_room';

export default {
  components: { ClientHeaderVue, ClientChatRoomVue },
  setup() {
    const axios = inject('axios');
    const restChat = new RestChat(axios)
    const freelancers = ref(null)
    
    const getFreelancer = async () => {
      try{
        const response = await restChat.getFreelancers()
        console.log("first")
        console.log(response)
      if(response.data){
        freelancers.value = response.data
        
      }
    }catch(err){
      console.log(err)
    }
    }
    
    onMounted(() => {
      getFreelancer()
    });

    return {
      freelancers
    };
  },
};
