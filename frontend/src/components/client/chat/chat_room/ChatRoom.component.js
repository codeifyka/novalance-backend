import { ref, onMounted, inject, nextTick } from 'vue';
import RestChat from '@/libs/RestProposals';

export default {
  props:{
    freelancer: {
      type: Object,
      required: true,
    },
    job_post: Object
  },
  setup() {
    const ws = inject('ws');
    const userId = ref(null); // user id ta3i
    const channelId = 'some-channel-id';//id howa ta3i + ta3 sahbi li nchati m3ah
    const messages = ref([]);
    const newMessage = ref('');
    const messagesRef = ref(null);
    const axios = inject('axios');
    const connect = () => {
      userId.value = Math.random().toString(36).substring(2);
      ws.send(JSON.stringify({ type: 'connect', userId: userId.value, channelId }));
    };
    const sendMessage = () => {
      if (newMessage.value.trim() !== '') {
        ws.send(JSON.stringify({ type: 'message', userId: userId.value, message: newMessage.value, channelId }));
        newMessage.value = '';
      }
    };

    onMounted(() => {
      ws.onmessage = (event) => {
        const message = JSON.parse(event.data);
        console.log(message)
        messages.value.push(...message.value,message);
        if (message.channelId === channelId) {
          nextTick(() => {
            messagesRef.value.scrollTop = messagesRef.value.scrollHeight;
          });
        }
      };

      connect();
    });

    return {
      userId,
      messages,
      newMessage,
      sendMessage,
      messagesRef,
    };
  },
};
