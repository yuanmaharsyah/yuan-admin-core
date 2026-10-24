/** The app's 32px mark: the Settings 1:1 logo (or admin-core.branding.logo), else the name's first letter. */
export function AppMark({ name, logo }: { name: string; logo: string | null }) {
    return logo ? (
        <img
            src={logo}
            alt=""
            aria-hidden="true"
            className="size-8 shrink-0 rounded object-contain"
        />
    ) : (
        <div
            aria-hidden="true"
            className="flex size-8 shrink-0 items-center justify-center rounded bg-primary text-base font-bold text-card"
        >
            {name.charAt(0).toUpperCase()}
        </div>
    );
}
