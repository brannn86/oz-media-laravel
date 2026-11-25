import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import BasicStationLayout from '@/layouts/BasicStationLayout';
import StationLayout from '@/layouts/StationLayout';
import { setupEcho } from '@/lib/echo';

setupEcho();

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    // Public pages send complete titles ("Programs | OZ Bandung"); app pages get the suffix.
    title: (title) =>
        !title
            ? appName
            : title.includes(' | ')
              ? title
              : `${title} - ${appName}`,
    layout: (name) => {
        switch (true) {
            case name.startsWith('corporate/'):
                return null;
            // Persistent: the player inside survives navigation between station pages.
            case name.startsWith('station/'):
                return StationLayout;
            // A basic station site (radio only), persistent for the same reason.
            case name.startsWith('basic/'):
                return BasicStationLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
