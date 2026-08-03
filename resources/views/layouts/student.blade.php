<!DOCTYPE html>
<html lang="en" class="h-full bg-[#f4f6fa]">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>BudgetApp - Financial Dashboard</title>
 <link rel="stylesheet" href="{{ asset('css/app.css') }}">
 <script src="{{ asset('js/chart.min.js') }}"></script>
 @livewireStyles
</head>
<body class="h-full font-sans antialiased text-slate-800 bg-[#f4f6fa]">

 <!-- Mobile Backdrop Overlay -->
 <div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 hidden lg:hidden transition-opacity"></div>

 <div class="min-h-full flex flex-col lg:flex-row w-full">
     
     <!-- Floating Pill Sidebar Container -->
     <aside id="sidebar" class="fixed inset-y-3 left-3 z-50 w-64 bg-white border border-slate-200/70 rounded-3xl p-5 flex flex-col justify-between transform -translate-x-[calc(100%+1rem)] lg:translate-x-0 transition-transform duration-300 ease-in-out lg:fixed lg:top-4 lg:bottom-4 lg:left-4 lg:z-30 shrink-0 overflow-y-auto shadow-sm">
      
         <!-- Top Section: Brand & Nav Links -->
         <div class="space-y-6">
             <!-- Sidebar Brand Logo & Mobile Close Button -->
             <div class="flex items-center justify-between px-2 pt-1">
                 <a href="{{ route('student.dashboard') }}" class="flex items-center gap-3">
                     <div class="h-10 w-10 bg-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-md shadow-indigo-600/30">
                         <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                         </svg>
                     </div>
                     <span class="font-black text-slate-900 text-xl tracking-tight">BudgetApp</span>
                 </a>
                 <button id="close-sidebar-btn" type="button" class="lg:hidden p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                     </svg>
                 </button>
             </div>

             <!-- Pill-Style Navigation Links -->
             <nav class="space-y-1.5">
                 <a href="{{ route('student.dashboard') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-2xl font-bold text-sm transition-all {{ request()->routeIs('student.dashboard') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                     Dashboard
                 </a>
                 <a href="{{ route('student.expenses.create') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ request()->routeIs('student.expenses.create') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                     Add Expense
                 </a>
                 <a href="{{ route('student.receipt-scanner') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ request()->routeIs('student.receipt-scanner') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3" stroke-width="2"/></svg>
                     Receipt Scanner
                 </a>
                 <a href="{{ route('student.forecast') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ request()->routeIs('student.forecast') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                     Spending Forecast
                 </a>
                 <a href="{{ route('student.goals') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ request()->routeIs('student.goals') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                     Savings Goal
                 </a>
                 <a href="{{ route('student.simulation') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ request()->routeIs('student.simulation') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                     Purchase Simulator
                 </a>
                 <a href="{{ route('student.notifications') }}"
                    class="flex items-center justify-between px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ request()->routeIs('student.notifications') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                     <div class="flex items-center gap-3">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 01-6 0v-1m6 0H9"/></svg>
                         Notifications
                     </div>
                     <span class="bg-rose-500 text-white text-[11px] font-extrabold px-2 py-0.5 rounded-full">3</span>
                 </a>
                 <a href="{{ route('student.profile') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ request()->routeIs('student.profile*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                     Profile
                 </a>
                 <a href="{{ route('student.settings') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ request()->routeIs('student.settings*') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/25' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                     Settings
                 </a>
             </nav>
         </div>

         <!-- Bottom Logout Section -->
         <div class="pt-4 border-t border-slate-100 mt-4">
             <form method="POST" action="{{ route('logout') }}">
                 @csrf
                 <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-2xl font-semibold text-sm text-rose-600 hover:bg-rose-50 transition-all">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                     Logout
                 </button>
             </form>
         </div>
     </aside>

     <!-- Right Column Content Area -->
     <div class="flex-1 flex flex-col min-w-0 w-full lg:pl-72">
         
         <!-- Mobile Top Navigation Header -->
         <header class="lg:hidden bg-white border-b border-slate-200/80 px-4 py-3 flex items-center justify-between sticky top-0 z-30 shadow-sm w-full">
             <div class="flex items-center gap-3">
                 <button id="open-sidebar-btn" type="button" class="p-2 text-slate-600 hover:text-indigo-600 rounded-xl focus:outline-none bg-slate-100/80">
                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                     </svg>
                 </button>
                 <a href="{{ route('student.dashboard') }}" class="flex items-center gap-2">
                     <div class="h-8 w-8 bg-indigo-600 rounded-xl flex items-center justify-center text-white shadow-md shadow-indigo-600/30">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                         </svg>
                     </div>
                     <span class="font-extrabold text-slate-900 text-lg tracking-tight">BudgetApp</span>
                 </a>
             </div>
             <div class="flex items-center gap-2">
                 <livewire:student.notification-center />
             </div>
         </header>

         <!-- Main View Content -->
         <main class="flex-1 min-w-0 w-full p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">
             {{ $slot }}
         </main>
     </div>
 </div>

 <!-- Vanilla JavaScript for Mobile Sidebar Toggle -->
 <script>
     document.addEventListener('DOMContentLoaded', () => {
         const sidebar = document.getElementById('sidebar');
         const overlay = document.getElementById('sidebar-overlay');
         const openBtn = document.getElementById('open-sidebar-btn');
         const closeBtn = document.getElementById('close-sidebar-btn');
       
         function openSidebar() {
             sidebar.classList.remove('-translate-x-[calc(100%+1rem)]');
             overlay.classList.remove('hidden');
         }
         function closeSidebar() {
             sidebar.classList.add('-translate-x-[calc(100%+1rem)]');
             overlay.classList.add('hidden');
         }
       
         if (openBtn) openBtn.addEventListener('click', openSidebar);
         if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
         if (overlay) overlay.addEventListener('click', closeSidebar);
     });
 </script>
 @livewireScripts
 <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>