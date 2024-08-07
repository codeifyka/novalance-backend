import { ref, onMounted, inject, nextTick } from 'vue';
import RestChat from '@/libs/RestChat';
import RestClientJobs from '@/libs/RestClientJobs'; 
import RestUserSession from '@/libs/RestUserSession';
export default {
  props: {
    client: {
      required: true,
    },
  },
  setup({ client }) {
    const ws = inject('ws'),
    channelId = client.client.id,
    messages = ref([]),
    newMessage = ref(''),
    messagesRef = ref(null),
    axios = inject('axios');
    const resClientJobPost = new RestClientJobs(axios);
    const JobPost = ref(null);
    const user = ref(null);
    const restChat = new RestChat(axios)
    const connect = () => {
      ws.send(JSON.stringify({ type: 'connect', userId: user.value.id, channelId }));
    };

    const sendMessage = async () => {
      if (newMessage.value.trim() !== '') {
        ws.send(JSON.stringify({ type: 'message', userId: user.value.id, message: newMessage.value, channelId }));
        const res = await restChat.storeMessage(newMessage.value, client.id)
        if(res.data){
          newMessage.value = '';
          messages.value.push(res.data);
          messagesRef.value.scrollTop = messagesRef.value.scrollHeight;
        }
      }
    };

    const getJobPostById = async () => {
      try {
        const response = await resClientJobPost.getById(client.job_post_id);
        if (response.data) {
          JobPost.value = response.data;
        }
      } catch (error) {
        console.error('Error fetching job post:', error);
      }
    };

    const getAllMessages = async () => {
      try{
        const res = await restChat.getAllMessages(client.id);
        if(res.data){
          messages.value = res.data;
          nextTick(() => {
            if (messagesRef.value) {
              messagesRef.value.scrollTop = messagesRef.value.scrollHeight;
            }
          });
        }
      }catch (err){
        console.log(err)
      }
    }

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
          if (message.channelId === user.value.id) {
            const  date = new Date();
            const  Isosdate = date.toISOString()
            message.created_at = Isosdate
            messages.value =[...messages.value, message] ;
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

      if (client) {
        getJobPostById();
      }
    });

    return {
      messages,
      newMessage,
      sendMessage,
      messagesRef,
      JobPost,
      user
    };
  },
};
