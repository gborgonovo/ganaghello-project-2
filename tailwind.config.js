import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
    ],

    theme: {
        extend: {
            colors: {
                paper:          '#EFEDE3',
                ink:            '#2C322A',
                salvia:         '#5C6B4F',
                terracotta:     '#C98A6B',
                'paper-dark':   '#E0DDD2',
                'salvia-light': '#8A9E7A',
                'salvia-dark':  '#3D4A35',
            },
            fontFamily: {
                sans:  ['Inter', ...defaultTheme.fontFamily.sans],
                serif: ['Lora', 'Georgia', ...defaultTheme.fontFamily.serif],
            },
            borderRadius: {
                sm:      '2px',
                DEFAULT: '5px',
                lg:      '7px',
                xl:      '11px',
                '2xl':   '15px',
            },
        },
    },

    plugins: [forms],
};
