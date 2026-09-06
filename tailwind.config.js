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
    'bg-amber-50', 'text-amber-600', 'bg-amber-500',
    'bg-blue-50', 'text-blue-600', 'bg-blue-500',
    'bg-emerald-50', 'text-emerald-600', 'bg-emerald-500',
    'bg-cyan-50', 'text-cyan-600', 'bg-cyan-500',
    'bg-purple-50', 'text-purple-600', 'bg-purple-500',
    'bg-pink-50', 'text-pink-600', 'bg-pink-500',
    'bg-indigo-50', 'text-indigo-600', 'bg-indigo-500',
    'bg-rose-50', 'text-rose-600', 'bg-rose-500',
    'bg-orange-50', 'text-orange-600', 'bg-orange-500',
    'bg-slate-100', 'bg-slate-400', 'text-slate-600',
  ],
  theme: {
    extend: {},
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}