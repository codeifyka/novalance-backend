import { defineComponent, onMounted, ref } from 'vue';
import RestFreelancerProjects from '@/libs/RestFreelancerProjects';
import { inject } from 'vue';
import type { AxiosInstance } from 'axios';

export default defineComponent({
    props: {
        isOpen: {
            type: Boolean,
            required: true,
        },
        freelancer: {
            type: Object,
            required: true,
        }
    },
    emits: ['close'],
    setup({ freelancer }, { emit }) {
        const closeModal = () => {
            emit('close');
        };

        const axios = inject<AxiosInstance>('axios');
        const restFreelancerProjects = new RestFreelancerProjects(axios!);
        const projects = ref<Project[]>([]);

        onMounted(async () => {
            if (!freelancer) {
                console.error('Freelancer data is null or undefined.');
                return;
            }
            try {
                const response = await restFreelancerProjects.getAll(freelancer.username);
                projects.value = response.value!;
                console.log(projects.value);
            } catch (err) {
                console.log(err);
            }
        });

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
            projects
        };
    },
});
