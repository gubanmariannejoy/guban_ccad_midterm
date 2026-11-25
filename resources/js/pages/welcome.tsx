import AppLogoIcon from '@/components/app-logo-icon';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, Leaf, PackageCheck, Sprout } from 'lucide-react';

const highlights = [
    {
        icon: Sprout,
        title: 'Grown with intention',
        description: 'A thoughtfully maintained collection for homes, gardens, and growing spaces.',
    },
    {
        icon: Leaf,
        title: 'Know every plant',
        description: 'Explore botanical details, pot sizes, and current availability before you order.',
    },
    {
        icon: PackageCheck,
        title: 'Simple, direct ordering',
        description: 'Build your order online and follow it from confirmation through dispatch.',
    },
];

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;
    const actionHref = auth.user ? route('dashboard') : route('register');
    const actionLabel = auth.user ? 'Go to your dashboard' : 'Create an account';

    return (
        <>
            <Head title="Welcome" />
            <div className="bg-background min-h-svh">
                <header className="border-border/70 bg-card/80 border-b">
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-5 px-5 py-4 sm:px-8">
                        <Link href={route('home')} className="flex items-center gap-3">
                            <span className="bg-primary/10 text-primary flex size-11 items-center justify-center rounded-2xl">
                                <AppLogoIcon className="size-7" />
                            </span>
                            <span className="grid leading-tight">
                                <span className="text-sm font-semibold tracking-tight sm:text-base">Plant Nursery &amp; Botanical</span>
                                <span className="text-muted-foreground mt-1 text-xs">Thoughtfully grown, nursery direct</span>
                            </span>
                        </Link>
                        <nav aria-label="Account" className="flex items-center gap-2 sm:gap-3">
                            {auth.user ? (
                                <Link
                                    href={actionHref}
                                    className="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-medium transition-colors"
                                >
                                    Dashboard <ArrowRight className="size-4" aria-hidden="true" />
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={route('login')}
                                        className="text-foreground hover:bg-accent rounded-full px-4 py-2.5 text-sm font-medium transition-colors"
                                    >
                                        Sign in
                                    </Link>
                                    <Link
                                        href={route('register')}
                                        className="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex items-center gap-2 rounded-full px-4 py-2.5 text-sm font-medium transition-colors"
                                    >
                                        Join the nursery <ArrowRight className="size-4" aria-hidden="true" />
                                    </Link>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main>
                    <section className="mx-auto grid max-w-7xl items-center gap-10 px-5 py-12 sm:px-8 sm:py-16 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16 lg:py-24">
                        <div className="max-w-2xl">
                            <p className="text-primary inline-flex items-center gap-2 text-xs font-semibold tracking-[0.18em] uppercase">
                                <span className="bg-primary h-px w-8" />A nursery for considered growing
                            </p>
                            <h1 className="mt-6 font-serif text-5xl leading-[1.04] tracking-tight sm:text-6xl lg:text-7xl">
                                Rooted in care.
                                <span className="text-primary block italic">Grown for your space.</span>
                            </h1>
                            <p className="text-muted-foreground mt-6 max-w-xl text-base leading-7 sm:text-lg">
                                Discover a considered collection of plants, with clear botanical details and an easy way to order directly from our
                                nursery.
                            </p>
                            <div className="mt-8 flex flex-wrap items-center gap-3">
                                <Link
                                    href={actionHref}
                                    className="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex items-center gap-2 rounded-full px-6 py-3 text-sm font-semibold shadow-sm transition-colors"
                                >
                                    {actionLabel} <ArrowRight className="size-4" aria-hidden="true" />
                                </Link>
                                {!auth.user && (
                                    <Link
                                        href={route('login')}
                                        className="border-border bg-card hover:bg-accent inline-flex items-center rounded-full border px-6 py-3 text-sm font-medium transition-colors"
                                    >
                                        I already have an account
                                    </Link>
                                )}
                            </div>
                            <div className="text-muted-foreground mt-9 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                                <span className="inline-flex items-center gap-2">
                                    <span className="bg-primary size-1.5 rounded-full" /> Nursery-direct selection
                                </span>
                                <span className="inline-flex items-center gap-2">
                                    <span className="bg-primary size-1.5 rounded-full" /> Clear stock and pricing
                                </span>
                            </div>
                        </div>

                        <div className="bg-secondary/70 relative isolate flex min-h-80 items-center justify-center overflow-hidden rounded-[2rem] p-8 sm:min-h-[26rem] sm:p-12">
                            <div className="border-primary/10 absolute size-[23rem] rounded-full border" />
                            <div className="border-primary/10 absolute size-[18rem] rounded-full border" />
                            <div className="bg-primary/10 absolute size-56 rounded-full" />
                            <div className="bg-primary text-primary-foreground shadow-primary/20 relative flex size-44 items-center justify-center rounded-[2.5rem] shadow-xl sm:size-52 sm:rounded-[3rem]">
                                <Sprout className="size-28 sm:size-36" strokeWidth={1.2} aria-hidden="true" />
                            </div>
                            <div className="bg-card/95 absolute top-8 left-5 flex items-center gap-3 rounded-2xl border p-3 shadow-sm sm:top-12 sm:left-10">
                                <span className="bg-secondary text-primary flex size-10 items-center justify-center rounded-xl">
                                    <Leaf className="size-5" aria-hidden="true" />
                                </span>
                                <span>
                                    <span className="block text-xs font-semibold">Botanical care</span>
                                    <span className="text-muted-foreground mt-0.5 block text-[11px]">Details that help you grow</span>
                                </span>
                            </div>
                            <span className="bg-card/95 text-primary absolute right-5 bottom-8 rounded-full border px-4 py-2 text-xs font-semibold tracking-[0.14em] uppercase shadow-sm sm:right-10 sm:bottom-12">
                                Grow well, live green
                            </span>
                        </div>
                    </section>

                    <section className="border-border/70 bg-card/60 border-t">
                        <div className="mx-auto grid max-w-7xl gap-4 px-5 py-10 sm:px-8 md:grid-cols-3 md:gap-6 md:py-14">
                            {highlights.map(({ icon: Icon, title, description }, index) => (
                                <article key={title} className="flex gap-4 rounded-2xl p-4 sm:p-5">
                                    <span className="bg-secondary text-primary flex size-12 shrink-0 items-center justify-center rounded-2xl">
                                        <Icon className="size-5" aria-hidden="true" />
                                    </span>
                                    <div>
                                        <p className="text-muted-foreground text-xs font-semibold tracking-[0.16em] uppercase">0{index + 1}</p>
                                        <h2 className="mt-2 font-serif text-xl">{title}</h2>
                                        <p className="text-muted-foreground mt-2 text-sm leading-6">{description}</p>
                                    </div>
                                </article>
                            ))}
                        </div>
                    </section>
                </main>

                <footer className="text-muted-foreground mx-auto flex max-w-7xl flex-col gap-2 px-5 py-6 text-xs sm:flex-row sm:items-center sm:justify-between sm:px-8">
                    <span>© {new Date().getFullYear()} Plant Nursery &amp; Botanical</span>
                    <span>Rooted in care. Grown for your space.</span>
                </footer>
            </div>
        </>
    );
}
