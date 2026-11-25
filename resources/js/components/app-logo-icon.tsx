import { Sprout } from 'lucide-react';
import { ComponentProps } from 'react';

export default function AppLogoIcon(props: ComponentProps<typeof Sprout>) {
    return <Sprout {...props} strokeWidth={1.8} />;
}
