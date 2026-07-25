import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const STORAGE_KEY = 'siteguardian-active-website';

/**
 * Tracks which website the top-bar switcher currently points at, shared
 * between the layout (sidebar links) and pages that render per-website
 * content (Dashboard). Persisted to localStorage so it survives navigation.
 */
export default function useActiveWebsite() {
    const { websites = [] } = usePage().props;
    const [activeWebsiteId, setActiveWebsiteIdState] = useState(null);

    useEffect(() => {
        const stored = Number(localStorage.getItem(STORAGE_KEY)) || null;
        const valid = websites.find((w) => w.id === stored);
        setActiveWebsiteIdState(valid ? stored : websites[0]?.id ?? null);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [websites.map((w) => w.id).join(',')]);

    function setActiveWebsiteId(id) {
        setActiveWebsiteIdState(id);
        localStorage.setItem(STORAGE_KEY, String(id));
    }

    return [activeWebsiteId, setActiveWebsiteId];
}
