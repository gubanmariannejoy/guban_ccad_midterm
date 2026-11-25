import AppLogoIcon from '@/components/app-logo-icon';
import { Link } from '@inertiajs/react';

interface AuthLayoutProps {
    children: React.ReactNode;
    name?: string;
    title?: string;
    description?: string;
    showLogo?: boolean;
}

export default function AuthSimpleLayout({ children, title, description, showLogo = true }: AuthLayoutProps) {
    return (
        <div className="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        {showLogo && (
                            <Link href={route('home')} className="flex flex-col items-center gap-2 font-medium">
                                <span className="bg-primary/10 flex size-12 items-center justify-center rounded-2xl">
                                    <AppLogoIcon className="text-primary size-7" />
                                </span>
                                <span className="text-sm font-semibold">Plant Nursery &amp; Botanical</span>
                            </Link>
                        )}

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-medium">{title}</h1>
                            <p className="text-muted-foreground text-center text-sm">{description}</p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
