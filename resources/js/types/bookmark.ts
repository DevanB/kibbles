export type BookmarkMedia = {
    type: 'photo' | 'video' | 'gif';
    url: string;
    previewUrl: string | null;
    width: number | null;
    height: number | null;
    mp4Url: string | null;
};

export type QuotedBookmark = {
    authorName: string;
    authorUsername: string;
    authorAvatarUrl: string | null;
    text: string;
    postedAt: string;
    media: BookmarkMedia[];
    url: string;
};

export type Bookmark = {
    id: string;
    xPostId: string;
    authorName: string;
    authorUsername: string;
    authorAvatarUrl: string | null;
    text: string;
    postedAt: string;
    firstSeenAt: string;
    media: BookmarkMedia[];
    quotedPost: QuotedBookmark | null;
    url: string;
};

export type BookmarkPage = {
    data: Bookmark[];
};

export type XConnection = {
    username: string;
    lastSyncedAt: string | null;
    lastFullSyncedAt: string | null;
};
