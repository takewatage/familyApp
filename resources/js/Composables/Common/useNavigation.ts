import { router } from '@inertiajs/vue3';

export const useNavigation = () => {
    const navigateToHome = () => {
        router.visit(route('home'));
    };

    const navigateToDok = () => {
        router.visit(route('dok'));
    };

    const navigateTo = (routeName: string, params?: Record<string, any>) => {
        router.visit(route(routeName, params));
    };

    return {
        navigateToHome,
        navigateToDok,
        navigateTo,
    };
};
