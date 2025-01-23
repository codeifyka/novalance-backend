import { defineComponent, ref } from 'vue';

export default defineComponent({
    props: {
        isOpen: {
            type: Boolean,
            required: true,
        },
    },
    emits: ['close', 'send'],
    setup(_, { emit }) {
        const coverLetter = ref('');

        const closeModal = () => {
            emit('close');
        };

        const sendProposal = () => {
            emit('send', coverLetter.value);
            closeModal();
        };

        const enter = (el: HTMLElement, done: () => void) => {
            const animation = el.animate([
                { opacity: 0, transform: 'scale(0.9)' },
                { opacity: 1, transform: 'scale(1)' }
            ], {
                duration: 300,
                easing: 'ease-out'
            });

            animation.onfinish = done;
        };

        const leave = (el: HTMLElement, done: () => void) => {
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
            coverLetter,
            closeModal,
            sendProposal,
            enter,
            leave,
        };
    },
});