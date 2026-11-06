import { Link, usePage } from '@inertiajs/react';
import { Bookmark, Gamepad2, LayoutGrid, Link2 } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { dashboard } from '@/routes';
import { index as savedLinks } from '@/routes/bookmarks';
import { index as games } from '@/routes/games';
import { index as bookmarkReviews } from '@/routes/x-bookmark-reviews';
import { index as bookmarks } from '@/routes/x-bookmarks';
import type { NavItem, Tag } from '@/types';

function bookmarkNavItems(
    tags: Tag[],
    xIsActive: boolean,
    bookmarksActive: boolean,
): NavItem[] {
    return [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Games',
            href: games(),
            icon: Gamepad2,
        },
        {
            title: 'Bookmarks',
            href: savedLinks(),
            icon: Link2,
            testId: 'sidebar-bookmarks',
            isActive: bookmarksActive,
            items: [
                {
                    title: 'All',
                    href: savedLinks(),
                    testId: 'sidebar-bookmarks-all',
                },
                {
                    title: 'Untagged',
                    href: savedLinks({ query: { tag: 'untagged' } }),
                    testId: 'sidebar-bookmarks-untagged',
                },
                ...tags.map((tag) => ({
                    title: tag.name,
                    href: savedLinks({ query: { tag: tag.id } }),
                    testId: `sidebar-bookmarks-tag-${tag.id}`,
                })),
            ],
        },
        {
            title: 'X',
            href: bookmarks(),
            icon: Bookmark,
            testId: 'sidebar-x',
            isActive: xIsActive,
            items: [
                {
                    title: 'All',
                    href: bookmarks(),
                    testId: 'sidebar-x-all',
                },
                {
                    title: 'Review',
                    href: bookmarkReviews(),
                    testId: 'sidebar-x-review',
                },
                {
                    title: 'Untagged',
                    href: bookmarks({ query: { tag: 'untagged' } }),
                    testId: 'sidebar-x-untagged',
                },
                ...tags.map((tag) => ({
                    title: tag.name,
                    href: bookmarks({ query: { tag: tag.id } }),
                    testId: `sidebar-x-tag-${tag.id}`,
                })),
            ],
        },
    ];
}

export function AppSidebar() {
    const { tags } = usePage<{ tags: Tag[] }>().props;
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain
                    items={bookmarkNavItems(
                        tags,
                        isCurrentOrParentUrl(bookmarks()) ||
                            isCurrentUrl(bookmarkReviews()),
                        isCurrentOrParentUrl(savedLinks()),
                    )}
                />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
