import AppLogoIcon from '@/components/app-logo-icon';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

interface AuthLayoutProps {
    children: React.ReactNode;
    title?: string;
    description?: string;
}

export default function AuthSplitLayout({ children, title, description }: AuthLayoutProps) {
    const { quote } = usePage<SharedData>().props;

    return (
        <div className="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div className="bg-primary text-primary-foreground relative hidden h-full flex-col overflow-hidden p-10 lg:flex">
                <div className="border-primary-foreground/10 absolute -top-32 -right-28 size-[32rem] rounded-full border" />
                <div className="border-primary-foreground/10 absolute -right-8 -bottom-36 size-[28rem] rounded-full border" />
                <Link href={route('home')} className="relative z-20 flex items-center text-lg font-medium">
                    <AppLogoIcon className="mr-3 size-8" />
                    Plant Nursery &amp; Botanical
                </Link>
                {quote && (
                    <div className="relative z-20 mt-auto">
                        <blockquote className="space-y-2">
                            <p className="font-serif text-3xl leading-tight">&ldquo;{quote.message}&rdquo;</p>
                            <footer className="text-primary-foreground/70 text-sm">{quote.author}</footer>
                        </blockquote>
                    </div>
                )}
            </div>
            <div className="w-full lg:p-8">
                <div className="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                    <Link href={route('home')} className="relative z-20 flex items-center justify-center lg:hidden">
                        <AppLogoIcon className="text-primary size-10" />
                    </Link>
                    <div className="flex flex-col items-start gap-2 text-left sm:items-center sm:text-center">
                        <h1 className="text-xl font-medium">{title}</h1>
                        <p className="text-muted-foreground text-sm text-balance">{description}</p>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
