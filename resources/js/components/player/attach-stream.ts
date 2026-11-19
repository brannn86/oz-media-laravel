/**
 * Points a media element at a live stream URL.
 *
 * HLS (.m3u8) plays natively in Safari; elsewhere hls.js is loaded on demand,
 * so visitors on plain Icecast/MP3 streams never download it.
 * Returns a cleanup function that fully stops the download.
 */
export function attachStream(
    media: HTMLMediaElement,
    url: string,
    onFatalError: () => void,
): () => void {
    let destroyed = false;
    let destroyHls: (() => void) | null = null;

    const isHls = new URL(url, window.location.href).pathname
        .toLowerCase()
        .endsWith('.m3u8');

    if (isHls && !media.canPlayType('application/vnd.apple.mpegurl')) {
        void import('hls.js').then(({ default: Hls }) => {
            if (destroyed) {
                return;
            }

            if (!Hls.isSupported()) {
                onFatalError();

                return;
            }

            const hls = new Hls({ lowLatencyMode: true });

            hls.on(Hls.Events.ERROR, (_event, data) => {
                if (data.fatal) {
                    onFatalError();
                }
            });
            hls.loadSource(url);
            hls.attachMedia(media);
            destroyHls = () => hls.destroy();
        }, onFatalError);
    } else {
        media.src = url;
    }

    return () => {
        destroyed = true;
        destroyHls?.();
        media.pause();
        media.removeAttribute('src');
        media.load();
    };
}
