import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function capitalizeFirstLetter(value: string): string {
    const characters = Array.from(value);
    const firstLetterIndex = characters.findIndex(
        (character) => character.toLocaleLowerCase() !== character.toLocaleUpperCase(),
    );

    if (firstLetterIndex === -1) {
        return value;
    }

    characters[firstLetterIndex] = characters[firstLetterIndex].toLocaleUpperCase();

    return characters.join('');
}
