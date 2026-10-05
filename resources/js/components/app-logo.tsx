export default function AppLogo() {
    return (
        <>
            <div
                className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
                data-test="app-logo"
            >
                <span
                    className="text-sm font-semibold"
                    data-test="app-logo-mark"
                >
                    K
                </span>
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    Kibbles
                </span>
            </div>
        </>
    );
}
