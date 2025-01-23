import logo from '@/assets/images/logo.png';
import { inject, onMounted, ref, onBeforeUnmount, defineComponent, type Ref } from 'vue';

export default defineComponent({
    setup() {
        const sections = inject<{
            sectionRefHome: Ref<HTMLElement | null>;
            sectionRefServices: Ref<HTMLElement | null>;
            sectionRefAbout: Ref<HTMLElement | null>;
            sectionRefFaq: Ref<HTMLElement | null>;
        }>('Sections');
        const { sectionRefHome, sectionRefServices, sectionRefAbout, sectionRefFaq } = sections!;
        const navbarRef = ref(null);
        const scrollNavbar = ref(false);
        const showMobileMenu = ref(false);

        const handleScroll: IntersectionObserverCallback = (entries) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) {
                    scrollNavbar.value = true;
                } else {
                    scrollNavbar.value = false;
                }
            });
        };

        let observer: IntersectionObserver;

        onMounted(() => {
            observer = new IntersectionObserver(handleScroll, {
                root: null,
                threshold: 0
            });

            if (navbarRef.value) {
                observer.observe(navbarRef.value);
            }
        });

        onBeforeUnmount(() => {
            if (observer && navbarRef.value) {
                observer.unobserve(navbarRef.value);
            }
        });

        function scrollTo(view: HTMLDivElement) {
            if (view && view.scrollIntoView) {
                view.scrollIntoView({ behavior: 'smooth' });
            } else {
                console.warn('Element not found or not scrollable:', view);
            }
        }

        function toggleMenu() {
            showMobileMenu.value = !showMobileMenu.value;
        }

        return {
            logo,
            scrollNavbar,
            navbarRef,
            sectionRefHome,
            sectionRefServices,
            sectionRefAbout,
            sectionRefFaq,
            scrollTo,
            showMobileMenu,
            toggleMenu
        };
    }
});
