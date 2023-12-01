import { ref } from 'vue';
export default {
    props: {
        job: Object,
    },
    setup(props){
        const job = ref(props.job)
        return { job }
    }
}
