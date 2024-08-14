import { ref, onMounted, inject, nextTick, toRefs, watch } from 'vue';
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
  setup( props, { emit } ) {
    const ws = inject('ws'),
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
    const { freelancer } = toRefs(props);

    const connect = () => {
      ws.send(JSON.stringify({ type: 'connect', userId: user.value.id, channelId: freelancer.value.freelancer.id, JobPostId: JobPost.value?.id }));
    };

    const sendMessage = async () => {
      if (newMessage.value.trim() !== '') {
        ws.send(JSON.stringify({ type: 'message', userId: user.value.id, message: newMessage.value, channelId: freelancer.value.freelancer.id, JobPostId: JobPost.value.id }));
        const res = await restChat.storeMessage(newMessage.value, freelancer.value.id)
        if(res.data){
          newMessage.value = '';
          messages.value.push(res.data);
          messagesRef.value.scrollTop = messagesRef.value.scrollHeight;
        }
      }
    };

    const getJobPostById = async () => {
      try {
        const response = await resClientJobPost.getById(freelancer.value.job_post_id);
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
        const res = await restChat.getAllMessages(freelancer.value.id);
        if(res.data){
          messages.value = res.data;
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

    watch(freelancer, async(newFreelancer) => {
      if (newFreelancer) {
        let restUserSession = new RestUserSession(axios);
        user.value = (await restUserSession.getInfo()).data.user;  
        // Ensure JobPost and messages are loaded before connecting
        await getJobPostById();
        await getAllMessages();
        connect()
      }
    }, { immediate: true });

    onMounted(async () => {
      let restUserSession = new RestUserSession(axios);
      user.value = (await restUserSession.getInfo()).data.user;
      connect();
      getAllMessages()
    });

    onMounted(() => {
      ws.onmessage = (event) => {
        try {
          const message = JSON.parse(event.data);
          console.log('Received message:', message);
          if (message.channelId === user.value.id && message.JobPostId == JobPost.value.id) {
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
          emit('received-message', message);
        } catch (error) {
          console.error('Error parsing message:', error);
        }
      };

      if (freelancer.value) {
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
