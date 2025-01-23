import { defineComponent, ref } from 'vue';

export default defineComponent({
    props: {
        isOpen: {
            type: Boolean,
            required: true,
        },
        end_date: {
            type: String,
            required: true,
        }
    },
    emits: ['close', 'send'],
    setup(props, { emit }) {
        const expiryDate = ref(props.end_date.substring(0, 10) || null)
        const closeModal = () => {
            emit('close');
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
            closeModal,
            enter,
            leave,
            expiryDate,
        };
    },
});