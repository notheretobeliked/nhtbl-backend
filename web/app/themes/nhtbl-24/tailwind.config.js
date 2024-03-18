/** @type {import('tailwindcss').Config} config */
const config = {
  content: ['./app/**/*.php', './resources/**/*.{php,vue,js}'],
  theme: {
    extend: {
      colors: {}, // Extend Tailwind's default colors
    },
    fontFamily: {
      'sans': ['Inter Tight'],
      'display': ['Avara'],
    },
    colors: {
      'black': "000000",
      'white': "#FFFFFF",  
      'nhtbl-green': {
        base: '#E0FF00'
      },  
      'nhtbl-purple': {
        base: '#D59CE5',
        light: '#E4D5E8',
      },  
    },
    fontSize: {
      'sm': '.875rem',
      'base': ['1rem', '1.25'],
      'lg': ['1.25rem', '1.35'],
      'xl': ['2rem', '1.3'],
      '2xl': ['2.875rem', '1.2'],
     },
  },
  plugins: [],
};

export default config;
