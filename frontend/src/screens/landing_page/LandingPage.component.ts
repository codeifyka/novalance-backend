import { CardVue } from "@/components/landing_page/card";
import { FooterVue } from "@/components/landing_page/footer";
import { HeaderVue } from "@/components/landing_page/header";
import { QuestionAnswerVue } from "@/components/landing_page/question_answer";
import { ref, provide, onMounted } from 'vue'
export default {
    name: 'landing_page',
    components: {
        HeaderVue,
        CardVue,
        QuestionAnswerVue,
        FooterVue
    },
    setup() {
        const sectionRefHome = ref<HTMLElement | null>(null);
        const sectionRefServices = ref<HTMLElement | null>(null);
        const sectionRefAbout = ref<HTMLElement | null>(null);
        const sectionRefFaq = ref<HTMLElement | null>(null);

        onMounted(() => {
            sectionRefHome.value = document.getElementById('home');
            sectionRefServices.value = document.getElementById('services');
            sectionRefAbout.value = document.getElementById('about');
            sectionRefFaq.value = document.getElementById('faq');
        });

        provide('Sections', {
            sectionRefHome,
            sectionRefServices,
            sectionRefAbout,
            sectionRefFaq
        })
        return {
            sectionRefServices,
            sectionRefAbout,
            sectionRefFaq,
            show: false,
            cardContents: [
                {
                    icon: 'teenyicons:search-property-solid',
                    title: 'Search for the freelancer',
                    paragraph: 'Start now finding the perfect freelancer to achieve your vision and develop your business successfully. '
                },
                {
                    icon: 'fluent:gift-card-money-20-filled',
                    title: 'Trusted Transactions',
                    paragraph: 'Enjoy high-quality services from trusted professionals with a money-back guarantee in case of dissatisfaction. '
                },
                {
                    icon: 'eos-icons:service',
                    title: 'Offer your services',
                    paragraph: 'If you are a professional in your field, this is your opportunity to showcase your services and reach clients who need your expertise.'
                },
            ],
            whyContent: [
                {
                    icon: 'ic:baseline-verified',
                    title: 'Fast and reliable transactions',
                    paragraph: 'The site contains professional freelancers whose accounts are documented and verified before publishing their services to ensure safe and serious transactions.'
                },
                {
                    icon: 'ri:exchange-fill',
                    title: 'Refund',
                    paragraph: 'The site contains professional freelancers whose accounts are documented and verified before publishing their services to ensure safe and serious transactions.'
                },
                {
                    icon: 'ion:time',
                    title: 'Technical support around the clock',
                    paragraph: 'We are here to help you and respond to any problems you encounter at any time.'
                }
            ],
            frequentlyQst: [
                {
                    question: 'Is it free to join the platform?',
                    answer: 'Yes, joining our platform is completely free. You can sign up, create a profile, and browse available jobs or freelancers without any cost.'
                },
                {
                    question: 'How to find a freelancer?',
                    answer: 'To find a freelancer, simply use our search feature to filter by skills, experience, and ratings. You can also post a job listing and receive proposals from interested freelancers.'
                },
                {
                    question: 'How does payment work?',
                    answer: 'Payments are made through our secure payment system. Once you have agreed on the terms with a freelancer, you can fund the project. The payment will be held in escrow and released to the freelancer once you are satisfied with the work.'
                },
                {
                    question: 'What payment methods are available?',
                    answer: 'We support various payment methods including credit cards with chargily api like Cart El Dhahabiya and Ecib . You can choose the most convenient method for you when making a payment.'
                },
                {
                    question: 'I`m a freelancer, how are they looking for me?',
                    answer: 'Clients can find you by searching for freelancers based on skills, experience, and ratings. You can also increase your visibility by completing your profile and share projects and services, showcasing your portfolio, and actively applying for jobs that match your expertise.'
                },
            ]
        }
    },
}