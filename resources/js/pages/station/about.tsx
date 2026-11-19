import { usePage } from '@inertiajs/react';
import { ContentPage } from '@/components/station/content-page';
import type { StationPageProps } from '@/types';

export default function StationAbout() {
    const { station } = usePage<StationPageProps>().props;

    return (
        <ContentPage
            heading={`About ${station.name}`}
            emptyText="Our story is being written. Check back soon."
        />
    );
}
