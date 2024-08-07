import { ref, onMounted, inject, nextTick } from 'vue';
import RestChat from '@/libs/RestChat';
import RestClientJobs from '@/libs/RestClientJobs'; 
import RestUserSession from '@/libs/RestUserSession';
export default {
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

    return {
      messages,
      newMessage,
      sendMessage,
      messagesRef,
      JobPost,
      user,
      isLoading
    };
  },
};
