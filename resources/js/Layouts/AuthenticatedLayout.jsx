import AnnouncementBanner from '@/Components/AnnouncementBanner';
import FlashBanner from '@/Components/FlashBanner';
import Sidebar from '@/Components/Sidebar';
import TopBar from '@/Components/TopBar';
import useActiveWebsite from '@/hooks/useActiveWebsite';

export default function AuthenticatedLayout({ header, children }) {
    const [activeWebsiteId, setActiveWebsiteId] = useActiveWebsite();

    return (
        <div className="flex h-screen flex-col bg-background text-foreground">
            <TopBar activeWebsiteId={activeWebsiteId} onSelectWebsite={setActiveWebsiteId} />

            <div className="flex flex-1 overflow-hidden">
                <Sidebar activeWebsiteId={activeWebsiteId} />

                <div className="flex flex-1 flex-col overflow-y-auto">
                    <AnnouncementBanner />
                    <FlashBanner />

                    {header && (
                        <div className="border-b border-border bg-card/50 px-6 py-4">
                            {header}
                        </div>
                    )}

                    <main className="flex-1 p-6">{children}</main>
                </div>
            </div>
        </div>
    );
}
