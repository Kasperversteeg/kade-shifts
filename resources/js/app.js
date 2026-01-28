import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { createI18n } from 'vue-i18n';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import en from './lang/en.json';
import nl from './lang/nl.json';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Apply saved theme before render to prevent flash
document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'light');

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const locale = props.initialPage.props.locale || 'en';

        const i18n = createI18n({
            legacy: false,
            locale,
            fallbackLocale: 'en',
            messages: { en, nl },
        });

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
