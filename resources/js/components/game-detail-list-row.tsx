import { cn } from 'cn';
import type { ReactNode } from 'react';

export default function GameDetailListRow({
    primary,
    secondary,
    trailing,
    className,
}: {
    primary: ReactNode;
    secondary?: ReactNode;
    trailing?: ReactNode;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'flex w-full items-center justify-between gap-4 px-4 py-3',
                className,
            )}
        >
            <div className="min-w-0">
                <p className="text-sm font-medium">{primary}</p>
                {secondary ? (
                    <p className="text-sm text-muted-foreground">{secondary}</p>
                ) : null}
            </div>
            {trailing ? (
                <div className="flex shrink-0 items-center">{trailing}</div>
            ) : null}
        </div>
    );
}
