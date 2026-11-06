import type { Tag } from './tag';

export type LinkPreviewStatus = 'pending' | 'ready' | 'failed';

export type SavedLink = {
    id: string;
    url: string;
    title: string | null;
    siteName: string | null;
    description: string | null;
    imageUrl: string | null;
    previewStatus: LinkPreviewStatus;
    tags: Tag[];
};

export type LinkPage = {
    data: SavedLink[];
};
