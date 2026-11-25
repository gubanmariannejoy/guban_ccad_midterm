import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    return (
        <div className="flex min-w-0 items-center gap-3 text-left">
            <span className="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                <AppLogoIcon className="size-6" />
            </span>
            <span className="grid min-w-0 flex-1 leading-tight">
                <span className="truncate text-sm font-semibold tracking-tight">Plant Nursery &amp; Botanical</span>
                <span className="text-muted-foreground mt-1 truncate text-[11px]">Thoughtfully grown, nursery direct</span>
            </span>
        </div>
    );
}
