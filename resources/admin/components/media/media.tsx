import { createContext, useContext, useEffect, useState } from 'react';
import { requestJson } from '../../lib/http';

export interface MediaItem {
    id: number;
    url: string;
    path: string;
    name: string;
    mime: string | null;
    size: number;
    width: number | null;
    height: number | null;
    alt: Record<string, string>;
    is_image: boolean;
}

export interface MediaUrls {
    index: string;
    list: string;
    show: string;
    store: string;
    update: string;
    destroy: string;
}

export const MediaContext = createContext<{ urls: MediaUrls; accept: string } | null>(null);

export function useMediaConfig() {
    const config = useContext(MediaContext);

    if (!config) {
        throw new Error('MediaContext is missing: wrap the editor in <MediaContext.Provider>.');
    }

    return config;
}

const cache = new Map<number, Promise<MediaItem | null>>();

export function rememberMedia(item: MediaItem): void {
    cache.set(item.id, Promise.resolve(item));
}

/**
 * The media item behind an image/file field value (an id), loaded once per page.
 */
export function useMediaItem(id: number | null): MediaItem | null {
    const { urls } = useMediaConfig();
    const [item, setItem] = useState<MediaItem | null>(null);

    useEffect(() => {
        if (id === null) {
            setItem(null);

            return;
        }

        if (!cache.has(id)) {
            cache.set(id, requestJson<MediaItem>(urls.show.replace('__ID__', String(id))).catch(() => null));
        }

        let active = true;
        cache.get(id)!.then((loaded) => active && setItem(loaded));

        return () => {
            active = false;
        };
    }, [id, urls.show]);

    return item;
}

export function formatSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    return bytes < 1024 * 1024 ? `${Math.round(bytes / 1024)} KB` : `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}
