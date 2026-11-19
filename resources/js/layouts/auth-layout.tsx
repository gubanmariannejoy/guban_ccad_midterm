import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';

export default function AuthLayout({
    children,
    title,
    description,
    showLogo,
}: {
    children: React.ReactNode;
    title: string;
    description: string;
    showLogo?: boolean;
}) {
    return (
        <AuthLayoutTemplate title={title} description={description} showLogo={showLogo}>
            {children}
        </AuthLayoutTemplate>
    );
}
