import { ref } from 'vue';
import { useTransition } from '@vueuse/core';

export default {
    props: {
        isOpen: {
            type: Boolean,
            required: true,
        },
        end_date: {
            type: String,
        }
    },
    emits: ['close', 'send'],
    setup(props, { emit }) {
        const expiryDate = ref(props.end_date.substring(0,10) || null)
        const closeModal = () => {
            emit('close');
        };

        const save = () => {
            emit('send', expiryDate.value);
            closeModal();
        };

        const enter = (el, done) => {
            const animation = el.animate([
                { opacity: 0, transform: 'scale(0.9)' },
                { opacity: 1, transform: 'scale(1)' }
            ], {
                duration: 300,
                easing: 'ease-out'
            });

            animation.onfinish = done;
        };

        const leave = (el, done) => {
            const animation = el.animate([
                { opacity: 1, transform: 'scale(1)' },
                { opacity: 0, transform: 'scale(0.9)' }
            ], {
                duration: 300,
                easing: 'ease-in'
            });

            animation.onfinish = done;
        };

        return {
            closeModal,
            save,
            enter,
            leave,
            expiryDate,
        };
    },
};