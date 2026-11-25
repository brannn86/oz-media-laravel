/**
 * Suggested listener locations: every city and regency where OZ broadcasts
 * (Jawa Barat, Bali) and its neighbours (Jakarta, Banten), then the other
 * big cities. Listeners can type anything, but picking one of these keeps
 * the audience-by-area numbers clean.
 */
const AREAS: Record<string, string[]> = {
    'Jawa Barat': [
        'Kota Bandung',
        'Kabupaten Bandung',
        'Kabupaten Bandung Barat',
        'Kota Cimahi',
        'Kabupaten Sumedang',
        'Kabupaten Garut',
        'Kota Bekasi',
        'Kabupaten Bekasi',
        'Kota Bogor',
        'Kabupaten Bogor',
        'Kota Depok',
        'Kabupaten Karawang',
        'Kabupaten Purwakarta',
        'Kabupaten Subang',
        'Kabupaten Cianjur',
        'Kota Sukabumi',
        'Kabupaten Sukabumi',
        'Kota Tasikmalaya',
        'Kabupaten Tasikmalaya',
        'Kabupaten Ciamis',
        'Kota Banjar',
        'Kabupaten Pangandaran',
        'Kota Cirebon',
        'Kabupaten Cirebon',
        'Kabupaten Kuningan',
        'Kabupaten Majalengka',
        'Kabupaten Indramayu',
    ],
    Bali: [
        'Kota Denpasar',
        'Kabupaten Badung',
        'Kabupaten Gianyar',
        'Kabupaten Tabanan',
        'Kabupaten Bangli',
        'Kabupaten Klungkung',
        'Kabupaten Karangasem',
        'Kabupaten Buleleng',
        'Kabupaten Jembrana',
    ],
    Jakarta: [
        'Jakarta Pusat',
        'Jakarta Selatan',
        'Jakarta Barat',
        'Jakarta Timur',
        'Jakarta Utara',
        'Kepulauan Seribu',
    ],
    Banten: [
        'Kota Tangerang',
        'Kota Tangerang Selatan',
        'Kabupaten Tangerang',
        'Kota Serang',
        'Kabupaten Serang',
        'Kota Cilegon',
        'Kabupaten Pandeglang',
        'Kabupaten Lebak',
    ],
    'Jawa Tengah': ['Kota Semarang', 'Kota Surakarta'],
    'DI Yogyakarta': [
        'Kota Yogyakarta',
        'Kabupaten Sleman',
        'Kabupaten Bantul',
    ],
    'Jawa Timur': ['Kota Surabaya', 'Kota Malang'],
    'Sumatera Utara': ['Kota Medan'],
    Aceh: ['Kota Banda Aceh'],
    'Sumatera Barat': ['Kota Padang'],
    Riau: ['Kota Pekanbaru'],
    'Kepulauan Riau': ['Kota Batam', 'Kota Tanjungpinang'],
    Jambi: ['Kota Jambi'],
    'Sumatera Selatan': ['Kota Palembang'],
    Bengkulu: ['Kota Bengkulu'],
    Lampung: ['Kota Bandar Lampung'],
    'Bangka Belitung': ['Kota Pangkalpinang'],
    'Kalimantan Barat': ['Kota Pontianak'],
    'Kalimantan Tengah': ['Kota Palangka Raya'],
    'Kalimantan Selatan': ['Kota Banjarmasin'],
    'Kalimantan Timur': ['Kota Samarinda', 'Kota Balikpapan'],
    'Sulawesi Utara': ['Kota Manado'],
    'Sulawesi Selatan': ['Kota Makassar'],
    'Nusa Tenggara Barat': ['Kota Mataram'],
    'Nusa Tenggara Timur': ['Kota Kupang'],
    Maluku: ['Kota Ambon'],
    Papua: ['Kota Jayapura'],
};

/** "Kota Bandung, Jawa Barat", ...; then "Outside Indonesia". */
export const LISTENER_LOCATIONS: string[] = [
    ...Object.entries(AREAS).flatMap(([province, places]) =>
        places.map((place) =>
            // Jakarta's areas already say Jakarta.
            province === 'Jakarta' && place.startsWith('Jakarta')
                ? place
                : `${place}, ${province}`,
        ),
    ),
    'Outside Indonesia',
];

/** The suggested spelling of `value`, if it is one (case-insensitively). */
export function listedLocation(value: string): string | null {
    const needle = value.trim().toLowerCase();

    return (
        LISTENER_LOCATIONS.find((place) => place.toLowerCase() === needle) ??
        null
    );
}

/** Suggestions for what's typed: every word must match (in any order). */
export function suggestLocations(query: string, limit = 8): string[] {
    const words = query.trim().toLowerCase().split(/\s+/).filter(Boolean);

    if (words.length === 0) {
        return LISTENER_LOCATIONS.slice(0, limit);
    }

    return LISTENER_LOCATIONS.filter((place) => {
        const haystack = place.toLowerCase();

        return words.every((word) => haystack.includes(word));
    }).slice(0, limit);
}
