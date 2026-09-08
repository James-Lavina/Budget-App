/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
    './app/Http/Livewire/**/*.php',
    "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
  ],
  safelist: [
    {
      pattern: /bg-(amber|orange|rose|pink|fuchsia|purple|indigo|blue|cyan|emerald|lime|slate)-(50|100|500|600)/,
    },
    {
      pattern: /text-(amber|orange|rose|pink|fuchsia|purple|indigo|blue|cyan|emerald|lime|slate)-700/,
    },
  ],
  theme: {
    extend: {},
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}