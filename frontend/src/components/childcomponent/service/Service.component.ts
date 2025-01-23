import { defineComponent } from 'vue';

export default defineComponent({
    props: {
        service: {
            type: Object as () => Service,
        }
    },
    setup(props) {
        const service = props.service;

        return { service }
    }
});
