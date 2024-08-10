import { ref, onMounted, inject, nextTick } from 'vue';
import RestChat from '@/libs/RestChat';
import RestClientJobs from '@/libs/RestClientJobs'; 
import RestUserSession from '@/libs/RestUserSession';
import { ModalVue } from './modal';
export default {
  components: {
    ModalVue
  },
  props: {
    freelancer: {
      required: true,
    },
  },
  setup({ freelancer }) {
    const ws = inject('ws'),
    channelId = freelancer.freelancer.id,
    messages = ref([]),
    newMessage = ref(''),
    messagesRef = ref(null),
    isLoading = ref(false),
    axios = inject('axios');
    const resClientJobPost = new RestClientJobs(axios);
    const JobPost = ref(null);
    let user = ref(null);
    const restChat = new RestChat(axios)
    const menuIsShow = ref(false)
    const isModalOpen = ref(false)
    const toastManager = inject("toastManager")

    const connect = () => {
      ws.send(JSON.stringify({ type: 'connect', userId: user.value.id, channelId }));
    };

    const sendMessage = async () => {
      if (newMessage.value.trim() !== '') {
        ws.send(JSON.stringify({ type: 'message', userId: user.value.id, message: newMessage.value, channelId }));
        const res = await restChat.storeMessage(newMessage.value, freelancer.id)
        if(res.data){
          newMessage.value = '';
          messages.value.push(res.data);
          console.log(messagesRef)
          messagesRef.value.scrollTop = messagesRef.value.scrollHeight;
        }
      }
    };

    const getJobPostById = async () => {
      try {
        const response = await resClientJobPost.getById(freelancer.job_post_id);
        if (response.data) {
          JobPost.value = response.data;
        }
      } catch (error) {
        console.error('Error fetching job post:', error);
      }
    };

    const getAllMessages = async () => {
      try{
        isLoading.value = true;
        const res = await restChat.getAllMessages(freelancer.id);
        if(res.data){
          messages.value = res.data;
          console.log(messages.value)
        }
      }catch (err){
        console.log(err)
      }finally{
        isLoading.value = false;
        nextTick(() => {
          if (messagesRef.value) {
            messagesRef.value.scrollTop = messagesRef.value.scrollHeight;
          }
        });
      }
    }

    const openModal = () => {
      isModalOpen.value = true;
    }

    onMounted(async () => {
      let restUserSession = new RestUserSession(axios);
      user.value = (await restUserSession.getInfo()).data.user;
      console.log(user.value)
      connect();
      getAllMessages()
    });

    onMounted(() => {
      ws.onmessage = (event) => {
        try {
          const message = JSON.parse(event.data);
          console.log('Received message:', message);
          if (message.channelId === user.value.id) {
            const  date = new Date();
            const  Isosdate = date.toISOString()
            message.created_at = Isosdate
            messages.value = Array.isArray(messages.value) ? [...messages.value, message] : [message];
            nextTick(() => {
              if (messagesRef.value) {
                messagesRef.value.scrollTop = messagesRef.value.scrollHeight;
              }
            });
          }
        } catch (error) {
          console.error('Error parsing message:', error);
        }
      };

      if (freelancer) {
        getJobPostById();
      }
    });

    const handleSend = async (end_date) =>{
      try{
          isLoading.value = true;
          console.log('expiry date Sent:', end_date);
          let response = await restChat.setExpiryDate(freelancer.id,end_date);
          if(response){
              toastManager.value.alertSuccess("Set expiry date successfuly.");
              isModalOpen.value = false;
              isLoading.value = false;
          }
      }catch(error){
          isLoading.value = false;
          console.log(error);
          toastManager.value.alertError(error);
      }
  }

    return {
      messages,
      newMessage,
      sendMessage,
      messagesRef,
      JobPost,
      user,
      isLoading,
      menuIsShow,
      openModal,
      isModalOpen,
      handleSend
    };
  },
};
