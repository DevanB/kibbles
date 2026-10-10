import { format } from 'date-fns';
import { useState, type ReactNode } from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Bookmark, BookmarkMedia, QuotedBookmark } from '@/types';

const PREVIEW_LENGTH = 240;

export function formatBookmarkTime(value: string): string {
    return format(new Date(value), 'MMM d, yyyy');
}

function initials(name: string): string {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

function BookmarkMediaList({
    media,
    bookmarkId,
}: {
    media: BookmarkMedia[];
    bookmarkId: string;
}) {
    if (media.length === 0) {
        return null;
    }

    return (
        <div className="space-y-2" data-test={`bookmark-media-${bookmarkId}`}>
            {media.map((item, index) => {
                const key = `${item.type}-${item.url}-${index}`;
                const src = item.previewUrl ?? item.url;

                if (item.type === 'video' && item.mp4Url) {
                    return (
                        <video
                            key={key}
                            className="w-full rounded-lg bg-muted"
                            controls
                            playsInline
                            poster={src || undefined}
                            src={item.mp4Url}
                        />
                    );
                }

                if (item.type === 'gif' && item.mp4Url) {
                    return (
                        <video
                            key={key}
                            className="w-full rounded-lg bg-muted"
                            autoPlay
                            loop
                            muted
                            playsInline
                            poster={src || undefined}
                            src={item.mp4Url}
                        />
                    );
                }

                if (src === '') {
                    return (
                        <Badge key={key} variant="secondary">
                            {item.type === 'video' ? 'Video' : 'Media'}
                        </Badge>
                    );
                }

                return (
                    <img
                        key={key}
                        src={src}
                        alt=""
                        className="w-full rounded-lg object-cover"
                    />
                );
            })}
        </div>
    );
}

function QuotedCard({
    quoted,
    bookmarkId,
}: {
    quoted: QuotedBookmark;
    bookmarkId: string;
}) {
    return (
        <a
            href={quoted.url}
            target="_blank"
            rel="noreferrer"
            data-test={`bookmark-quoted-${bookmarkId}`}
            className="block space-y-2 rounded-xl border border-sidebar-border/70 p-3 text-left hover:bg-muted/40 dark:border-sidebar-border"
        >
            <p className="text-sm font-medium">
                {quoted.authorName}{' '}
                <span className="font-normal text-muted-foreground">
                    @{quoted.authorUsername}
                </span>
            </p>
            {quoted.text !== '' && (
                <p className="text-sm whitespace-pre-wrap">{quoted.text}</p>
            )}
            <BookmarkMediaList
                media={quoted.media}
                bookmarkId={`${bookmarkId}-quoted`}
            />
        </a>
    );
}

export function BookmarkCard({
    bookmark,
    actions,
}: {
    bookmark: Bookmark;
    actions?: ReactNode;
}) {
    const [expanded, setExpanded] = useState(false);
    const needsExpansion = bookmark.text.length > PREVIEW_LENGTH;
    const text =
        expanded || !needsExpansion
            ? bookmark.text
            : `${bookmark.text.slice(0, PREVIEW_LENGTH).trimEnd()}…`;

    return (
        <article
            data-test={`bookmark-${bookmark.id}`}
            className="space-y-3 rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border"
        >
            <div className="flex items-start gap-3">
                <Avatar className="size-10">
                    {bookmark.authorAvatarUrl ? (
                        <AvatarImage src={bookmark.authorAvatarUrl} alt="" />
                    ) : null}
                    <AvatarFallback>
                        {initials(
                            bookmark.authorName || bookmark.authorUsername,
                        )}
                    </AvatarFallback>
                </Avatar>
                <div className="min-w-0 flex-1">
                    <p className="truncate font-medium">
                        {bookmark.authorName}
                    </p>
                    <p className="truncate text-sm text-muted-foreground">
                        @{bookmark.authorUsername} ·{' '}
                        {formatBookmarkTime(bookmark.postedAt)}
                    </p>
                </div>
            </div>

            {bookmark.text !== '' && (
                <p
                    data-test={`bookmark-text-${bookmark.id}`}
                    className="text-sm whitespace-pre-wrap"
                >
                    {text}
                </p>
            )}

            {needsExpansion && (
                <Button
                    type="button"
                    variant="link"
                    size="sm"
                    className="h-auto p-0"
                    data-test={`show-full-post-${bookmark.id}`}
                    onClick={() => {
                        setExpanded((current) => !current);
                    }}
                >
                    {expanded ? 'Show less' : 'Show full post'}
                </Button>
            )}

            <BookmarkMediaList
                media={bookmark.media}
                bookmarkId={bookmark.id}
            />

            {bookmark.quotedPost ? (
                <QuotedCard
                    quoted={bookmark.quotedPost}
                    bookmarkId={bookmark.id}
                />
            ) : null}

            {actions}
        </article>
    );
}
