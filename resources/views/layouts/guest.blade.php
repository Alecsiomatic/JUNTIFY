<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>DDU - {{ config('app.name', 'Laravel') }}</title>

        <!-- Adobe Fonts - Nota: Necesitas configurar tu Adobe Fonts Kit ID -->
        <!-- <link rel="stylesheet" href="https://use.typekit.net/YOUR_KIT_ID.css"> -->

        <!-- Google Fonts - Lato -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --ddu-lavanda: #6F78E4;
                --ddu-aqua: #6DDEDD;
            }

            .font-area-inktrap {
                font-family: 'area-inktrap', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }

            .font-lato {
                font-family: 'Lato', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }

            .font-zuume {
                font-family: 'zuume', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                text-transform: uppercase;
            }

            .bg-ddu-gradient {
                background: linear-gradient(135deg, var(--ddu-lavanda) 0%, var(--ddu-aqua) 100%);
            }

            .text-ddu-lavanda { color: var(--ddu-lavanda); }
            .text-ddu-aqua { color: var(--ddu-aqua); }
            .bg-ddu-lavanda { background-color: var(--ddu-lavanda); }
            .bg-ddu-aqua { background-color: var(--ddu-aqua); }
            .border-ddu-lavanda { border-color: var(--ddu-lavanda); }
            .border-ddu-aqua { border-color: var(--ddu-aqua); }
        </style>
    </head>
    <body class="font-lato text-gray-900 antialiased bg-ddu-gradient">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <div class="mb-8">
                <div class="flex flex-col items-center">
                    <div class="text-6xl font-area-inktrap font-bold text-white mb-2">
                        DDU
                    </div>
                    <div class="font-zuume text-white text-sm tracking-wider opacity-90">
                        PANEL DE ADMINISTRACIÓN
                    </div>
                </div>
            </div>

            <div class="w-full sm:max-w-md px-6 py-8 bg-white/95 backdrop-blur-sm shadow-2xl overflow-hidden sm:rounded-2xl border border-white/20">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
