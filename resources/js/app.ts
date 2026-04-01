import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, type DefineComponent } from 'vue';
import { createI18n } from 'vue-i18n';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import en from './lang/en.json';
import nl from './lang/nl.json';

const appName: string = import.meta.env.VITE_APP_NAME || 'Laravel';
const supportedLocales = ['en', 'nl'] as const;

function detectBrowserLocale(): string {
    const browserLang = navigator.language?.split('-')[0];
    return supportedLocales.includes(browserLang as typeof supportedLocales[number])
        ? browserLang
        : 'nl';
}

// Apply saved theme before render to prevent flash
document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'light');

createInertiaApp({
    title: (title: string) => `${title} - ${appName}`,
    resolve: (name: string) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const locale = (props.initialPage.props.locale as string) || detectBrowserLocale();

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
