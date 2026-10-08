import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { ModalLink } from '@inertiaui/modal-react';
import { cn } from 'cn';
import { Plus } from 'lucide-react';
import GameController from '@/actions/App/Http/Controllers/GameController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button, buttonVariants } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { index, show } from '@/routes/games';
import type { BreadcrumbItem, Game } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Games',
        href: index(),
    },
];

export default function Index({ games }: { games: Game[] }) {
    setLayoutProps({ breadcrumbs });

    return (
        <>
            <Head title="Games" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Games"
                        description="Your personal catalog of games"
                    />

                    {games.length > 0 && (
                        <Button asChild data-test="create-game-button">
                            <ModalLink
                                href={GameController.create.url()}
                                navigate
                            >
                                <Plus />
                                Add Game
                            </ModalLink>
                        </Button>
                    )}
                </div>

                {games.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center dark:border-sidebar-border">
                        <p className="text-lg font-medium">No games yet</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Add your first game to start your catalog.
                        </p>
                        <ModalLink
                            href={GameController.create.url()}
                            navigate
                            className={cn(buttonVariants(), 'mt-4')}
                            data-test="create-game-button"
                        >
                            Add Game
                        </ModalLink>
                    </div>
                ) : (
                    <ul className="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                        {games.map((game) => (
                            <li key={game.id}>
                                <Link
                                    href={show(game)}
                                    className="group flex flex-col gap-2"
                                    data-test={`game-open-${game.id}`}
                                >
                                    <span className="relative block overflow-hidden rounded-xl">
                                        {game.imageUrl ? (
                                            <img
                                                src={game.imageUrl}
                                                alt=""
                                                className="aspect-[3/4] w-full object-cover"
                                                data-test={`game-art-${game.id}`}
                                            />
                                        ) : (
                                            <span
                                                aria-label="Box art"
                                                data-test={`game-art-slot-${game.id}`}
                                                className="block aspect-[3/4] w-full bg-muted"
                                            />
                                        )}
                                        <Badge
                                            variant="secondary"
                                            data-test={`game-status-${game.id}`}
                                            className="absolute right-2 bottom-2"
                                        >
                                            {game.statusLabel}
                                        </Badge>
                                    </span>
                                    <span
                                        className="font-medium"
                                        data-test={`game-title-${game.id}`}
                                    >
                                        {game.title}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

Index.layout = [AppLayout, { breadcrumbs }];
